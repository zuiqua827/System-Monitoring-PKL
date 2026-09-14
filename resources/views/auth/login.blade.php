<x-guest-layout>
    {{-- Header Branding SIMONGAN inside card --}}
    <div class="login-brand-header">
        <div class="login-brand-logo-wrap">
            <img src="{{ asset('images/simongan-logo.png') }}" alt="Logo SIMONGAN" loading="eager">
        </div>
        <h1 class="login-brand-title">SIMONGAN</h1>
        <p class="login-brand-subtitle">Sistem Monitoring Praktik Kerja Lapangan</p>
        <!-- <div class="login-portal-badge">Portal Akses SMKN 1 Bangsri</div> -->
    </div>

    {{-- Session Status & Global Errors --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <x-input-error :messages="$errors->get('login')" class="mb-4 text-center font-semibold" />

    {{-- Role Tabs & Interactive Login Form --}}
    <div x-data="{
        tab: '{{ old('role', 'siswa') }}',
        showPassword: false,
        rawInput: '{{ old('email', '') }}',
        formatSiswaInput() {
            if (this.tab === 'siswa') {
                let trimmed = this.rawInput.trim();
                if (/^\d+$/.test(trimmed)) {
                    this.rawInput = trimmed + '@smkn1bangsri.sch.id';
                }
            }
        }
    }">
        {{-- Role Selector Tabs --}}
        <div class="role-tabs-wrap" role="tablist" aria-label="Pilih Tipe Akun">
            <button
                type="button"
                role="tab"
                :aria-selected="tab === 'siswa'"
                @click="tab = 'siswa'"
                :class="tab === 'siswa' ? 'role-tab-btn is-active' : 'role-tab-btn'"
            >
                Siswa
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="tab === 'guru'"
                @click="tab = 'guru'"
                :class="tab === 'guru' ? 'role-tab-btn is-active' : 'role-tab-btn'"
            >
                Guru
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="tab === 'dudi'"
                @click="tab = 'dudi'"
                :class="tab === 'dudi' ? 'role-tab-btn is-active' : 'role-tab-btn'"
            >
                DUDI
            </button>
        </div>

        <form method="POST" action="{{ route('login') }}" @submit="formatSiswaInput">
            @csrf

            {{-- Role sync for backend validation --}}
            <input type="hidden" name="role" :value="tab">

            {{-- Identifier Field (NIS / Email) --}}
            <div class="login-form-group">
                <label for="email" class="login-label">
                    <span x-show="tab === 'siswa'">Email NIS</span>
                    <span x-show="tab === 'guru'" x-cloak>Email Guru</span>
                    <span x-show="tab === 'dudi'" x-cloak>Email DUDI / Industri</span>
                </label>
                <div class="login-input-shell">
                    <input
                        id="email"
                        class="login-input"
                        type="text"
                        name="email"
                        x-model="rawInput"
                        autocomplete="username"
                        :placeholder="tab === 'siswa' ? ' Masukan Email NIS' : (tab === 'guru' ? 'Masukkan Email Guru' : 'Masukkan Email Industri')"
                        required
                    />
                </div>
                <p class="login-field-helper">
                    <span x-show="tab === 'siswa'">Gunakan Email NIS siswa</span>
                    <span x-show="tab === 'guru'" x-cloak>Gunakan email resmi yang terdaftar untuk guru</span>
                    <span x-show="tab === 'dudi'" x-cloak>Gunakan email perwakilan industri yang terdaftar</span>
                </p>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                <x-input-error :messages="$errors->get('nis')" class="mt-1.5" />
            </div>

            {{-- Password Field with Show/Hide Toggle --}}
            <div class="login-form-group">
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="login-label !mb-0">
                        Kata Sandi
                    </label>

                    {{-- Forgot password for Guru / DUDI --}}
                    <span x-show="tab !== 'siswa'" x-cloak>
                        @if (Route::has('password.request'))
                            <a
                                class="text-xs font-semibold text-blue-600 hover:text-blue-700 transition"
                                href="{{ route('password.request') }}"
                            >
                                Lupa Password?
                            </a>
                        @endif
                    </span>
                </div>

                <div class="login-input-shell">
                    <input
                        id="password"
                        class="login-input has-icon-right"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                    />

                    {{-- Toggle Visibility Button --}}
                    <button
                        type="button"
                        class="password-toggle-btn"
                        @click="showPassword = !showPassword"
                        :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                    >
                        {{-- Eye Open Icon --}}
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        {{-- Eye Slash Icon --}}
                        <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            {{-- Remember Me --}}
            <div class="flex items-center justify-between mt-3 mb-5">
                <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input
                        id="remember_me"
                        type="checkbox"
                        class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500/30 accent-[#2563EB] cursor-pointer"
                        name="remember"
                    >
                    <span class="text-xs font-medium text-slate-600">Ingat Sesi Saya</span>
                </label>
            </div>

            {{-- Submit Action Button --}}
            <div>
                <button type="submit" class="login-submit-btn">
                    <span>Masuk</span>
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>
