<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Detail Order {{ $order->receipt_number }}</h2>
            <a href="{{ route('orders.tracking') }}" class="text-sm text-slate-700 hover:underline">Kembali ke Tracking</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $showPaymentInfo = in_array($order->status, ['selesai', 'diambil'], true);
                $customerCorrectionLocked = auth()->user()?->role === 'kasir' && $order->status === 'diambil';
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bw-card p-4">
                    <p class="text-sm text-slate-500">Pelanggan</p>
                    <p class="mt-1 font-semibold">{{ $order->customer?->name ?? '-' }}</p>
                    <p class="text-sm text-slate-600">{{ $order->customer?->phone ?? '-' }}</p>
                </div>
                <div class="bw-card p-4">
                    <p class="text-sm text-slate-500">Status</p>
                    <p class="mt-1 font-semibold">{{ $order->status }}</p>
                    @if ($showPaymentInfo)
                        <p class="text-sm text-slate-600">Pembayaran: {{ $order->payment_status }}</p>
                        <p class="text-sm text-slate-600">Metode: {{ $order->paymentOption?->label ?? '-' }}</p>
                        @if ($order->payment_proof_path)
                            <a href="{{ route('media.public', ['path' => $order->payment_proof_path]) }}" target="_blank" class="inline-flex mt-2 text-xs font-semibold text-cyan-700 hover:underline">Lihat Bukti Pembayaran</a>
                        @endif
                    @else
                        <p class="text-sm text-slate-600">Pembayaran: Ditentukan saat pengambilan</p>
                    @endif
                </div>
                <div class="bw-card p-4">
                    <p class="text-sm text-slate-500">Nominal</p>
                    <p class="text-sm text-slate-700">Total: Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</p>
                    <p class="text-sm text-slate-700">Diskon: Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}</p>
                    <p class="mt-1 font-semibold">Final: Rp {{ number_format((float) $order->final_amount, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="bw-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Perbaiki Data Customer</p>
                        <p class="mt-1 text-xs text-slate-500">Gunakan form ini kalau nama/telepon/alamat customer salah input pada order ini.</p>
                        @if ($customerCorrectionLocked)
                            <p class="mt-1 text-xs font-medium text-amber-700">Order sudah diambil. Kasir tidak bisa mengubah data customer.</p>
                        @endif
                    </div>
                    @if ($order->customer_id && ! $customerCorrectionLocked)
                        <form method="POST" action="{{ route('orders.customer.remove', $order) }}" onsubmit="return confirm('Hapus data customer dari order ini?');">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500">Hapus dari Order</button>
                        </form>
                    @endif
                </div>

                <form method="POST" action="{{ route('orders.customer.update', $order) }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <input type="text" name="customer_name" value="{{ old('customer_name', $order->customer?->name) }}" class="bw-input" placeholder="Nama customer" @disabled($customerCorrectionLocked) required>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone', $order->customer?->phone) }}" class="bw-input" placeholder="No. HP customer" @disabled($customerCorrectionLocked) required>
                    <input type="text" name="customer_address" value="{{ old('customer_address', $order->customer?->address) }}" class="bw-input" placeholder="Alamat (opsional)" @disabled($customerCorrectionLocked)>
                    <div class="md:col-span-3">
                        @if (! $customerCorrectionLocked)
                            <button class="bw-btn-primary">Simpan Perubahan Customer</button>
                        @endif
                    </div>
                </form>
            </div>

            @if ($order->completion_photo_path)
                <div class="bw-card p-4">
                    <p class="text-sm text-slate-500">Bukti Cucian Selesai</p>
                    <img src="{{ route('media.public', ['path' => $order->completion_photo_path]) }}" alt="Bukti cucian selesai" class="mt-2 max-h-64 rounded-lg border border-slate-200">
                </div>
            @endif

            <div class="bw-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-800">Preview Pesan WA</p>
                    <div class="flex items-center gap-2">
                        <a href="{{ $invoiceUrl }}" target="_blank" class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500 transition">Buka Nota</a>
                        <a href="{{ $invoicePdfUrl }}" class="inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500 transition">Download PDF</a>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition"
                            onclick="navigator.clipboard.writeText('{{ $invoiceUrl }}').then(function(){ alert('Link nota disalin'); }).catch(function(){ window.prompt('Copy link nota:', '{{ $invoiceUrl }}'); });"
                        >
                            Copy Link Nota
                        </button>
                        <a href="{{ route('orders.send-wa', $order) }}" target="_blank" class="bw-btn-accent">Kirim WA Sekarang</a>
                    </div>
                </div>

                @if ($waPreview)
                    <p class="mt-2 text-xs text-slate-500">Template aktif: {{ $waPreview['template_key'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">Nomor tujuan: {{ $waPreview['phone'] }}</p>
                    <textarea class="mt-3 w-full bw-input" rows="5" readonly>{{ $waPreview['message'] }}</textarea>
                @else
                    <p class="mt-2 text-sm text-rose-600">Preview WA belum tersedia. Pastikan template aktif dan nomor customer valid.</p>
                @endif
            </div>

            <div class="bw-table-wrap overflow-x-auto">
                <div class="p-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Detail Item</h3>
                    <p class="mt-1 text-xs text-slate-500">Rincian layanan ditampilkan dalam format yang mudah dibaca.</p>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Layanan</th>
                            <th class="px-4 py-2 text-left">Qty</th>
                            <th class="px-4 py-2 text-left">Durasi</th>
                            <th class="px-4 py-2 text-left">Addon</th>
                            <th class="px-4 py-2 text-left">Rincian Harga</th>
                            <th class="px-4 py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($order->details as $detail)
                            @php
                                $snapshot = is_array($detail->snapshot_data) ? $detail->snapshot_data : [];
                                $serviceName = $snapshot['service_name'] ?? ('Service #'.$detail->master_service_id);
                                $unit = $snapshot['unit'] ?? null;
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
                                    ->values()
                                    ->all();
                                $baseAmount = (float) ($snapshot['base_amount'] ?? 0);
                                $durationAmount = (float) ($snapshot['duration_amount'] ?? 0);
                                $addonsAmount = (float) ($snapshot['addons_amount'] ?? 0);
                                $lineTotal = (float) ($snapshot['sub_total_calculated'] ?? 0);
                            @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <p class="font-semibold text-slate-900">{{ $serviceName }}</p>
                                    <p class="text-xs text-slate-500">ID: {{ $detail->master_service_id }}{{ $unit ? ' • '.$unit : '' }}</p>
                                </td>
                                <td class="px-4 py-2">{{ $detail->qty }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ $durationName ?: '-' }}</td>
                                <td class="px-4 py-2">
                                    @if (count($addonNames) > 0)
                                        <ul class="space-y-1">
                                            @foreach ($addonNames as $addonName)
                                                <li class="text-slate-700">{{ $addonName }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-slate-700">
                                    <p>Base: Rp {{ number_format($baseAmount, 0, ',', '.') }}</p>
                                    <p>Durasi: Rp {{ number_format($durationAmount, 0, ',', '.') }}</p>
                                    <p>Addon: Rp {{ number_format($addonsAmount, 0, ',', '.') }}</p>
                                </td>
                                <td class="px-4 py-2 text-right font-semibold text-slate-900">Rp {{ number_format($lineTotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Tidak ada detail.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bw-table-wrap overflow-x-auto">
                <div class="p-4 border-b border-gray-100"><h3 class="font-semibold">Log Aktivitas</h3></div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Waktu</th>
                            <th class="px-4 py-2 text-left">Aktor</th>
                            <th class="px-4 py-2 text-left">Aksi</th>
                            <th class="px-4 py-2 text-left">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($order->logs as $log)
                            <tr>
                                <td class="px-4 py-2">{{ $log->created_at }}</td>
                                <td class="px-4 py-2">{{ $log->user?->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $log->action_type }}</td>
                                <td class="px-4 py-2">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada log.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
