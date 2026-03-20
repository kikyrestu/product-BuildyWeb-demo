<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->receipt_number }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <div class="mx-auto max-w-md px-4 py-6">
        <div class="mb-3 rounded-xl border border-slate-200 bg-white p-2 print:hidden">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <a href="{{ request()->fullUrl() }}" class="rounded-lg bg-slate-900 px-3 py-2 text-center font-semibold text-white">Lihat Nota</a>
                <a href="{{ $progressUrl }}" class="rounded-lg bg-sky-100 px-3 py-2 text-center font-semibold text-sky-700">Lihat Progress</a>
            </div>
        </div>

        <div class="mb-3 flex items-center justify-end gap-2 print:hidden">
            <a
                href="{{ $progressUrl }}"
                class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500 transition"
            >
                Lihat Progress
            </a>
            <a
                href="{{ $invoicePdfUrl }}"
                class="inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500 transition"
            >
                Download PDF
            </a>
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition"
                onclick="navigator.clipboard.writeText(window.location.href).then(function(){ alert('Link nota disalin'); }).catch(function(){ window.prompt('Copy link nota:', window.location.href); });"
            >
                Copy Link Nota
            </button>
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition"
                onclick="window.print()"
            >
                Print / Save PDF
            </button>
        </div>

        <div class="bw-card p-5">
            <div class="border-b border-slate-200 pb-4 flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">{{ $laundryProfile->laundry_name }}</h1>
                    <p class="text-xs text-slate-500 mt-1">Nota Laundry</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $order->receipt_number }}</p>
                </div>
                @if ($laundryLogoDataUri)
                    <img src="{{ $laundryLogoDataUri }}" alt="Logo {{ $laundryProfile->laundry_name }}" class="h-11 w-11 rounded-lg border border-slate-200 object-cover bg-white shrink-0">
                @endif
            </div>

            <div class="mt-3 text-xs text-slate-600 space-y-0.5">
                @if ($laundryProfile->owner_name)
                    <p>Owner: {{ $laundryProfile->owner_name }}</p>
                @endif
                @if ($laundryProfile->phone || $laundryProfile->whatsapp)
                    <p>
                        Kontak:
                        {{ $laundryProfile->phone ?: '-' }}
                        @if ($laundryProfile->whatsapp)
                            • WA {{ $laundryProfile->whatsapp }}
                        @endif
                    </p>
                @endif
                @if ($laundryProfile->address)
                    <p>{{ $laundryProfile->address }}</p>
                @endif
                @if ($laundryProfile->city || $laundryProfile->postal_code)
                    <p>{{ trim(($laundryProfile->city ?? '').' '.($laundryProfile->postal_code ?? '')) }}</p>
                @endif
            </div>

            <div class="mt-4 text-sm space-y-1">
                <p class="font-semibold text-slate-900">{{ $order->customer?->name ?? 'Pelanggan' }}</p>
                <p class="text-slate-600">{{ $order->customer?->phone ?? '-' }}</p>
                <p class="text-slate-600">Kasir: {{ $order->cashier?->name ?? '-' }}</p>
                <p class="text-slate-600">Tgl Masuk: {{ optional($order->created_at)->format('d-m-Y H:i') }}</p>
                <p class="text-slate-600">Status: {{ $order->status }}</p>
            </div>

            <div class="mt-5 border-t border-dashed border-slate-300 pt-4 text-sm">
                <h2 class="font-semibold text-slate-900 mb-2">Rincian Item</h2>
                @forelse ($order->details as $detail)
                    @php
                        $snapshot = is_array($detail->snapshot_data) ? $detail->snapshot_data : [];
                        $serviceName = $snapshot['service_name'] ?? ('Service #'.$detail->master_service_id);
                        $durationRaw = $snapshot['duration'] ?? null;
                        $durationName = null;
                        if (is_array($durationRaw)) {
                            $durationName = $durationRaw['name'] ?? collect($durationRaw)
                                ->filter(fn ($value) => is_scalar($value))
                                ->map(fn ($value) => (string) $value)
                                ->implode(' - ');
                        } elseif (is_scalar($durationRaw)) {
                            $durationName = (string) $durationRaw;
                        }

                        $addons = is_array($snapshot['addons'] ?? null) ? $snapshot['addons'] : [];
                        $addonNames = collect($addons)
                            ->map(function ($addon): ?string {
                                if (is_array($addon)) {
                                    $name = $addon['name'] ?? null;

                                    return is_scalar($name) ? (string) $name : null;
                                }

                                return is_scalar($addon) ? (string) $addon : null;
                            })
                            ->filter()
                            ->implode(', ');

                        $lineTotal = (float) ($snapshot['sub_total_calculated'] ?? 0);
                    @endphp
                    <div class="py-2 border-b border-slate-100">
                        <p class="font-medium text-slate-900">{{ $serviceName }} ({{ $detail->qty }})</p>
                        @if ($durationName)
                            <p class="text-xs text-slate-500">Durasi: {{ $durationName }}</p>
                        @endif
                        @if ($addonNames !== '')
                            <p class="text-xs text-slate-500">Addon: {{ $addonNames }}</p>
                        @endif
                        <p class="text-right font-semibold text-slate-800">Rp {{ number_format($lineTotal, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="text-slate-500">Tidak ada rincian item.</p>
                @endforelse
            </div>

            <div class="mt-4 border-t border-dashed border-slate-300 pt-4 text-sm space-y-1">
                <div class="flex items-center justify-between"><span>Subtotal</span><span>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between"><span>Diskon</span><span>Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between font-bold text-base text-slate-900"><span>Total</span><span>Rp {{ number_format((float) $order->final_amount, 0, ',', '.') }}</span></div>
            </div>

            @php
                $showPaymentInfo = in_array($order->status, ['selesai', 'diambil'], true);
            @endphp

            @if ($showPaymentInfo)
                <div class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">
                    <p>Pembayaran: {{ $order->payment_status }}</p>
                    <p>Metode: {{ $order->paymentOption?->label ?? '-' }}</p>
                    @if ($order->payment_note)
                        <p>Catatan: {{ $order->payment_note }}</p>
                    @endif
                </div>
            @endif

            @if ($order->completion_photo_path)
                <div class="mt-4">
                    <p class="text-sm font-semibold text-slate-900">Bukti Selesai</p>
                    <img src="{{ route('media.public', ['path' => $order->completion_photo_path]) }}" alt="Bukti selesai" class="mt-2 rounded-lg border border-slate-200">
                </div>
            @endif

            <div class="mt-5 text-center text-xs text-slate-500 border-t border-slate-200 pt-4">
                {{ $laundryProfile->invoice_footer_note ?: 'Terima kasih sudah menggunakan layanan kami.' }}
            </div>
        </div>
    </div>
</body>
</html>
