<x-guest-layout>
    <!-- Logo -->
    <div class="mb-6 flex justify-center">
        <img src="{{ asset('images/sipkl-logo.png') }}" alt="SIPKL Logo" class="h-16 w-16 object-contain">
    </div>

    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-slate-900">SIPKL Administrator</h2>
        <p class="mt-1 text-sm text-slate-500">Monitoring Console</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                class="mt-1.5 block w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="Masukkan email admin"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                id="password"
                class="mt-1.5 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="checkbox"
                    name="remember"
                >
                <span class="text-sm text-slate-600">{{ __('Ingat saya') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-blue-600 hover:text-blue-700" href="{{ route('password.request') }}">
                    {{ __('Lupa password?') }}
                </a>
            @endif
        </div>

        <div>
            <x-primary-button class="w-full justify-center py-2.5 text-sm">
                {{ __('Masuk') }}
            </x-primary-button>
        </div>
    </form>

    {{-- PKL user login link --}}
    <p class="mt-6 text-center text-sm text-slate-500">
        Bukan Administrator?
        <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">Login Siswa / Guru / Industri</a>
    </p>
</x-guest-layout>
