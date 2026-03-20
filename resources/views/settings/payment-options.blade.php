<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Payment Settings Owner</h2>
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

            <div class="bw-card bw-card-accent p-6">
                <h3 class="text-lg font-semibold text-slate-900">Tambah Opsi Pembayaran</h3>
                <p class="mt-1 text-sm text-slate-600">Owner bisa atur transfer bank, QRIS statis, atau cash kasir.</p>

                <form method="POST" action="{{ route('settings.payment-options.store') }}" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <select name="type" class="bw-input" required>
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer</option>
                        <option value="qris">QRIS</option>
                    </select>
                    <input type="text" name="label" class="bw-input" placeholder="Label (contoh: Transfer BCA)" required>
                    <input type="text" name="bank_name" class="bw-input" placeholder="Nama bank (opsional)">
                    <input type="text" name="account_name" class="bw-input" placeholder="Atas nama rekening (opsional)">
                    <input type="text" name="account_number" class="bw-input" placeholder="No rekening (opsional)">
                    <input type="number" name="sort_order" min="1" max="999" class="bw-input" placeholder="Urutan tampil, contoh: 10">
                    <input type="file" name="qris_image" accept="image/*" class="bw-input md:col-span-2">
                    <textarea name="notes" rows="2" class="bw-input md:col-span-2" placeholder="Catatan instruksi pembayaran"></textarea>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Aktif
                    </label>
                    <div class="md:col-span-2">
                        <button class="bw-btn-accent">Simpan Opsi Pembayaran</button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @forelse ($paymentOptions as $item)
                    <div class="bw-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item->label }}</p>
                                <p class="text-xs text-slate-500 uppercase tracking-wide">{{ $item->type }}</p>
                            </div>
                            <span class="bw-badge {{ $item->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>

                        @if ($item->type === 'transfer')
                            <div class="mt-3 text-sm text-slate-700 space-y-1">
                                <p>Bank: {{ $item->bank_name ?: '-' }}</p>
                                <p>A/N: {{ $item->account_name ?: '-' }}</p>
                                <p>No Rek: {{ $item->account_number ?: '-' }}</p>
                            </div>
                        @endif

                        @if ($item->type === 'qris' && $item->qris_image_path)
                            <img src="{{ route('media.public', ['path' => $item->qris_image_path]) }}" alt="QRIS" class="mt-3 h-40 w-auto rounded-lg border border-slate-200">
                        @endif

                        @if ($item->notes)
                            <p class="mt-3 text-sm text-slate-600">{{ $item->notes }}</p>
                        @endif

                        <form method="POST" action="{{ route('settings.payment-options.update', $item) }}" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                            @csrf
                            @method('PUT')
                            <select name="type" class="bw-input" required>
                                <option value="cash" @selected($item->type === 'cash')>Cash</option>
                                <option value="transfer" @selected($item->type === 'transfer')>Transfer</option>
                                <option value="qris" @selected($item->type === 'qris')>QRIS</option>
                            </select>
                            <input type="text" name="label" value="{{ $item->label }}" class="bw-input" required>
                            <input type="text" name="bank_name" value="{{ $item->bank_name }}" class="bw-input" placeholder="Nama bank">
                            <input type="text" name="account_name" value="{{ $item->account_name }}" class="bw-input" placeholder="Atas nama">
                            <input type="text" name="account_number" value="{{ $item->account_number }}" class="bw-input" placeholder="No rekening">
                            <input type="number" name="sort_order" min="1" max="999" value="{{ $item->sort_order }}" class="bw-input" placeholder="Urutan">
                            <input type="file" name="qris_image" accept="image/*" class="bw-input md:col-span-2">
                            <textarea name="notes" rows="2" class="bw-input md:col-span-2">{{ $item->notes }}</textarea>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_active" value="1" @checked($item->is_active)>
                                Aktif
                            </label>
                            <div class="md:col-span-2 flex flex-wrap gap-2">
                                <button class="bw-btn-primary">Update Opsi</button>
                                <form method="POST" action="{{ route('settings.payment-options.destroy', $item) }}" onsubmit="return confirm('Hapus opsi pembayaran ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500 transition">Hapus</button>
                                </form>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="bw-card p-5 text-sm text-slate-500 lg:col-span-2">
                        Belum ada opsi pembayaran. Tambahkan minimal 1 supaya kasir bisa pilih metode pembayaran.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
