<x-guest-layout>
<!-- Logo -->
    <div class="mb-6 flex justify-center">
<img src="{{ asset('images/simongan-logo.png') }}" alt="SIMONGAN Logo" class="h-16 w-16 object-contain">
    </div>

    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-slate-900">Selamat Datang</h2>
        <p class="mt-1 text-sm text-slate-500">Silakan masuk untuk melanjutkan ke dashboard.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Role Tabs --}}
    <div x-data="{ tab: 'siswa' }">
        <div class="mb-5 grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1">
            <button type="button" @click="tab = 'siswa'" :class="tab === 'siswa' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-lg px-3 py-2 text-sm font-semibold transition">
                Siswa
            </button>
            <button type="button" @click="tab = 'guru'" :class="tab === 'guru' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-lg px-3 py-2 text-sm font-semibold transition">
                Guru
            </button>
            <button type="button" @click="tab = 'dudi'" :class="tab === 'dudi' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-lg px-3 py-2 text-sm font-semibold transition">
                Industri
            </button>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
@csrf
            {{-- Role is synced to the active tab via Alpine, but has a static
                 fallback "siswa" so the field is never empty (which would fail
                 validation and show a misleading "credentials" error). --}}
            <input type="hidden" name="role" value="siswa" x-bind:value="tab">

            {{-- Siswa fields --}}
            <div x-show="tab === 'siswa'" x-cloak>
                <div>
                    <x-input-label for="nis" :value="__('NIS')" />
                    <x-text-input
                        id="nis"
                        class="mt-1.5 block w-full"
                        type="text"
                        name="nis"
                        :value="old('nis')"
                        autofocus
                        autocomplete="username"
                        placeholder="Masukkan NIS"
                        x-bind:required="tab === 'siswa'"
                    />
                    <x-input-error :messages="$errors->get('nis')" class="mt-2" />
                </div>
            </div>

            {{-- Guru / DUDI email field --}}
            <div x-show="tab !== 'siswa'" x-cloak>
                <div>
                    <x-input-label for="email" :value="__('Email')" />
<x-text-input
                        id="email"
                        class="mt-1.5 block w-full"
                        type="email"
                        name="email"
                        :value="old('email')"
                        autofocus
                        autocomplete="username"
                        x-bind:placeholder="tab === 'guru' ? 'Masukkan Email Guru' : 'Masukkan Email Industri'"
                        x-bind:required="tab !== 'siswa'"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
            </div>

            {{-- Password field (shared) --}}
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

            {{-- Remember Me + Forgot Password --}}
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

                {{-- Forgot password only for Guru/DUDI --}}
                <span x-show="tab !== 'siswa'" x-cloak>
                    @if (Route::has('password.request'))
                        <a class="text-sm font-medium text-blue-600 hover:text-blue-700" href="{{ route('password.request') }}">
                            {{ __('Lupa password?') }}
                        </a>
                    @endif
                </span>
            </div>

            <div>
                <x-primary-button class="w-full justify-center py-2.5 text-sm">
                    {{ __('Masuk') }}
                </x-primary-button>
            </div>
        </form>
</div>
</x-guest-layout>
