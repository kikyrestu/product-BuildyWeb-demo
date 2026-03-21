<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            POS Kasir
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bw-card p-5">
                <h3 class="font-semibold text-slate-900">Checkout Cepat</h3>
                <form method="POST" action="{{ route('pos.checkout') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                    @csrf
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" class="bw-input" placeholder="Nama pelanggan" required>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" class="bw-input" placeholder="No. WA pelanggan" required>
                    <input type="text" name="customer_address" value="{{ old('customer_address') }}" class="bw-input" placeholder="Alamat (opsional)">

                    <select id="service_id" name="service_id" class="bw-input" required>
                        <option value="">Pilih Service</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" data-unit="{{ strtolower(trim((string) ($service->unit ?? 'kg'))) }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <div>
                        <div class="relative">
                            <input type="number" step="0.01" min="0.1" name="qty" value="{{ old('qty', '1') }}" class="bw-input pr-16" placeholder="Qty" required>
                            <span id="qty-unit-badge" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-500">/kg</span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Contoh: 1 /kg</p>
                    </div>
                    <select name="duration_id" class="bw-input">
                        <option value="">Tanpa Duration</option>
                        @foreach ($durations as $duration)
                            <option value="{{ $duration->id }}" @selected((string) old('duration_id') === (string) $duration->id)>{{ $duration->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="voucher_code" value="{{ old('voucher_code') }}" class="bw-input" placeholder="Kode voucher (opsional)">

                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="text-sm text-slate-700">Addons (opsional)</label>
                        <div class="mt-2 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-2">
                            @foreach ($addons as $addon)
                                <label class="flex items-center gap-2 text-sm border border-slate-200 rounded-lg px-3 py-2 bg-slate-50/60">
                                    <input type="checkbox" name="addon_ids[]" value="{{ $addon->id }}" @checked(in_array((string) $addon->id, collect(old('addon_ids', []))->map(fn($v) => (string) $v)->all(), true))>
                                    <span>{{ $addon->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="xl:col-span-4">
                        <p class="mb-2 text-xs text-slate-500">Pembayaran dipilih saat proses pengambilan di Tracking Board.</p>
                        <button class="bw-btn-primary">Checkout Sekarang</button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2 bw-card p-5">
                    <h3 class="font-semibold text-slate-900">Pilih Layanan</h3>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse ($services as $service)
                            <button type="button" class="text-left border border-slate-200 rounded-lg p-3 bg-white/80 hover:border-slate-400 hover:bg-white">
                                <p class="font-semibold text-sm">{{ $service->name }}</p>
                                <p class="text-xs text-slate-500 mt-1">Rp {{ number_format((float) $service->base_price, 0, ',', '.') }} / {{ $service->unit ?? 'item' }}</p>
                            </button>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada service aktif. Tambahkan di Master Data.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bw-card p-5">
                    <h3 class="font-semibold text-slate-900">Ringkasan Cepat</h3>
                    <ul class="mt-3 text-sm space-y-2 text-slate-600">
                        <li>Durasi aktif: {{ $durations->count() }}</li>
                        <li>Addons aktif: {{ $addons->count() }}</li>
                        <li>Service aktif: {{ $services->count() }}</li>
                    </ul>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="text-xs text-slate-500">Selanjutnya: integrasi form checkout realtime ke endpoint API checkout.</p>
                        <a href="{{ route('orders.tracking') }}" class="inline-block mt-3 text-sm text-slate-800 font-medium hover:underline">Buka Tracking Board</a>
                    </div>
                </div>
            </div>

            <div class="bw-card p-5">
                <h3 class="font-semibold text-slate-900">Pilihan Durasi & Addons</h3>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium">Durations</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($durations as $item)
                                <span class="px-3 py-1 rounded-full text-xs bg-amber-100 text-amber-800">{{ $item->name }}</span>
                            @empty
                                <span class="text-sm text-gray-500">Belum ada duration.</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Addons</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($addons as $item)
                                <span class="px-3 py-1 rounded-full text-xs bg-blue-100 text-blue-800">{{ $item->name }}</span>
                            @empty
                                <span class="text-sm text-gray-500">Belum ada addon.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const serviceSelect = document.getElementById('service_id');
            const unitBadge = document.getElementById('qty-unit-badge');

            if (!serviceSelect || !unitBadge) {
                return;
            }

            const updateUnit = function () {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const selectedUnit = (selectedOption && selectedOption.dataset && selectedOption.dataset.unit)
                    ? selectedOption.dataset.unit
                    : 'kg';

                unitBadge.textContent = '/' + selectedUnit;
            };

            serviceSelect.addEventListener('change', updateUnit);
            updateUnit();
        });
    </script>
</x-app-layout>
