<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $brandName = config('app.name', 'SI Laundry');

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('laundry_profiles')) {
                $brandName = \App\Models\LaundryProfile::query()->value('laundry_name') ?: $brandName;
            }
        } catch (\Throwable) {
            // Installer must stay accessible even when database schema is not ready yet.
        }
    @endphp
    <title>Setup Awal | {{ $brandName }}</title>
    <meta name="description" content="Setup awal konfigurasi database untuk {{ $brandName }}.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <div class="mx-auto max-w-xl px-4 py-10">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Setup Awal Aplikasi</h1>
            <p class="mt-1 text-sm text-slate-600">Masukkan kredensial database untuk menyelesaikan instalasi pertama.</p>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('install_test_lockout_seconds'))
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    @php
                        $lockoutSeconds = (int) session('install_test_lockout_seconds');
                        $lockoutMinutes = (int) ceil($lockoutSeconds / 60);
                    @endphp
                    Tes koneksi sementara dikunci. Sisa waktu: {{ $lockoutMinutes }} menit.
                </div>
            @endif

            @if (session('status'))
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('install.process') }}" class="mt-5 grid grid-cols-1 gap-3">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama Laundry</label>
                    <input type="text" name="app_name" value="{{ old('app_name', 'SI Laundry') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Contoh: Laundryku" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Driver Database</label>
                    <select name="db_connection" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                        <option value="pgsql" @selected(old('db_connection', 'pgsql') === 'pgsql')>PostgreSQL</option>
                        <option value="mysql" @selected(old('db_connection') === 'mysql')>MySQL</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">DB Host</label>
                        <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">DB Port</label>
                        <input type="number" name="db_port" value="{{ old('db_port', '5432') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">DB Name</label>
                    <input type="text" name="db_database" value="{{ old('db_database') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">DB Username</label>
                    <input type="text" name="db_username" value="{{ old('db_username') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">DB Password</label>
                    <input type="password" name="db_password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    Setup ini berjalan tanpa seed data otomatis. Sistem hanya membuat struktur database dan akun owner awal.
                </div>

                <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-sm font-semibold text-slate-800">Akun Owner Awal</p>
                    <p class="text-xs text-slate-600">Akun ini akan dibuat otomatis setelah setup berhasil.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama Owner</label>
                    <input type="text" name="owner_name" value="{{ old('owner_name', 'Owner Laundry') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email Owner</label>
                    <input type="email" name="owner_email" value="{{ old('owner_email') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Password Owner</label>
                        <input type="password" name="owner_password" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                        <input type="password" name="owner_password_confirmation" class="w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                </div>

                <p class="text-xs text-slate-500">Keamanan: percobaan tes koneksi dibatasi maksimal 5 kali gagal per IP, lalu dikunci sementara 10 menit.</p>

                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button
                        type="submit"
                        formaction="{{ route('install.test') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Tes Koneksi Dulu
                    </button>
                    <button class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Simpan & Jalankan Setup
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
