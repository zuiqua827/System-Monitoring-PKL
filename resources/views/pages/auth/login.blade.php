<x-layouts::auth :title="__('Log in')">
    <div class="grid min-h-[calc(100vh-2rem)] grid-cols-1 overflow-hidden rounded-[32px] bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] lg:grid-cols-[1.4fr_1fr]">
        <div class="relative hidden overflow-hidden rounded-t-[32px] bg-slate-950 p-10 text-white lg:flex">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(59,130,246,0.20),_transparent_35%),linear-gradient(180deg,#0f172a_00,#111827_100%)]"></div>
            <div class="relative z-10 flex h-full flex-col justify-between">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-lg font-semibold text-white" wire:navigate>
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10 text-white">P</span>
                        {{ config('app.name', 'PKL-SYSTEM') }}
                    </a>
                </div>

                <div class="space-y-6">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-sky-300">Sistem Monitoring PKL</p>
                        <h2 class="mt-3 text-4xl font-semibold leading-tight">Membangun karir masa depan lewat internship berbasis data.</h2>
                        <p class="mt-4 max-w-xl text-sm leading-6 text-slate-300">Platform terintegrasi untuk mengelola program Praktik Kerja Lapangan dengan transparansi, efisiensi, dan kolaborasi antara industri dan akademisi.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-3xl bg-white/10 p-5">
                            <p class="text-sm font-semibold text-slate-200">Dashboard Role</p>
                            <p class="mt-2 text-2xl font-bold">Siswa, Guru, Industri</p>
                        </div>
                        <div class="rounded-3xl bg-white/10 p-5">
                            <p class="text-sm font-semibold text-slate-200">Keamanan</p>
                            <p class="mt-2 text-2xl font-bold">SSL & Otentikasi Aman</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/10 bg-white/5 p-5 text-sm text-slate-300">
                    <p class="font-semibold text-slate-100">Bergabung dengan 2.400+ siswa PKL lainnya</p>
                    <p class="mt-2">Akses absensi, jurnal, nilai, dan notifikasi dari satu dashboard modern.</p>
                </div>
            </div>
        </div>

        <div class="p-6 sm:p-10 bg-slate-50">
            <div class="mx-auto max-w-md rounded-[32px] border border-slate-200 bg-white p-8 shadow-xl">
                <div x-data="{ role: 'Siswa' }" class="space-y-8">
                    <div class="space-y-2 text-center">
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-sky-600">Selamat datang</p>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Masuk ke dalam sistem</h1>
                        <p class="text-sm text-slate-500">Pilih peran untuk memulai sesi Anda.</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-2 shadow-sm">
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(['Siswa','Guru','Industri'] as $tab)
                                <button
                                    type="button"
                                    x-on:click="role = '{{ $tab }}'"
                                    :class="role === '{{ $tab }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:bg-white hover:text-slate-900'"
                                    class="rounded-2xl px-4 py-3 text-sm font-semibold transition"
                                >
                                    {{ $tab }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <x-auth-session-status class="text-center" :status="session('status')" />

                    <x-passkey-verify />

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                        @csrf
                        <input type="hidden" name="role" x-bind:value="role">

                        <flux:input
                            name="email"
                            :label="__('Email atau NIS')"
                            :value="old('email')"
                            type="text"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="Masukkan email atau NIS"
                        />

                        <div class="relative">
                            <flux:input
                                name="password"
                                :label="__('Password')"
                                type="password"
                                required
                                autocomplete="current-password"
                                :placeholder="__('Password')"
                                viewable
                            />

                            @if (Route::has('password.request'))
                                <flux:link class="absolute top-0 end-0 text-sm text-sky-600 hover:text-sky-700" :href="route('password.request')" wire:navigate>
                                    {{ __('Lupa password?') }}
                                </flux:link>
                            @endif
                        </div>

                        <flux:checkbox name="remember" :label="__('Ingat saya')" :checked="old('remember')" />

                        <flux:button variant="primary" type="submit" class="w-full py-3 text-sm font-semibold">
                            {{ __('Masuk Ke Sistem') }}
                        </flux:button>
                    </form>

                    <div class="text-center text-sm text-slate-500">
                        <span>Belum punya akun? </span>
                        <flux:link :href="route('register')" wire:navigate class="font-semibold text-slate-900 hover:text-sky-600">Hubungi Super Admin</flux:link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::auth>
