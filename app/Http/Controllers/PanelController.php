<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Customer;
use App\Models\LaundryProfile;
use App\Models\MasterItem;
use App\Models\PaymentOption;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\WaTemplate;
use App\Services\OrderWorkflowService;
use App\Services\TransactionCalculatorService;
use App\Services\VoucherValidationService;
use App\Services\WaMessageBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class PanelController extends Controller
{
    private const ALLOWED_IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const IMAGE_MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_IMAGE_SIZE_BYTES = 4_194_304;

    private const ALLOWED_PUBLIC_MEDIA_DIRECTORIES = ['completion-proofs/', 'payment-proofs/', 'payment-qris/', 'laundry-profiles/'];

    public function __construct(
        private readonly TransactionCalculatorService $calculatorService,
        private readonly VoucherValidationService $voucherValidationService,
        private readonly OrderWorkflowService $orderWorkflowService,
        private readonly WaMessageBuilderService $waMessageBuilderService
    ) {
    }

    public function pos(): View
    {
        return view('pos.index', [
            'services' => MasterItem::query()->where('type', 'service')->where('is_active', true)->orderBy('name')->get(),
            'durations' => MasterItem::query()->where('type', 'duration')->where('is_active', true)->orderBy('name')->get(),
            'addons' => MasterItem::query()->where('type', 'addon')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function checkoutPos(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'service_id' => ['required', 'integer', 'exists:master_items,id'],
            'qty' => ['required', 'numeric', 'min:0.1'],
            'duration_id' => ['nullable', 'integer', 'exists:master_items,id'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', 'exists:master_items,id'],
            'voucher_code' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = Customer::query()->updateOrCreate(
            ['phone' => $validated['customer_phone']],
            [
                'name' => $validated['customer_name'],
                'address' => $validated['customer_address'] ?? null,
            ]
        );

        $itemPayload = [
            'service_id' => (int) $validated['service_id'],
            'qty' => (float) $validated['qty'],
            'duration_id' => ! empty($validated['duration_id']) ? (int) $validated['duration_id'] : null,
            'addon_ids' => $validated['addon_ids'] ?? [],
        ];

        try {
            $calculated = $this->calculatorService->calculate([$itemPayload]);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['service_id' => $exception->getMessage()])->withInput();
        }

        $voucher = null;
        $discountAmount = 0.0;

        if (! empty($validated['voucher_code'])) {
            $voucherResult = $this->voucherValidationService->validateCode(
                $validated['voucher_code'],
                $calculated['total_amount']
            );

            if (! $voucherResult['is_valid']) {
                return back()->withErrors(['voucher_code' => $voucherResult['message']])->withInput();
            }

            $voucher = $voucherResult['voucher'];
            $discountAmount = $voucherResult['discount_amount'];
        }

        $actorId = (int) auth()->id();
        $receiptNumber = $this->generateReceiptNumber();

        try {
            $transaction = DB::transaction(function () use ($customer, $calculated, $voucher, $discountAmount, $validated, $actorId, $receiptNumber) {
                $transactionPayload = [
                'receipt_number' => $receiptNumber,
                'customer_id' => $customer->id,
                'cashier_id' => $actorId,
                'voucher_id' => $voucher?->id,
                'status' => 'antrean',
                'payment_status' => 'unpaid',
                'total_amount' => $calculated['total_amount'],
                'discount_amount' => $discountAmount,
                'final_amount' => max(0, $calculated['total_amount'] - $discountAmount),
                ];

                $created = Transaction::query()->create($transactionPayload);

                foreach ($calculated['lines'] as $line) {
                    TransactionDetail::query()->create([
                        'transaction_id' => $created->id,
                        'master_service_id' => $line['service_id'],
                        'qty' => $line['qty'],
                        'snapshot_data' => $line['snapshot_data'],
                    ]);
                }

                TransactionLog::query()->create([
                    'transaction_id' => $created->id,
                    'user_id' => $actorId,
                    'action_type' => 'created_order',
                    'description' => 'Order created via web POS checkout.',
                ]);

                return $created;
            });
        } catch (QueryException $exception) {
            Log::error('POS checkout failed due to database query exception.', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'service_id' => 'Checkout gagal karena struktur database belum sinkron. Jalankan migration terbaru di server.',
            ])->withInput();
        }

        return redirect()
            ->route('orders.tracking')
            ->with('status', 'Checkout berhasil. Resi: '.$transaction->receipt_number);
    }

    public function master(): View
    {
        $this->authorizeOwnerAdmin();

        return view('master.index', [
            'services' => MasterItem::query()->where('type', 'service')->latest('id')->get(),
            'durations' => MasterItem::query()->where('type', 'duration')->latest('id')->get(),
            'addons' => MasterItem::query()->where('type', 'addon')->latest('id')->get(),
        ]);
    }

    public function storeMasterItem(Request $request): RedirectResponse
    {
        $this->authorizeOwnerAdmin();

        $validated = $request->validate([
            'type' => ['required', 'in:service,duration,addon'],
            'name' => ['required', 'string', 'max:120'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'is_percentage_modifier' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        MasterItem::query()->create([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'base_price' => $validated['base_price'],
            'unit' => $validated['unit'] ?? null,
            'is_percentage_modifier' => (bool) ($validated['is_percentage_modifier'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'Master item berhasil ditambahkan.');
    }

    public function tracking(Request $request): View
    {
        $boardDate = $request->query('date', now()->toDateString());

        $statuses = ['antrean', 'proses_cuci', 'proses_setrika', 'selesai', 'diambil'];
        $transitions = [
            'antrean' => ['proses_cuci'],
            'proses_cuci' => ['proses_setrika', 'selesai'],
            'proses_setrika' => ['selesai'],
            'selesai' => ['diambil'],
            'diambil' => [],
        ];
        $columns = [];

        foreach ($statuses as $status) {
            $columns[$status] = Transaction::query()
                ->with(['customer:id,name,phone', 'paymentOption:id,type,label,bank_name,account_name,account_number,qris_image_path,is_active'])
                ->where('status', $status)
                ->whereDate('created_at', $boardDate)
                ->latest('id')
                ->limit(30)
                ->get();

            $columns[$status]->each(function (Transaction $item): void {
                $item->setAttribute('invoice_url', $this->makeInvoiceUrl($item));
                $item->setAttribute('invoice_pdf_url', $this->makeInvoicePdfUrl($item));
            });
        }

        $paymentOptions = PaymentOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        $statusLabels = [
            'antrean' => 'Antrean',
            'proses_cuci' => 'Proses Cuci',
            'proses_setrika' => 'Proses Setrika',
            'selesai' => 'Selesai',
            'diambil' => 'Sudah Diambil',
        ];

        $paymentStatusLabels = [
            'unpaid' => 'Belum Bayar',
            'partial' => 'DP / Partial',
            'paid' => 'Lunas',
        ];

        return view('orders.tracking', [
            'columns' => $columns,
            'boardDate' => $boardDate,
            'statuses' => $statuses,
            'transitions' => $transitions,
            'statusLabels' => $statusLabels,
            'paymentStatusLabels' => $paymentStatusLabels,
            'paymentOptions' => $paymentOptions,
            'waTemplateByStatus' => [
                'antrean' => 'order_created',
                'proses_cuci' => 'order_created',
                'proses_setrika' => 'order_created',
                'selesai' => 'order_ready',
                'diambil' => 'order_picked_up',
            ],
        ]);
    }

    public function orderDetail(Transaction $transaction): View
    {
        $transaction->load([
            'customer',
            'cashier:id,name,email',
            'voucher',
            'paymentOption',
            'details',
            'logs.user:id,name,email',
        ]);

        return view('orders.show', [
            'order' => $transaction,
            'waPreview' => $this->buildWaPreviewSafe($transaction, $this->resolveWaTemplateKeyByStatus($transaction->status)),
            'invoiceUrl' => $this->makeInvoiceUrl($transaction),
            'invoicePdfUrl' => $this->makeInvoicePdfUrl($transaction),
        ]);
    }

    public function updateOrderCustomer(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($this->isCashierLockedForCompletedPickup($transaction)) {
            return back()->withErrors(['tracking' => 'Order sudah diambil. Koreksi customer hanya bisa dilakukan owner/admin.']);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = Customer::query()->updateOrCreate(
            ['phone' => $validated['customer_phone']],
            [
                'name' => $validated['customer_name'],
                'address' => $validated['customer_address'] ?? null,
            ]
        );

        $transaction->customer_id = $customer->id;
        $transaction->save();

        TransactionLog::query()->create([
            'transaction_id' => $transaction->id,
            'user_id' => (int) auth()->id(),
            'action_type' => 'updated_customer_data',
            'description' => 'Data customer order diperbarui dari halaman detail order.',
        ]);

        return back()->with('status', 'Data customer berhasil diperbarui.');
    }

    public function removeOrderCustomer(Transaction $transaction): RedirectResponse
    {
        if ($this->isCashierLockedForCompletedPickup($transaction)) {
            return back()->withErrors(['tracking' => 'Order sudah diambil. Koreksi customer hanya bisa dilakukan owner/admin.']);
        }

        $transaction->customer_id = null;
        $transaction->save();

        TransactionLog::query()->create([
            'transaction_id' => $transaction->id,
            'user_id' => (int) auth()->id(),
            'action_type' => 'removed_customer_data',
            'description' => 'Data customer dilepas dari order lewat halaman detail order.',
        ]);

        return back()->with('status', 'Data customer pada order berhasil dihapus.');
    }

    public function invoice(Transaction $transaction): View
    {
        $transaction->load([
            'customer',
            'cashier:id,name,email',
            'voucher',
            'paymentOption',
            'details',
        ]);

        $laundryProfile = $this->resolveLaundryProfile();

        return view('orders.invoice', [
            'order' => $transaction,
            'invoicePdfUrl' => $this->makeInvoicePdfUrl($transaction),
            'progressUrl' => $this->makeProgressUrl($transaction),
            'laundryProfile' => $laundryProfile,
            'laundryLogoDataUri' => $this->resolveLaundryLogoDataUri($laundryProfile),
        ]);
    }

    public function progress(Transaction $transaction): View
    {
        $transaction->load([
            'customer',
            'paymentOption',
        ]);

        $laundryProfile = $this->resolveLaundryProfile();

        return view('orders.progress', [
            'order' => $transaction,
            'invoiceUrl' => $this->makeInvoiceUrl($transaction),
            'laundryProfile' => $laundryProfile,
            'laundryLogoDataUri' => $this->resolveLaundryLogoDataUri($laundryProfile),
        ]);
    }

    public function downloadInvoicePdf(Transaction $transaction): Response
    {
        $transaction->load([
            'customer',
            'cashier:id,name,email',
            'voucher',
            'paymentOption',
            'details',
        ]);

        $laundryProfile = $this->resolveLaundryProfile();

        $html = view('orders.invoice-pdf', [
            'order' => $transaction,
            'laundryProfile' => $laundryProfile,
            'laundryLogoDataUri' => $this->resolveLaundryLogoDataUri($laundryProfile),
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        $filename = 'nota-'.$transaction->receipt_number.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function sendOrderWa(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'template_key' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $templateKey = $validated['template_key'] ?? $this->resolveWaTemplateKeyByStatus($transaction->status);

        try {
            $wa = $this->waMessageBuilderService->buildFromTransaction(
                $transaction,
                $templateKey,
                $validated['phone'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['tracking' => $exception->getMessage()]);
        }

        $message = $wa['message'];
        $invoiceUrl = $this->makeInvoiceUrl($transaction);

        $message .= "\n\nLink nota: ".$invoiceUrl;

        if (! empty($transaction->completion_photo_path) && in_array($templateKey, ['order_ready', 'order_picked_up'], true)) {
            $photoUrl = route('media.public', ['path' => $transaction->completion_photo_path], true);
            $message .= "\n\nBukti foto: ".$photoUrl;
        }

        $waUrl = 'https://wa.me/'.$wa['phone'].'?text='.rawurlencode($message);

        return redirect()->away($waUrl);
    }

    public function advanceTrackingStatus(Request $request, Transaction $transaction): RedirectResponse
    {
        $nextStatus = $this->nextStatus($transaction->status);

        if (! $nextStatus) {
            return back()->with('status', 'Status sudah di tahap akhir.');
        }

        $preCheck = $this->applyTrackingBusinessRules($request, $transaction, $nextStatus);

        if ($preCheck !== true) {
            return back()->withErrors(['tracking' => $preCheck]);
        }

        try {
            $this->orderWorkflowService->updateStatus(
                $transaction,
                $nextStatus,
                (int) auth()->id(),
                'Status diperbarui dari tracking board web.'
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['tracking' => $exception->getMessage()]);
        }

        return back()->with('status', 'Status order berhasil diperbarui ke '.$nextStatus.'.');
    }

    public function setTrackingStatus(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:antrean,proses_cuci,proses_setrika,selesai,diambil'],
        ]);

        $preCheck = $this->applyTrackingBusinessRules($request, $transaction, $validated['status']);

        if ($preCheck !== true) {
            return back()->withErrors(['tracking' => $preCheck]);
        }

        try {
            $this->orderWorkflowService->updateStatus(
                $transaction,
                $validated['status'],
                (int) auth()->id(),
                'Status diperbarui dari tracking board web.'
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['tracking' => $exception->getMessage()]);
        }

        return back()->with('status', 'Status order berhasil diperbarui ke '.$validated['status'].'.');
    }

    public function updateTrackingPayment(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:unpaid,paid,partial'],
            'payment_option_id' => ['nullable', 'integer', 'exists:payment_options,id'],
            'payment_note' => ['nullable', 'string', 'max:255'],
            'payment_proof' => ['nullable', File::image()->types(self::ALLOWED_IMAGE_EXTENSIONS)->max(4096)],
        ]);

        $selectedPaymentOption = null;
        if (! empty($validated['payment_option_id'])) {
            $selectedPaymentOption = PaymentOption::query()->find($validated['payment_option_id']);
        }

        if (in_array($validated['payment_status'], ['paid', 'partial'], true) && empty($validated['payment_option_id'])) {
            return back()->withErrors(['tracking' => 'Pilih metode pembayaran jika status pembayaran Paid/Partial.']);
        }

        $requiresProof =
            $selectedPaymentOption !== null
            && in_array($selectedPaymentOption->type, ['transfer', 'qris'], true)
            && in_array($validated['payment_status'], ['paid', 'partial'], true);

        if ($requiresProof && ! $request->hasFile('payment_proof') && empty($transaction->payment_proof_path)) {
            return back()->withErrors(['tracking' => 'Metode transfer/QRIS wajib upload bukti pembayaran.']);
        }

        if ($request->hasFile('payment_proof')) {
            $paymentProof = $request->file('payment_proof');

            if (! $paymentProof instanceof UploadedFile || ! $this->isSafeImageUpload($paymentProof)) {
                return back()->withErrors(['tracking' => 'Bukti pembayaran tidak valid. Gunakan JPG/PNG/WEBP maksimal 4MB.'])->withInput();
            }

            if (! empty($transaction->payment_proof_path)) {
                Storage::disk('public')->delete($transaction->payment_proof_path);
            }

            $transaction->payment_proof_path = $this->storePublicImage($paymentProof, 'payment-proofs');
        }

        if ($validated['payment_status'] === 'unpaid') {
            $validated['payment_option_id'] = null;

            if (! empty($transaction->payment_proof_path)) {
                Storage::disk('public')->delete($transaction->payment_proof_path);
                $transaction->payment_proof_path = null;
            }
        }

        if (
            $selectedPaymentOption !== null
            && $selectedPaymentOption->type === 'cash'
            && ! empty($transaction->payment_proof_path)
        ) {
            Storage::disk('public')->delete($transaction->payment_proof_path);
            $transaction->payment_proof_path = null;
        }

        $old = $transaction->payment_status;
        $paymentChanged = false;

        if ($old !== $validated['payment_status']) {
            $transaction->payment_status = $validated['payment_status'];
            $paymentChanged = true;
        }

        if (array_key_exists('payment_option_id', $validated)) {
            $transaction->payment_option_id = $validated['payment_option_id'] ?? null;
            $paymentChanged = true;
        }

        if (! empty($validated['payment_note'])) {
            $transaction->payment_note = $validated['payment_note'];
            $paymentChanged = true;
        }

        if ($paymentChanged) {
            $transaction->save();

            $paymentOption = $transaction->paymentOption?->label ?? 'tanpa metode';

            TransactionLog::query()->create([
                'transaction_id' => $transaction->id,
                'user_id' => (int) auth()->id(),
                'action_type' => 'updated_payment_to_'.$validated['payment_status'],
                'description' => 'Payment diupdate ke '.$validated['payment_status'].' via '.$paymentOption.'.',
            ]);
        }

        return back()->with('status', 'Payment status berhasil diperbarui.');
    }

    public function laundryProfileSettings(): View
    {
        $this->authorizeOwner();

        return view('settings.laundry-profile', [
            'profile' => $this->resolveLaundryProfile(),
        ]);
    }

    public function updateLaundryProfileSettings(Request $request): RedirectResponse
    {
        $this->authorizeOwner();

        $validated = $request->validate([
            'laundry_name' => ['required', 'string', 'max:150'],
            'logo_image' => ['nullable', File::image()->types(self::ALLOWED_IMAGE_EXTENSIONS)->max(4096)],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'invoice_footer_note' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = $this->resolveLaundryProfile();

        if ($request->hasFile('logo_image')) {
            $logoImage = $request->file('logo_image');

            if (! $logoImage instanceof UploadedFile || ! $this->isSafeImageUpload($logoImage)) {
                return back()->withErrors(['logo_image' => 'Logo tidak valid. Gunakan JPG/PNG/WEBP maksimal 4MB.'])->withInput();
            }

            if (! empty($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $validated['logo_path'] = $this->storePublicImage($logoImage, 'laundry-profiles');
        }

        unset($validated['logo_image']);

        $profile->update($validated);

        return back()->with('status', 'Profil laundry berhasil diperbarui.');
    }

    public function paymentOptions(): View
    {
        $this->authorizeOwnerAdmin();

        return view('settings.payment-options', [
            'paymentOptions' => PaymentOption::query()->orderBy('sort_order')->orderBy('label')->get(),
        ]);
    }

    public function storePaymentOption(Request $request): RedirectResponse
    {
        $this->authorizeOwnerAdmin();

        $validated = $request->validate([
            'type' => ['required', 'in:cash,transfer,qris'],
            'label' => ['required', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'qris_image' => ['nullable', File::image()->types(self::ALLOWED_IMAGE_EXTENSIONS)->max(4096)],
        ]);

        $qrisPath = null;
        if ($request->hasFile('qris_image')) {
            $qrisImage = $request->file('qris_image');

            if (! $qrisImage instanceof UploadedFile || ! $this->isSafeImageUpload($qrisImage)) {
                return back()->withErrors(['qris_image' => 'File QRIS tidak valid. Gunakan JPG/PNG/WEBP maksimal 4MB.'])->withInput();
            }

            $qrisPath = $this->storePublicImage($qrisImage, 'payment-qris');
        }

        PaymentOption::query()->create([
            'type' => $validated['type'],
            'label' => $validated['label'],
            'bank_name' => $validated['bank_name'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 100),
            'qris_image_path' => $qrisPath,
        ]);

        return back()->with('status', 'Opsi pembayaran berhasil ditambahkan.');
    }

    public function updatePaymentOption(Request $request, PaymentOption $paymentOption): RedirectResponse
    {
        $this->authorizeOwnerAdmin();

        $validated = $request->validate([
            'type' => ['required', 'in:cash,transfer,qris'],
            'label' => ['required', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'qris_image' => ['nullable', File::image()->types(self::ALLOWED_IMAGE_EXTENSIONS)->max(4096)],
        ]);

        $qrisPath = $paymentOption->qris_image_path;
        if ($request->hasFile('qris_image')) {
            $qrisImage = $request->file('qris_image');

            if (! $qrisImage instanceof UploadedFile || ! $this->isSafeImageUpload($qrisImage)) {
                return back()->withErrors(['qris_image' => 'File QRIS tidak valid. Gunakan JPG/PNG/WEBP maksimal 4MB.'])->withInput();
            }

            if (! empty($qrisPath)) {
                Storage::disk('public')->delete($qrisPath);
            }
            $qrisPath = $this->storePublicImage($qrisImage, 'payment-qris');
        }

        $paymentOption->update([
            'type' => $validated['type'],
            'label' => $validated['label'],
            'bank_name' => $validated['bank_name'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 100),
            'qris_image_path' => $qrisPath,
        ]);

        return back()->with('status', 'Opsi pembayaran berhasil diperbarui.');
    }

    public function destroyPaymentOption(PaymentOption $paymentOption): RedirectResponse
    {
        $this->authorizeOwnerAdmin();

        $usedCount = Transaction::query()
            ->where('payment_option_id', $paymentOption->id)
            ->count();

        if ($usedCount > 0) {
            $paymentOption->is_active = false;
            $paymentOption->save();

            return back()->with('status', 'Opsi pembayaran dipakai di transaksi, jadi dinonaktifkan (tidak dihapus).');
        }

        if (! empty($paymentOption->qris_image_path)) {
            Storage::disk('public')->delete($paymentOption->qris_image_path);
        }

        $paymentOption->delete();

        return back()->with('status', 'Opsi pembayaran berhasil dihapus.');
    }

    public function userManagement(): View
    {
        $this->authorizeOwner();

        return view('settings.users', [
            'users' => User::query()->orderBy('role')->orderBy('name')->get(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeOwner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'lowercase', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:owner,admin,kasir'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'username' => Str::lower($validated['username']),
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'password' => $validated['password'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'User baru berhasil dibuat.');
    }

    public function updateUserAccess(Request $request, User $user): RedirectResponse
    {
        $this->authorizeOwner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'lowercase', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/', 'unique:users,username,'.$user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:owner,admin,kasir'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $currentUserId = (int) auth()->id();
        $targetUserId = (int) $user->id;
        $nextRole = $validated['role'];
        $nextActive = (bool) ($validated['is_active'] ?? false);

        if ($targetUserId === $currentUserId && $nextRole !== 'owner') {
            return back()->withErrors(['users' => 'Owner yang sedang login tidak boleh menurunkan role dirinya sendiri.']);
        }

        if ($targetUserId === $currentUserId && $nextActive === false) {
            return back()->withErrors(['users' => 'Owner yang sedang login tidak boleh menonaktifkan dirinya sendiri.']);
        }

        $otherOwners = User::query()
            ->where('role', 'owner')
            ->where('id', '!=', $user->id)
            ->count();

        if ($user->role === 'owner' && $nextRole !== 'owner' && $otherOwners === 0) {
            return back()->withErrors(['users' => 'Tidak bisa mengubah owner terakhir menjadi role lain.']);
        }

        if ($user->role === 'owner' && $nextActive === false && $otherOwners === 0) {
            return back()->withErrors(['users' => 'Tidak bisa menonaktifkan owner terakhir.']);
        }

        $payload = [
            'name' => $validated['name'],
            'username' => Str::lower($validated['username']),
            'email' => strtolower($validated['email']),
            'role' => $nextRole,
            'is_active' => $nextActive,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return back()->with('status', 'Akses user berhasil diperbarui.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        $this->authorizeOwner();

        $currentUserId = (int) auth()->id();
        $targetUserId = (int) $user->id;

        if ($targetUserId === $currentUserId) {
            return back()->withErrors(['users' => 'Owner yang sedang login tidak boleh menghapus dirinya sendiri.']);
        }

        $otherOwners = User::query()
            ->where('role', 'owner')
            ->where('id', '!=', $user->id)
            ->count();

        if ($user->role === 'owner' && $otherOwners === 0) {
            return back()->withErrors(['users' => 'Tidak bisa menghapus owner terakhir.']);
        }

        $hasTransactionHistory = Transaction::query()->where('cashier_id', $user->id)->exists();

        if ($hasTransactionHistory) {
            return back()->withErrors(['users' => 'User sudah dipakai di transaksi. Nonaktifkan saja jika tidak dipakai lagi.']);
        }

        $user->delete();

        return back()->with('status', 'User berhasil dihapus.');
    }

    public function reports(Request $request): View
    {
        $this->authorizeOwnerAdmin();

        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $query = Transaction::query()->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        $summary = [
            'total_orders' => (clone $query)->count(),
            'gross_total' => (float) (clone $query)->sum('total_amount'),
            'discount_total' => (float) (clone $query)->sum('discount_amount'),
            'net_total' => (float) (clone $query)->sum('final_amount'),
        ];

        $transactions = (clone $query)->with('customer:id,name,phone')->latest('id')->limit(200)->get();

        return view('reports.financial', [
            'summary' => $summary,
            'transactions' => $transactions,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'paymentStatus' => $request->query('payment_status'),
        ]);
    }

    public function exportFinancialExcel(Request $request): StreamedResponse
    {
        $this->authorizeOwnerAdmin();

        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $paymentStatus = $request->query('payment_status');

        $query = Transaction::query()
            ->with('customer:id,name,phone')
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->latest('id');

        if (! empty($paymentStatus)) {
            $query->where('payment_status', $paymentStatus);
        }

        $rows = $query->get();
        $filename = 'financial-report-'.$dateFrom.'-to-'.$dateTo.'.xls';

        return response()->streamDownload(function () use ($rows): void {
            echo "<table border='1'>";
            echo '<tr>'
                .'<th>Receipt Number</th>'
                .'<th>Customer Name</th>'
                .'<th>Customer Phone</th>'
                .'<th>Status</th>'
                .'<th>Payment Status</th>'
                .'<th>Total Amount</th>'
                .'<th>Discount Amount</th>'
                .'<th>Final Amount</th>'
                .'<th>Created At</th>'
                .'</tr>';

            foreach ($rows as $trx) {
                echo '<tr>';
                echo '<td>'.e($trx->receipt_number).'</td>';
                echo '<td>'.e((string) ($trx->customer?->name ?? '')).'</td>';
                echo '<td>'.e((string) ($trx->customer?->phone ?? '')).'</td>';
                echo '<td>'.e($trx->status).'</td>';
                echo '<td>'.e($trx->payment_status).'</td>';
                echo '<td>'.(float) $trx->total_amount.'</td>';
                echo '<td>'.(float) $trx->discount_amount.'</td>';
                echo '<td>'.(float) $trx->final_amount.'</td>';
                echo '<td>'.e((string) $trx->created_at).'</td>';
                echo '</tr>';
            }

            echo '</table>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function waTemplates(): View
    {
        $this->authorizeOwnerAdmin();

        return view('settings.wa-templates', [
            'templates' => WaTemplate::query()->orderBy('template_key')->get(),
        ]);
    }

    public function updateWaTemplates(Request $request): RedirectResponse
    {
        $this->authorizeOwnerAdmin();

        $validated = $request->validate([
            'templates' => ['required', 'array', 'min:1'],
            'templates.*.id' => ['required', 'integer', 'exists:wa_templates,id'],
            'templates.*.title' => ['required', 'string', 'max:150'],
            'templates.*.content' => ['required', 'string', 'max:2000'],
            'templates.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['templates'] as $item) {
            WaTemplate::query()->where('id', $item['id'])->update([
                'title' => $item['title'],
                'content' => $item['content'],
                'is_active' => (bool) ($item['is_active'] ?? false),
            ]);
        }

        return back()->with('status', 'Template WA berhasil diperbarui.');
    }

    public function showPublicMedia(string $path): BinaryFileResponse
    {
        $normalizedPath = ltrim($path, '/');

        abort_if(str_contains($normalizedPath, '..'), 404);
        abort_unless((bool) preg_match('/^[a-zA-Z0-9_\/\.-]+$/', $normalizedPath), 404);

        $allowedDirectory = collect(self::ALLOWED_PUBLIC_MEDIA_DIRECTORIES)
            ->contains(fn (string $prefix): bool => str_starts_with($normalizedPath, $prefix));

        abort_unless($allowedDirectory, 404);

        $extension = strtolower((string) pathinfo($normalizedPath, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true), 404);

        abort_unless(Storage::disk('public')->exists($normalizedPath), 404);

        $mimeType = strtolower((string) (Storage::disk('public')->mimeType($normalizedPath) ?: ''));
        abort_unless(isset(self::IMAGE_MIME_TO_EXTENSION[$mimeType]), 404);
        abort_unless(self::IMAGE_MIME_TO_EXTENSION[$mimeType] === $extension || ($mimeType === 'image/jpeg' && $extension === 'jpeg'), 404);

        return response()->file(Storage::disk('public')->path($normalizedPath), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeOwnerAdmin(): void
    {
        $user = auth()->user();

        abort_if(! $user, 403);
        abort_if($user->is_active === false, 403);
        abort_if(! in_array($user->role, ['owner', 'admin'], true), 403);
    }

    private function authorizeOwner(): void
    {
        $user = auth()->user();

        abort_if(! $user, 403);
        abort_if($user->is_active === false, 403);
        abort_if($user->role !== 'owner', 403);
    }

    private function generateReceiptNumber(): string
    {
        $datePart = now()->format('Ymd');
        $prefix = 'TRX-'.$datePart.'-';

        $lastToday = Transaction::query()
            ->where('receipt_number', 'like', $prefix.'%')
            ->latest('id')
            ->value('receipt_number');

        if (! $lastToday) {
            return $prefix.'001';
        }

        $lastSequence = (int) substr($lastToday, -3);

        return $prefix.str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    private function nextStatus(string $status): ?string
    {
        return match ($status) {
            'antrean' => 'proses_cuci',
            'proses_cuci' => 'proses_setrika',
            'proses_setrika' => 'selesai',
            'selesai' => 'diambil',
            default => null,
        };
    }

    private function applyTrackingBusinessRules(Request $request, Transaction $transaction, string $targetStatus): bool|string
    {
        if ($targetStatus === 'selesai') {
            if ($request->hasFile('completion_photo')) {
                $completionPhoto = $request->file('completion_photo');

                if (! $completionPhoto instanceof UploadedFile || ! $this->isSafeImageUpload($completionPhoto)) {
                    return 'Foto bukti tidak valid. Gunakan JPG/PNG/WEBP maksimal 4MB.';
                }

                $path = $this->storePublicImage($completionPhoto, 'completion-proofs');
                $transaction->completion_photo_path = $path;
                $transaction->save();
            }

            if (empty($transaction->completion_photo_path)) {
                return 'Wajib upload foto bukti saat order dinyatakan selesai.';
            }
        }

        if ($targetStatus === 'diambil' && $transaction->payment_status !== 'paid') {
            return 'Order tidak bisa diambil sebelum status pembayaran Lunas (Paid).';
        }

        if ($targetStatus === 'diambil') {
            if (empty($transaction->payment_option_id)) {
                return 'Sebelum status diambil, kasir wajib memilih metode pembayaran.';
            }

            $paymentOption = $transaction->paymentOption()->first();
            if ($paymentOption === null) {
                return 'Metode pembayaran tidak valid. Pilih ulang metode pembayaran.';
            }

            if (in_array($paymentOption->type, ['transfer', 'qris'], true) && empty($transaction->payment_proof_path)) {
                return 'Untuk transfer/QRIS, upload bukti pembayaran sebelum order diambil.';
            }
        }

        return true;
    }

    private function resolveWaTemplateKeyByStatus(string $status): string
    {
        return match ($status) {
            'selesai' => 'order_ready',
            'diambil' => 'order_picked_up',
            default => 'order_created',
        };
    }

    private function buildWaPreviewSafe(Transaction $transaction, string $templateKey): ?array
    {
        try {
            $wa = $this->waMessageBuilderService->buildFromTransaction($transaction, $templateKey);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (! empty($transaction->completion_photo_path) && in_array($templateKey, ['order_ready', 'order_picked_up'], true)) {
            $photoUrl = route('media.public', ['path' => $transaction->completion_photo_path], true);
            $wa['message'] .= "\n\nBukti foto: ".$photoUrl;
        }

        $wa['message'] .= "\n\nLink nota: ".$this->makeInvoiceUrl($transaction);
        $wa['wa_url'] = 'https://wa.me/'.$wa['phone'].'?text='.rawurlencode($wa['message']);

        return $wa;
    }

    private function makeInvoiceUrl(Transaction $transaction): string
    {
        return URL::signedRoute('orders.invoice', ['transaction' => $transaction->id]);
    }

    private function makeInvoicePdfUrl(Transaction $transaction): string
    {
        return URL::signedRoute('orders.invoice.download', ['transaction' => $transaction->id]);
    }

    private function makeProgressUrl(Transaction $transaction): string
    {
        return URL::signedRoute('orders.progress', ['transaction' => $transaction->id]);
    }

    private function resolveLaundryProfile(): LaundryProfile
    {
        return LaundryProfile::query()->firstOrCreate(
            ['id' => 1],
            [
                'laundry_name' => config('app.name', 'SI Laundry'),
            ]
        );
    }

    private function resolveLaundryLogoDataUri(LaundryProfile $laundryProfile): ?string
    {
        $logoPath = $laundryProfile->logo_path;

        if (empty($logoPath) || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $extension = strtolower((string) pathinfo($logoPath, PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
        $mimeType = strtolower($mimeType);

        if (! in_array($mimeType, self::ALLOWED_IMAGE_MIME_TYPES, true)) {
            return null;
        }

        $contents = Storage::disk('public')->get($logoPath);

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function isSafeImageUpload(UploadedFile $file): bool
    {
        return $this->inspectImageUpload($file) !== null;
    }

    private function isCashierLockedForCompletedPickup(Transaction $transaction): bool
    {
        $user = auth()->user();

        return $transaction->status === 'diambil' && $user?->role === 'kasir';
    }

    private function storePublicImage(UploadedFile $file, string $directory): string
    {
        $inspected = $this->inspectImageUpload($file);

        if ($inspected === null) {
            throw new InvalidArgumentException('File image tidak valid.');
        }

        $filename = Str::uuid()->toString().'.'.$inspected['extension'];
        $storedPath = $file->storeAs($directory, $filename, 'public');

        if ($storedPath === false) {
            throw new RuntimeException('Gagal menyimpan file image.');
        }

        return $storedPath;
    }

    private function inspectImageUpload(UploadedFile $file): ?array
    {
        if (! $file->isValid()) {
            return null;
        }

        if ((int) $file->getSize() > self::MAX_IMAGE_SIZE_BYTES) {
            return null;
        }

        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_file($realPath)) {
            return null;
        }

        $binary = file_get_contents($realPath);
        if ($binary === false) {
            return null;
        }

        $imageInfo = @getimagesizefromstring($binary);
        $imageMime = strtolower((string) ($imageInfo['mime'] ?? ''));

        if (! isset(self::IMAGE_MIME_TO_EXTENSION[$imageMime])) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $finfoMime = strtolower((string) $finfo->file($realPath));

        if (! isset(self::IMAGE_MIME_TO_EXTENSION[$finfoMime])) {
            return null;
        }

        if ($finfoMime !== $imageMime) {
            return null;
        }

        return [
            'mime' => $imageMime,
            'extension' => self::IMAGE_MIME_TO_EXTENSION[$imageMime],
        ];
    }
}
