<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Profil Laundry</h2>
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

            <div class="bw-card p-6">
                <h3 class="text-lg font-semibold text-slate-900">Data Bisnis Laundry</h3>
                <p class="mt-1 text-sm text-slate-600">Data ini otomatis dipakai di Nota Digital dan PDF.</p>

                <form method="POST" action="{{ route('settings.laundry-profile.update') }}" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    @method('PUT')

                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 items-start">
                        <div>
                            <label class="block text-sm text-slate-700 mb-1">Logo Laundry</label>
                            <input type="file" name="logo_image" accept="image/*" class="bw-input">
                            <p class="mt-1 text-xs text-slate-500">Format: JPG/PNG/WEBP, maksimal 4MB.</p>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700 mb-1">Preview Logo</label>
                            @if ($profile->logo_path)
                                <img src="{{ route('media.public', ['path' => $profile->logo_path]) }}" alt="Logo Laundry" class="h-24 w-24 rounded-xl border border-slate-200 object-cover bg-white">
                            @else
                                <div class="h-24 w-24 rounded-xl border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-xs text-slate-500">
                                    Belum ada logo
                                </div>
                            @endif
                        </div>
                    </div>

                    <input
                        type="text"
                        name="laundry_name"
                        class="bw-input"
                        value="{{ old('laundry_name', $profile->laundry_name) }}"
                        placeholder="Nama Laundry"
                        required
                    >

                    <input
                        type="text"
                        name="owner_name"
                        class="bw-input"
                        value="{{ old('owner_name', $profile->owner_name) }}"
                        placeholder="Nama Owner"
                    >

                    <input
                        type="text"
                        name="phone"
                        class="bw-input"
                        value="{{ old('phone', $profile->phone) }}"
                        placeholder="No Telepon"
                    >

                    <input
                        type="text"
                        name="whatsapp"
                        class="bw-input"
                        value="{{ old('whatsapp', $profile->whatsapp) }}"
                        placeholder="No WhatsApp"
                    >

                    <input
                        type="email"
                        name="email"
                        class="bw-input"
                        value="{{ old('email', $profile->email) }}"
                        placeholder="Email Bisnis"
                    >

                    <div class="grid grid-cols-2 gap-3">
                        <input
                            type="text"
                            name="city"
                            class="bw-input"
                            value="{{ old('city', $profile->city) }}"
                            placeholder="Kota"
                        >
                        <input
                            type="text"
                            name="postal_code"
                            class="bw-input"
                            value="{{ old('postal_code', $profile->postal_code) }}"
                            placeholder="Kode Pos"
                        >
                    </div>

                    <textarea
                        name="address"
                        rows="3"
                        class="bw-input md:col-span-2"
                        placeholder="Alamat lengkap laundry"
                    >{{ old('address', $profile->address) }}</textarea>

                    <input
                        type="text"
                        name="invoice_footer_note"
                        class="bw-input md:col-span-2"
                        value="{{ old('invoice_footer_note', $profile->invoice_footer_note) }}"
                        placeholder="Footer nota (contoh: Komplain maksimal 2x24 jam)"
                    >

                    <div class="md:col-span-2">
                        <button class="bw-btn-primary">Simpan Profil Laundry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
