<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Master Data</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            <div class="bw-card p-6">
                <h3 class="text-lg font-semibold text-slate-900">Tutorial Master Data</h3>
                <p class="mt-1 text-sm text-slate-600">Biar user baru gak bingung, ikutin urutan ini pas setup awal:</p>
                <ol class="mt-4 space-y-2 text-sm text-slate-700 list-decimal list-inside">
                    <li>Tambah minimal 1 item <span class="font-semibold">Service</span> dulu (contoh: Cuci Kering Lipat).</li>
                    <li>Lanjut tambah <span class="font-semibold">Duration</span> untuk biaya percepatan (contoh: Express 6 Jam).</li>
                    <li>Tambahkan <span class="font-semibold">Addon</span> opsional (contoh: Pewangi Premium).</li>
                    <li>Cek hasilnya di POS, pastikan service muncul sebelum transaksi pertama.</li>
                </ol>
                <div class="mt-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                    Tips: Field <span class="font-semibold">Unit</span> cocok diisi <span class="font-semibold">kg</span> atau <span class="font-semibold">pcs</span> untuk service. Duration dan addon boleh dikosongkan.
                </div>
            </div>

            <div class="bw-card p-6">
                <h3 class="text-lg font-semibold text-slate-900">Tambah Master Item</h3>
                <form action="{{ route('master.store') }}" method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
                    @csrf
                    <select name="type" class="bw-input md:col-span-1" required>
                        <option value="service">Service</option>
                        <option value="duration">Duration</option>
                        <option value="addon">Addon</option>
                    </select>
                    <input type="text" name="name" class="bw-input md:col-span-2" placeholder="Nama item" required>
                    <input type="number" step="0.01" name="base_price" class="bw-input md:col-span-1" placeholder="Harga" required>
                    <input type="text" name="unit" class="bw-input md:col-span-1" placeholder="Unit (opsional)">
                    <button class="bw-btn-primary md:col-span-1">Simpan</button>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bw-card p-4">
                    <h4 class="font-semibold">Services</h4>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse($services as $item)
                            <li class="flex justify-between border-b border-slate-100 pb-2"><span>{{ $item->name }}</span><span>Rp {{ number_format((float) $item->base_price, 0, ',', '.') }}</span></li>
                        @empty
                            <li class="text-gray-500">Belum ada data.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bw-card p-4">
                    <h4 class="font-semibold">Durations</h4>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse($durations as $item)
                            <li class="flex justify-between border-b border-slate-100 pb-2"><span>{{ $item->name }}</span><span>Rp {{ number_format((float) $item->base_price, 0, ',', '.') }}</span></li>
                        @empty
                            <li class="text-gray-500">Belum ada data.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bw-card p-4">
                    <h4 class="font-semibold">Addons</h4>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse($addons as $item)
                            <li class="flex justify-between border-b border-slate-100 pb-2"><span>{{ $item->name }}</span><span>Rp {{ number_format((float) $item->base_price, 0, ',', '.') }}</span></li>
                        @empty
                            <li class="text-gray-500">Belum ada data.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
