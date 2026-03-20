<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4 rounded-lg border border-emerald-200/40 bg-emerald-500/12 px-3 py-2 text-emerald-100" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Username or Email -->
        <div>
            <label for="login" class="mb-1 block text-sm font-semibold text-sky-50">Username atau Email</label>
            <input
                id="login"
                class="block w-full rounded-xl border border-sky-200/35 bg-white/88 px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-200/70"
                type="text"
                name="login"
                value="{{ old('login') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="username atau nama@email.com"
            />
            <x-input-error :messages="$errors->get('login')" class="mt-2 text-xs text-rose-200" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="mb-1 block text-sm font-semibold text-sky-50">Password</label>
            <input
                id="password"
                class="block w-full rounded-xl border border-sky-200/35 bg-white/88 px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-200/70"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs text-rose-200" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-sky-200/70 bg-white/90 text-sky-600 shadow-sm focus:ring-sky-400" name="remember">
                <span class="ms-2 text-sm font-medium text-sky-100/90">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-sky-100/90 underline underline-offset-2 hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-sky-300" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <button
                type="submit"
                class="inline-flex items-center rounded-xl border border-sky-300/60 bg-sky-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-sky-900/30 transition hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-300"
            >
                {{ __('Log in') }}
            </button>
        </div>
    </form>
</x-guest-layout>
