<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $brandName = \App\Models\LaundryProfile::query()->value('laundry_name') ?: config('app.name', 'SI Laundry');
    @endphp

    <title>{{ $brandName }}</title>
    <meta name="description" content="Sistem kasir, tracking, invoice, dan laporan keuangan untuk operasional laundry harian.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-4xl items-center justify-center px-5 py-10">
        <section class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-sm md:p-10">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Aplikasi Laundry</p>
            <h1 class="text-3xl font-bold leading-tight text-slate-900 md:text-4xl">{{ $brandName }}</h1>
            <p class="mt-3 max-w-2xl text-sm text-slate-600 md:text-base">
                Sistem kasir, tracking, invoice, dan laporan keuangan untuk operasional laundry harian.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Buka Dashboard
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Masuk
                    </a>
                @endauth
            </div>
        </section>
    </main>
</body>
</html>
