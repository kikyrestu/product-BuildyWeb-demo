<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionLog;
use App\Services\OrderWorkflowService;
use App\Services\TransactionCalculatorService;
use App\Services\VoucherValidationService;
use App\Services\WaMessageBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(
        private readonly TransactionCalculatorService $calculatorService,
        private readonly VoucherValidationService $voucherValidationService,
        private readonly OrderWorkflowService $orderWorkflowService,
        private readonly WaMessageBuilderService $waMessageBuilderService
    ) {
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load([
            'customer',
            'cashier:id,name,email',
            'voucher',
            'details',
            'logs.user:id,name,email',
        ]);

        return response()->json([
            'data' => $transaction,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:antrean,proses_cuci,proses_setrika,selesai,diambil'],
            'payment_status' => ['nullable', 'in:unpaid,paid,partial'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Transaction::query()
            ->with(['customer'])
            ->latest('id');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['payment_status'])) {
            $query->where('payment_status', $validated['payment_status']);
        }

        if (! empty($validated['q'])) {
            $keyword = $validated['q'];

            $query->where(function ($builder) use ($keyword): void {
                $builder->where('receipt_number', 'like', '%'.$keyword.'%')
                    ->orWhereHas('customer', function ($customerQuery) use ($keyword): void {
                        $customerQuery->where('name', 'like', '%'.$keyword.'%')
                            ->orWhere('phone', 'like', '%'.$keyword.'%');
                    });
            });
        }

        $perPage = (int) ($validated['per_page'] ?? 20);
        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function board(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => ['nullable', 'in:unpaid,paid,partial'],
            'q' => ['nullable', 'string', 'max:100'],
            'limit_per_status' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $statuses = ['antrean', 'proses_cuci', 'proses_setrika', 'selesai', 'diambil'];
        $limit = (int) ($validated['limit_per_status'] ?? 20);
        $result = [];

        foreach ($statuses as $status) {
            $query = Transaction::query()
                ->with('customer')
                ->where('status', $status)
                ->latest('id');

            if (! empty($validated['payment_status'])) {
                $query->where('payment_status', $validated['payment_status']);
            }

            if (! empty($validated['q'])) {
                $keyword = $validated['q'];

                $query->where(function ($builder) use ($keyword): void {
                    $builder->where('receipt_number', 'like', '%'.$keyword.'%')
                        ->orWhereHas('customer', function ($customerQuery) use ($keyword): void {
                            $customerQuery->where('name', 'like', '%'.$keyword.'%')
                                ->orWhere('phone', 'like', '%'.$keyword.'%');
                        });
                });
            }

            $result[] = [
                'status' => $status,
                'items' => $query->limit($limit)->get(),
            ];
        }

        return response()->json([
            'data' => $result,
        ]);
    }

    public function statusSummary(): JsonResponse
    {
        $statuses = ['antrean', 'proses_cuci', 'proses_setrika', 'selesai', 'diambil'];

        $counts = Transaction::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [];

        foreach ($statuses as $status) {
            $summary[] = [
                'status' => $status,
                'total' => (int) ($counts[$status] ?? 0),
            ];
        }

        return response()->json([
            'data' => $summary,
        ]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        try {
            $calculated = $this->calculatorService->calculate($validated['items']);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $voucherData = [
            'code' => null,
            'discount_amount' => 0.0,
        ];

        if (! empty($validated['voucher_code'])) {
            $voucherResult = $this->voucherValidationService->validateCode(
                $validated['voucher_code'],
                $calculated['total_amount']
            );

            $voucherData = [
                'code' => $validated['voucher_code'],
                'discount_amount' => $voucherResult['is_valid']
                    ? $voucherResult['discount_amount']
                    : 0.0,
            ];
        }

        $finalAmount = max(0, $calculated['total_amount'] - $voucherData['discount_amount']);

        return response()->json([
            'data' => [
                'total_amount' => $calculated['total_amount'],
                'discount_amount' => round($voucherData['discount_amount'], 2),
                'final_amount' => round($finalAmount, 2),
                'items' => $calculated['lines'],
            ],
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request, true);
        $actorId = (int) ($request->user()?->id ?? ($validated['cashier_id'] ?? 0));

        if ($actorId <= 0) {
            return response()->json([
                'message' => 'Cashier is required.',
            ], 422);
        }

        try {
            $calculated = $this->calculatorService->calculate($validated['items']);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $voucher = null;
        $discountAmount = 0.0;

        if (! empty($validated['voucher_code'])) {
            $voucherResult = $this->voucherValidationService->validateCode(
                $validated['voucher_code'],
                $calculated['total_amount']
            );

            if ($voucherResult['is_valid']) {
                $discountAmount = $voucherResult['discount_amount'];
                $voucher = $voucherResult['voucher'];
            }
        }

        $transaction = DB::transaction(function () use ($validated, $calculated, $discountAmount, $voucher, $actorId) {
            $createdTransaction = Transaction::query()->create([
                'receipt_number' => $this->generateReceiptNumber(),
                'customer_id' => $validated['customer_id'] ?? null,
            'cashier_id' => $actorId,
                'voucher_id' => $voucher?->id,
                'status' => 'antrean',
                'payment_status' => $validated['payment_status'] ?? 'unpaid',
                'total_amount' => $calculated['total_amount'],
                'discount_amount' => $discountAmount,
                'final_amount' => max(0, $calculated['total_amount'] - $discountAmount),
            ]);

            foreach ($calculated['lines'] as $line) {
                TransactionDetail::query()->create([
                    'transaction_id' => $createdTransaction->id,
                    'master_service_id' => $line['service_id'],
                    'qty' => $line['qty'],
                    'snapshot_data' => $line['snapshot_data'],
                ]);
            }

            TransactionLog::query()->create([
                'transaction_id' => $createdTransaction->id,
                'user_id' => $actorId,
                'action_type' => 'created_order',
                'description' => 'Order created via checkout endpoint.',
            ]);

            return $createdTransaction->load(['customer', 'details']);
        });

        return response()->json([
            'data' => [
                'id' => $transaction->id,
                'receipt_number' => $transaction->receipt_number,
                'status' => $transaction->status,
                'payment_status' => $transaction->payment_status,
                'total_amount' => (float) $transaction->total_amount,
                'discount_amount' => (float) $transaction->discount_amount,
                'final_amount' => (float) $transaction->final_amount,
                'details_count' => $transaction->details->count(),
            ],
        ], 201);
    }

    public function updateStatus(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:antrean,proses_cuci,proses_setrika,selesai,diambil'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $actorId = (int) ($request->user()?->id ?? ($validated['user_id'] ?? 0));

        if ($actorId <= 0) {
            return response()->json([
                'message' => 'User is required.',
            ], 422);
        }

        try {
            $updated = $this->orderWorkflowService->updateStatus(
                $transaction,
                $validated['status'],
                $actorId,
                $validated['note'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => [
                'id' => $updated->id,
                'receipt_number' => $updated->receipt_number,
                'status' => $updated->status,
                'updated_at' => $updated->updated_at,
            ],
        ]);
    }

    public function updatePaymentStatus(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:unpaid,paid,partial'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $actorId = (int) ($request->user()?->id ?? ($validated['user_id'] ?? 0));

        if ($actorId <= 0) {
            return response()->json([
                'message' => 'User is required.',
            ], 422);
        }

        $oldPaymentStatus = $transaction->payment_status;

        if ($oldPaymentStatus !== $validated['payment_status']) {
            $transaction->payment_status = $validated['payment_status'];
            $transaction->save();

            TransactionLog::query()->create([
                'transaction_id' => $transaction->id,
                'user_id' => $actorId,
                'action_type' => 'updated_payment_to_'.$validated['payment_status'],
                'description' => $validated['note']
                    ?? ('Payment status moved from '.$oldPaymentStatus.' to '.$validated['payment_status']),
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $transaction->id,
                'receipt_number' => $transaction->receipt_number,
                'payment_status' => $transaction->payment_status,
                'updated_at' => $transaction->updated_at,
            ],
        ]);
    }

    public function waPreview(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'template_key' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $result = $this->waMessageBuilderService->buildFromTransaction(
                $transaction,
                $validated['template_key'],
                $validated['phone'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $result,
        ]);
    }

    private function validatePayload(Request $request, bool $requireCheckoutFields = false): array
    {
        $rules = [
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'integer', 'min:1'],
            'items.*.qty' => ['required', 'numeric', 'min:0.1'],
            'items.*.duration_id' => ['nullable', 'integer', 'min:1'],
            'items.*.addon_ids' => ['nullable', 'array'],
            'items.*.addon_ids.*' => ['integer', 'min:1'],
            'voucher_code' => ['nullable', 'string', 'max:100'],
        ];

        if ($requireCheckoutFields) {
            $rules['cashier_id'] = ['nullable', 'integer', 'exists:users,id'];
            $rules['customer_id'] = ['nullable', 'integer', 'exists:customers,id'];
            $rules['payment_status'] = ['nullable', 'in:unpaid,paid,partial'];
        }

        return $request->validate($rules);
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
}
