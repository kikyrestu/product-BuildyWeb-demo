<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Progress Cucian {{ $order->receipt_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @keyframes bw-status-pulse {
            0% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.45); }
            70% { box-shadow: 0 0 0 12px rgba(14, 165, 233, 0); }
            100% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0); }
        }

        .bw-progress-pulse {
            animation: bw-status-pulse 1.8s ease-out infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <div class="mx-auto max-w-md px-4 py-6">
        @php
            $progressSteps = [
                'antrean' => ['label' => 'Antrean', 'icon' => '1'],
                'proses_cuci' => ['label' => 'Cuci', 'icon' => '2'],
                'proses_setrika' => ['label' => 'Setrika', 'icon' => '3'],
                'selesai' => ['label' => 'Selesai', 'icon' => '4'],
                'diambil' => ['label' => 'Diambil', 'icon' => '5'],
            ];
            $statusLabelMap = [
                'antrean' => 'Antrean',
                'proses_cuci' => 'Proses Cuci',
                'proses_setrika' => 'Proses Setrika',
                'selesai' => 'Selesai',
                'diambil' => 'Sudah Diambil',
            ];
            $progressKeys = array_keys($progressSteps);
            $currentProgressIndex = array_search($order->status, $progressKeys, true);
            $currentProgressIndex = $currentProgressIndex === false ? 0 : $currentProgressIndex;
            $progressPercent = (int) round(($currentProgressIndex / (max(count($progressKeys) - 1, 1))) * 100);
            $currentStatusLabel = $statusLabelMap[$order->status] ?? ucfirst((string) $order->status);
        @endphp

        <div class="mb-3 rounded-xl border border-slate-200 bg-white p-2">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <a href="{{ request()->fullUrl() }}" class="rounded-lg bg-sky-600 px-3 py-2 text-center font-semibold text-white">Lihat Progress</a>
                <a href="{{ $invoiceUrl }}" class="rounded-lg bg-slate-100 px-3 py-2 text-center font-semibold text-slate-700">Lihat Nota</a>
            </div>
        </div>

        <div class="bw-card p-5">
            <div class="border-b border-slate-200 pb-4 flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">{{ $laundryProfile->laundry_name }}</h1>
                    <p class="text-xs text-slate-500 mt-1">Progress Cucian</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $order->receipt_number }}</p>
                </div>
                @if ($laundryLogoDataUri)
                    <img src="{{ $laundryLogoDataUri }}" alt="Logo {{ $laundryProfile->laundry_name }}" class="h-11 w-11 rounded-lg border border-slate-200 object-cover bg-white shrink-0">
                @endif
            </div>

            <div class="mt-3 text-sm text-slate-700 space-y-1">
                <p><span class="text-slate-500">Pelanggan:</span> {{ $order->customer?->name ?? '-' }}</p>
                <p><span class="text-slate-500">Status saat ini:</span> <span class="font-semibold text-sky-700">{{ $currentStatusLabel }}</span></p>
                <p><span class="text-slate-500">Progress:</span> <span class="font-semibold">{{ $progressPercent }}%</span></p>
            </div>

            <div class="mt-4 rounded-xl border border-sky-100 bg-gradient-to-r from-sky-50 to-cyan-50 p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Progress Laundry</p>
                    <span class="text-xs font-semibold text-sky-700">{{ $progressPercent }}%</span>
                </div>

                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-sky-100">
                    <div class="h-2 rounded-full bg-gradient-to-r from-sky-500 to-cyan-500 transition-all duration-700" style="width: {{ $progressPercent }}%"></div>
                </div>

                <div class="mt-4 grid grid-cols-5 gap-1">
                    @foreach ($progressSteps as $stepKey => $step)
                        @php
                            $stepIndex = array_search($stepKey, $progressKeys, true);
                            $isDone = $stepIndex <= $currentProgressIndex;
                            $isCurrent = $stepIndex === $currentProgressIndex;
                        @endphp
                        <div class="text-center">
                            <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full border text-xs font-bold {{ $isDone ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-300 bg-white text-slate-500' }} {{ $isCurrent ? 'bw-progress-pulse' : '' }}">
                                {{ $step['icon'] }}
                            </div>
                            <p class="mt-1 text-[10px] leading-tight {{ $isDone ? 'text-sky-700 font-semibold' : 'text-slate-500' }}">{{ $step['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($order->completion_photo_path)
                <div class="mt-4">
                    <p class="text-sm font-semibold text-slate-900">Bukti Cucian Selesai</p>
                    <img src="{{ route('media.public', ['path' => $order->completion_photo_path]) }}" alt="Bukti cucian selesai" class="mt-2 max-h-64 rounded-lg border border-slate-200">
                </div>
            @endif

            <div class="mt-5 text-center text-xs text-slate-500 border-t border-slate-200 pt-4">
                {{ $laundryProfile->invoice_footer_note ?: 'Terima kasih sudah menggunakan layanan kami.' }}
            </div>
        </div>
    </div>
</body>
</html>
