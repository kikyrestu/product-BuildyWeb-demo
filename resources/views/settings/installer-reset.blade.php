<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Reset Installer</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bw-card p-6">
                <h3 class="text-lg font-semibold text-slate-900">Mode Ulang Setup Database</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Fitur ini hanya untuk owner. Setelah reset, lock installer dihapus, kamu akan logout otomatis,
                    lalu aplikasi kembali ke halaman setup awal database.
                </p>

                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Pastikan kamu paham risikonya. Setup ulang bisa mengubah konfigurasi database aktif.
                </div>

                <form method="POST" action="{{ route('settings.installer.reset') }}" class="mt-5 space-y-3" onsubmit="return confirm('Yakin aktifkan ulang installer? Anda akan logout otomatis.');">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Konfirmasi Password Owner</label>
                        <input type="password" name="password" class="bw-input" required>
                    </div>

                    <button class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">
                        Aktifkan Ulang Installer
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
