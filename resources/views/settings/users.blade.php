<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">User Management</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="bw-card p-5">
                <h3 class="text-lg font-semibold text-slate-900">Tambah User Baru</h3>
                <p class="mt-1 text-sm text-slate-600">Buat akun admin/kasir/owner tanpa lewat halaman register publik.</p>

                <form method="POST" action="{{ route('settings.users.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <input type="text" name="name" value="{{ old('name') }}" class="bw-input" placeholder="Nama user" required>
                    <input type="text" name="username" value="{{ old('username') }}" class="bw-input" placeholder="Username (lowercase/angka/underscore)" required>
                    <input type="email" name="email" value="{{ old('email') }}" class="bw-input" placeholder="Email user" required>
                    <select name="role" class="bw-input" required>
                        <option value="kasir" @selected(old('role') === 'kasir')>Kasir</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                    </select>
                    <label class="inline-flex items-center gap-2 text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                        Akun aktif
                    </label>
                    <input type="password" name="password" class="bw-input" placeholder="Password (min 8 karakter)" required>
                    <input type="password" name="password_confirmation" class="bw-input" placeholder="Konfirmasi password" required>

                    <div class="md:col-span-2">
                        <button class="bw-btn-accent">Buat User</button>
                    </div>
                </form>
            </div>

            <div class="bw-card p-5">
                <h3 class="text-lg font-semibold text-slate-900">Edit & Hapus Akun User</h3>
                <p class="mt-1 text-sm text-slate-600">Khusus owner: edit nama, username, email, role, status, reset password, atau hapus user.</p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Username</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Role</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Password Baru (opsional)</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-lg border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-sm font-semibold tracking-wide text-cyan-800">
                                            {{ '@'.$item->username }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $item->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $item->email }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ strtoupper($item->role) }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                    <td class="px-4 py-3 text-slate-500">-</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-2">
                                            <a href="#edit-user-{{ $item->id }}" class="inline-flex items-center rounded-lg bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600">Edit</a>
                                            <form method="POST" action="{{ route('settings.users.destroy', $item) }}" onsubmit="return confirm('Hapus user {{ $item->name }}? Tindakan ini tidak bisa dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="edit-user-{{ $item->id }}" class="bg-slate-50/70">
                                    <td colspan="7" class="px-4 py-4">
                                        <form method="POST" action="{{ route('settings.users.update', $item) }}" class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="username" value="{{ $item->username }}" class="bw-input" placeholder="Username" required>
                                            <input type="text" name="name" value="{{ $item->name }}" class="bw-input" placeholder="Nama" required>
                                            <input type="email" name="email" value="{{ $item->email }}" class="bw-input" placeholder="Email" required>
                                            <select name="role" class="bw-input" required>
                                                <option value="owner" @selected($item->role === 'owner')>Owner</option>
                                                <option value="admin" @selected($item->role === 'admin')>Admin</option>
                                                <option value="kasir" @selected($item->role === 'kasir')>Kasir</option>
                                            </select>
                                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700">
                                                <input type="checkbox" name="is_active" value="1" @checked($item->is_active)>
                                                Aktif
                                            </label>
                                            <div class="flex items-center">
                                                <button class="bw-btn-primary">Simpan Perubahan</button>
                                            </div>
                                            <input type="password" name="password" class="bw-input md:col-span-2" placeholder="Password baru (opsional)">
                                            <input type="password" name="password_confirmation" class="bw-input md:col-span-2" placeholder="Konfirmasi password baru">
                                            <p class="text-xs text-slate-500 md:col-span-2 lg:col-span-2">Kosongkan password jika tidak ingin reset.</p>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
