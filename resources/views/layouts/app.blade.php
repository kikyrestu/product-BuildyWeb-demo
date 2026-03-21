<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $brandName = \App\Models\LaundryProfile::query()->value('laundry_name') ?: config('app.name', 'SI Laundry');
        $brandLogoPath = \App\Models\LaundryProfile::query()->value('logo_path');
        $pageTitle = match (true) {
            request()->routeIs('dashboard') => 'Dashboard',
            request()->routeIs('pos.*') => 'POS Kasir',
            request()->routeIs('orders.tracking') => 'Tracking Board',
            request()->routeIs('orders.show') => 'Detail Order',
            request()->routeIs('reports.financial') => 'Laporan Keuangan',
            request()->routeIs('master.*') => 'Master Data',
            request()->routeIs('settings.laundry-profile*') => 'Profil Laundry',
            request()->routeIs('settings.wa-templates*') => 'WA Templates',
            request()->routeIs('settings.payment-options*') => 'Payment Settings',
            request()->routeIs('settings.users*') => 'User Management',
            request()->routeIs('profile.*') => 'Profil Akun',
            default => null,
        };
        $metaDescription = $pageTitle
            ? $pageTitle.' - '.$brandName
            : 'Sistem operasional laundry untuk kasir, tracking, invoice, dan laporan.';
    @endphp

    <title>{{ $pageTitle ? $pageTitle.' | '.$brandName : $brandName }}</title>
    <meta name="description" content="{{ $metaDescription }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Fallback utility styles for hosting environments with Vite asset issues -->
    <script src="https://cdn.tailwindcss.com"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bw-shell" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">
        <aside class="hidden md:flex md:w-72 md:flex-col bg-gradient-to-b from-slate-950 via-slate-900 to-slate-800 text-slate-100">
            <div class="px-6 py-6 border-b border-slate-700/70">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    @if (! empty($brandLogoPath))
                        <img src="{{ route('media.public', ['path' => $brandLogoPath]) }}" alt="Logo {{ $brandName }}" class="h-10 w-10 rounded-xl border border-white/20 bg-white object-cover shadow-lg shadow-cyan-500/20">
                    @else
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-blue-500 shadow-lg shadow-cyan-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7h18M6 12h12M8 17h8" />
                            </svg>
                        </span>
                    @endif
                    <span>
                        <span class="block text-lg font-semibold tracking-wide">{{ $brandName }}</span>
                        <span class="mt-0.5 block text-xs text-slate-300">Ops Center</span>
                    </span>
                </a>
            </div>

            <nav class="flex-1 px-4 py-5 space-y-2 text-sm">
                @php
                    $userRole = auth()->user()->role;
                    $canOwnerAdmin = in_array($userRole, ['owner', 'admin'], true);
                    $isOwner = $userRole === 'owner';
                @endphp
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('dashboard') ? 'bg-cyan-400/30 text-cyan-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-cyan-400/30 group-hover:text-cyan-100' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zm10-10h8V3h-8v8z"/></svg>
                    </span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('pos.index') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('pos.*') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('pos.*') ? 'bg-emerald-400/30 text-emerald-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-emerald-400/30 group-hover:text-emerald-100' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    </span>
                    <span>POS Kasir</span>
                </a>
                <a href="{{ route('orders.tracking') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('orders.tracking') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('orders.tracking') ? 'bg-amber-400/30 text-amber-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-amber-400/30 group-hover:text-amber-100' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    </span>
                    <span>Tracking Board</span>
                </a>
                @if ($canOwnerAdmin)
                    <a href="{{ route('master.index') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('master.*') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('master.*') ? 'bg-violet-400/30 text-violet-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-violet-400/30 group-hover:text-violet-100' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                        </span>
                        <span>Master Data</span>
                    </a>
                    <a href="{{ route('reports.financial') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('reports.financial') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('reports.financial') ? 'bg-rose-400/30 text-rose-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-rose-400/30 group-hover:text-rose-100' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19h16M7 16V9m5 7V5m5 11v-3"/></svg>
                        </span>
                        <span>Laporan</span>
                    </a>
                @endif

                @if ($isOwner)
                    <a href="{{ route('settings.laundry-profile') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('settings.laundry-profile*') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('settings.laundry-profile*') ? 'bg-fuchsia-400/30 text-fuchsia-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-fuchsia-400/30 group-hover:text-fuchsia-100' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
                        </span>
                        <span>Profil Laundry</span>
                    </a>
                    <a href="{{ route('settings.wa-templates') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('settings.wa-templates*') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('settings.wa-templates*') ? 'bg-green-400/30 text-green-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-green-400/30 group-hover:text-green-100' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-1.9 5.4A8.5 8.5 0 1 1 21 11.5z"/><path d="M8 10h8M8 14h5"/></svg>
                        </span>
                        <span>WA Templates</span>
                    </a>
                    <a href="{{ route('settings.payment-options') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('settings.payment-options*') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('settings.payment-options*') ? 'bg-cyan-400/30 text-cyan-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-cyan-400/30 group-hover:text-cyan-100' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h.01M10 12h4"/></svg>
                        </span>
                        <span>Payment Settings</span>
                    </a>
                    <a href="{{ route('settings.users') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('settings.users*') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('settings.users*') ? 'bg-indigo-400/30 text-indigo-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-indigo-400/30 group-hover:text-indigo-100' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                        </span>
                        <span>User Management</span>
                    </a>
                @endif
                <a href="{{ route('profile.edit') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('profile.*') ? 'bg-white/15 text-white shadow-inner ring-1 ring-white/20' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ request()->routeIs('profile.*') ? 'bg-slate-300/30 text-slate-100' : 'bg-slate-700/70 text-slate-200 group-hover:bg-slate-300/30 group-hover:text-slate-100' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span>Profile</span>
                </a>
            </nav>

            <div class="px-4 py-4 border-t border-slate-700/70 space-y-2">
                <div class="px-3">
                    <p class="text-sm text-slate-100 font-medium leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-cyan-200/90">{{ '@'.auth()->user()->username }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-slate-200 hover:bg-white/10 hover:text-white transition">
                        Log Out
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <div class="md:hidden bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
                <span class="font-semibold">{{ $brandName }}</span>
                <button type="button" class="px-3 py-1.5 rounded bg-slate-700" @click="sidebarOpen = !sidebarOpen">Menu</button>
            </div>

            <div x-show="sidebarOpen" x-transition class="md:hidden bg-slate-800 text-slate-100 px-4 py-3 space-y-2">
                @php
                    $userRole = auth()->user()->role;
                    $canOwnerAdmin = in_array($userRole, ['owner', 'admin'], true);
                    $isOwner = $userRole === 'owner';
                @endphp
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">Dashboard</a>
                <a href="{{ route('pos.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('pos.*') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">POS Kasir</a>
                <a href="{{ route('orders.tracking') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('orders.tracking') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">Tracking Board</a>
                @if ($canOwnerAdmin)
                    <a href="{{ route('master.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('master.*') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">Master Data</a>
                    <a href="{{ route('reports.financial') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('reports.financial') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">Laporan</a>
                @endif
                @if ($isOwner)
                    <a href="{{ route('settings.laundry-profile') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('settings.laundry-profile*') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">Profil Laundry</a>
                    <a href="{{ route('settings.wa-templates') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('settings.wa-templates*') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">WA Templates</a>
                    <a href="{{ route('settings.payment-options') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('settings.payment-options*') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">Payment Settings</a>
                    <a href="{{ route('settings.users') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('settings.users*') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">User Management</a>
                    <a href="{{ route('settings.installer.reset.form') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('settings.installer.*') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">Reset Installer</a>
                @endif
                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('profile.*') ? 'bg-slate-700 text-white' : 'text-slate-200' }}">Profile</a>
                <div class="px-3 py-2 rounded-lg bg-slate-700/40">
                    <p class="text-sm font-medium text-white leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-cyan-200/90">{{ '@'.auth()->user()->username }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-slate-200">Log Out</button>
                </form>
            </div>

            @isset($header)
                <header class="bg-white/90 border-b border-slate-200 backdrop-blur">
                    <div class="px-6 py-4 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1 px-2 lg:px-4">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
