<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $brandName = \App\Models\LaundryProfile::query()->value('laundry_name') ?: config('app.name', 'SI Laundry');
            $pageTitle = match (true) {
                request()->routeIs('login') => 'Masuk',
                request()->routeIs('password.request') => 'Lupa Password',
                request()->routeIs('password.reset') => 'Reset Password',
                request()->routeIs('install.*') => 'Setup Awal',
                default => null,
            };
            $metaDescription = $pageTitle
                ? $pageTitle.' - '.$brandName
                : 'Akses sistem operasional laundry dengan aman.';
        @endphp

        <title>{{ $pageTitle ? $pageTitle.' | '.$brandName : $brandName }}</title>
        <meta name="description" content="{{ $metaDescription }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Fallback utility styles for hosting environments with Vite asset issues -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .login-bg-art {
                background-size: cover;
                background-repeat: no-repeat;
                background-image: url('{{ asset('images/login-bg.png') }}');
                background-color: #0f172a;
            }

            .login-bg-art--character {
                background-image:
                    linear-gradient(90deg, rgba(15, 23, 42, 0.62) 0%, rgba(15, 23, 42, 0.34) 40%, rgba(15, 23, 42, 0.14) 100%),
                    url('{{ asset('images/login-bg.png') }}');
                background-position: 56% 8%;
                background-size: cover;
            }

            .login-bg-art--logo {
                background-image:
                    linear-gradient(90deg, rgba(15, 23, 42, 0.56) 0%, rgba(15, 23, 42, 0.30) 42%, rgba(15, 23, 42, 0.12) 100%),
                    url('{{ asset('images/login-bg.png') }}');
                background-position: 50% 6%;
                background-size: cover;
            }

            @media (max-width: 767px) {
                .login-bg-art--character {
                    background-image:
                        linear-gradient(180deg, rgba(15, 23, 42, 0.62) 0%, rgba(15, 23, 42, 0.38) 36%, rgba(15, 23, 42, 0.52) 100%),
                        url('{{ asset('images/login-bg.png') }}');
                    background-position: 62% 12%;
                    background-size: cover;
                }

                .login-bg-art--logo {
                    background-image:
                        linear-gradient(180deg, rgba(15, 23, 42, 0.52) 0%, rgba(15, 23, 42, 0.30) 36%, rgba(15, 23, 42, 0.52) 100%),
                        url('{{ asset('images/login-bg.png') }}');
                    background-position: 36% 10%;
                    background-size: cover;
                }
            }

            @media (min-width: 1280px) {
                .login-bg-art--character {
                    background-position: 58% 10%;
                    background-size: cover;
                }

                .login-bg-art--logo {
                    background-position: 52% 8%;
                    background-size: cover;
                }
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        @if (request()->routeIs('login'))
            @php
                $bgMode = request()->query('bg') === 'logo' ? 'logo' : 'character';
            @endphp
            <div class="relative min-h-screen overflow-hidden">
                <div
                    class="login-bg-art login-bg-art--{{ $bgMode }} absolute inset-0"
                ></div>

                <div class="absolute inset-y-0 left-0 w-[58%] bg-gradient-to-r from-slate-950/82 via-slate-900/44 to-transparent"></div>

                <div class="relative min-h-screen px-4 py-8 sm:px-8">
                    <div class="flex min-h-[calc(100vh-4rem)] items-center justify-start">
                        <div class="w-full max-w-md rounded-2xl border border-white/30 bg-slate-900/48 p-6 shadow-[0_30px_70px_rgba(2,8,23,0.62)] ring-1 ring-white/18 backdrop-blur-xl sm:p-8">
                            <div class="mb-5">
                                <a href="/" class="inline-block text-xs font-semibold uppercase tracking-[0.2em] text-sky-100/80">
                                    {{ $brandName }}
                                </a>
                                <h1 class="mt-2 text-3xl font-bold text-white">Masuk Ke Sistem</h1>
                                <p class="mt-1 text-sm text-sky-100/80">Silakan login untuk mengelola operasional laundry.</p>
                            </div>
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
                <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </body>
</html>
