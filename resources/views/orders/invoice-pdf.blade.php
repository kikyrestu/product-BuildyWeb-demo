<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota {{ $order->receipt_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            font-size: 12px;
            margin: 18px;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .muted {
            color: #475569;
        }

        .section {
            margin-top: 12px;
            border-top: 1px dashed #94a3b8;
            padding-top: 8px;
        }

        .line-item {
            padding: 6px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .line-title {
            font-weight: 600;
        }

        .right {
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .total {
            font-weight: 700;
            font-size: 14px;
        }

        .footer {
            margin-top: 16px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .logo-wrap {
            width: 52px;
            text-align: right;
        }

        .logo {
            height: 44px;
            width: 44px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            object-fit: cover;
        }

        .progress-card {
            margin-top: 10px;
            border: 1px solid #bae6fd;
            background: #f0f9ff;
            border-radius: 8px;
            padding: 8px;
        }

        .progress-line {
            margin-top: 6px;
            height: 6px;
            width: 100%;
            background: #dbeafe;
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 6px;
            background: #0284c7;
            border-radius: 999px;
        }

        .progress-steps {
            margin-top: 8px;
            width: 100%;
            border-collapse: collapse;
        }

        .progress-node {
            display: inline-block;
            height: 16px;
            width: 16px;
            border-radius: 50%;
            border: 1px solid #94a3b8;
            line-height: 16px;
            font-size: 9px;
            text-align: center;
            color: #475569;
            background: #ffffff;
        }

        .progress-node-active {
            border-color: #0369a1;
            background: #0284c7;
            color: #ffffff;
        }

        .progress-label {
            margin-top: 2px;
            font-size: 9px;
            color: #64748b;
        }

        .progress-label-active {
            color: #075985;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">{{ $laundryProfile->laundry_name }}</div>
                <div class="muted">Nota Laundry - {{ $order->receipt_number }}</div>
            </td>
            <td class="logo-wrap">
                @if ($laundryLogoDataUri)
                    <img src="{{ $laundryLogoDataUri }}" alt="Logo {{ $laundryProfile->laundry_name }}" class="logo">
                @endif
            </td>
        </tr>
    </table>

    <div style="margin-top: 6px;" class="muted">
        @if ($laundryProfile->owner_name)
            <div>Owner: {{ $laundryProfile->owner_name }}</div>
        @endif
        @if ($laundryProfile->phone || $laundryProfile->whatsapp)
            <div>
                Kontak: {{ $laundryProfile->phone ?: '-' }}
                @if ($laundryProfile->whatsapp)
                    | WA {{ $laundryProfile->whatsapp }}
                @endif
            </div>
        @endif
        @if ($laundryProfile->address)
            <div>{{ $laundryProfile->address }}</div>
        @endif
        @if ($laundryProfile->city || $laundryProfile->postal_code)
            <div>{{ trim(($laundryProfile->city ?? '').' '.($laundryProfile->postal_code ?? '')) }}</div>
        @endif
    </div>

    <div style="margin-top: 8px;">
        <div><strong>{{ $order->customer?->name ?? 'Pelanggan' }}</strong></div>
        <div class="muted">{{ $order->customer?->phone ?? '-' }}</div>
        <div class="muted">Kasir: {{ $order->cashier?->name ?? '-' }}</div>
        <div class="muted">Tanggal: {{ optional($order->created_at)->format('d-m-Y H:i') }}</div>
        <div class="muted">Status: {{ $order->status }}</div>
    </div>

    @php
        $progressSteps = [
            'antrean' => ['label' => 'Antrean', 'icon' => '1'],
            'proses_cuci' => ['label' => 'Cuci', 'icon' => '2'],
            'proses_setrika' => ['label' => 'Setrika', 'icon' => '3'],
            'selesai' => ['label' => 'Selesai', 'icon' => '4'],
            'diambil' => ['label' => 'Diambil', 'icon' => '5'],
        ];
        $progressKeys = array_keys($progressSteps);
        $currentProgressIndex = array_search($order->status, $progressKeys, true);
        $currentProgressIndex = $currentProgressIndex === false ? 0 : $currentProgressIndex;
        $progressPercent = (int) round(($currentProgressIndex / (max(count($progressKeys) - 1, 1))) * 100);
    @endphp

    <div class="progress-card">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="font-size:10px; font-weight:700; color:#0369a1; text-transform:uppercase; letter-spacing:0.4px;">Progress Laundry</td>
                <td style="font-size:10px; font-weight:700; color:#0369a1; text-align:right;">{{ $progressPercent }}%</td>
            </tr>
        </table>

        <div class="progress-line">
            <div class="progress-fill" style="width: {{ $progressPercent }}%;"></div>
        </div>

        <table class="progress-steps">
            <tr>
                @foreach ($progressSteps as $stepKey => $step)
                    @php
                        $stepIndex = array_search($stepKey, $progressKeys, true);
                        $isDone = $stepIndex <= $currentProgressIndex;
                    @endphp
                    <td style="text-align:center; width:20%;">
                        <span class="progress-node {{ $isDone ? 'progress-node-active' : '' }}">{{ $step['icon'] }}</span>
                        <div class="progress-label {{ $isDone ? 'progress-label-active' : '' }}">{{ $step['label'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>

        @php
            $statusLabelMap = [
                'antrean' => 'Antrean',
                'proses_cuci' => 'Proses Cuci',
                'proses_setrika' => 'Proses Setrika',
                'selesai' => 'Selesai',
                'diambil' => 'Sudah Diambil',
            ];
            $currentStatusLabel = $statusLabelMap[$order->status] ?? ucfirst((string) $order->status);
        @endphp

        <div style="margin-top: 6px; font-size: 10px; color: #0f172a; font-weight: 700;">
            Tahap saat ini: {{ $currentStatusLabel }} ({{ $progressPercent }}%)
        </div>

        <table style="margin-top: 5px; width: 100%; border-collapse: collapse; font-size: 9px; color: #0f172a;">
            <thead>
                <tr>
                    @foreach ($progressSteps as $step)
                        <th style="border: 1px solid #cbd5e1; padding: 2px 1px; text-align: center;">{{ $step['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach ($progressSteps as $stepKey => $step)
                        @php
                            $stepIndex = array_search($stepKey, $progressKeys, true);
                            $isDone = $stepIndex <= $currentProgressIndex;
                        @endphp
                        <td style="border: 1px solid #cbd5e1; padding: 2px 1px; text-align: center;">{{ $isDone ? 'OK' : '-' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div style="font-weight: 700; margin-bottom: 4px;">Rincian Item</div>
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

            <div class="line-item">
                <div class="line-title">{{ $serviceName }} ({{ $detail->qty }})</div>
                @if ($durationName)
                    <div class="muted">Durasi: {{ $durationName }}</div>
                @endif
                @if ($addonNames !== '')
                    <div class="muted">Addon: {{ $addonNames }}</div>
                @endif
                <div class="right"><strong>Rp {{ number_format($lineTotal, 0, ',', '.') }}</strong></div>
            </div>
        @empty
            <div class="muted">Tidak ada rincian item.</div>
        @endforelse
    </div>

    <div class="section">
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Diskon</td>
                <td class="right">Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total">Total</td>
                <td class="right total">Rp {{ number_format((float) $order->final_amount, 0, ',', '.') }}</td>
            </tr>
        </table>

        @php
            $showPaymentInfo = in_array($order->status, ['selesai', 'diambil'], true);
        @endphp

        @if ($showPaymentInfo)
            <div style="margin-top: 8px;" class="muted">Pembayaran: {{ $order->payment_status }}</div>
            <div class="muted">Metode: {{ $order->paymentOption?->label ?? '-' }}</div>
            @if ($order->payment_note)
                <div class="muted">Catatan: {{ $order->payment_note }}</div>
            @endif
        @endif
    </div>

    <div class="footer">{{ $laundryProfile->invoice_footer_note ?: 'Terima kasih sudah menggunakan layanan kami.' }}</div>
</body>
</html>
