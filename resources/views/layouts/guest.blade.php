<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistem Monitoring PKL') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen bg-[#F7F9FC] font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen w-full">
        {{-- Left: Brand panel --}}
        <div class="relative hidden w-1/2 flex-col justify-center overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 p-12 lg:flex">
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 h-72 w-72 rounded-full bg-blue-400/10 blur-3xl"></div>

            <div class="relative">
                <div class="mb-8 flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-xl font-extrabold text-white shadow-lg">P</span>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-white">PKL-SYSTEM</h1>
                        <p class="text-xs text-slate-400">Monitoring Console</p>
                    </div>
                </div>

                <h2 class="text-3xl font-bold leading-tight text-white">
                    Sistem Monitoring<br>
                    <span class="text-blue-400">Praktek Kerja Lapangan</span>
                </h2>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
                    Platform terpadu untuk memantau, mengevaluasi, dan mengelola kegiatan PKL siswa secara real-time.
                </p>

                <div class="mt-10 grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-700/50 bg-slate-900/50 p-4 backdrop-blur">
                        <p class="text-2xl font-bold text-white">4</p>
                        <p class="text-xs text-slate-400">Role Pengguna</p>
                    </div>
                    <div class="rounded-xl border border-slate-700/50 bg-slate-900/50 p-4 backdrop-blur">
                        <p class="text-2xl font-bold text-white">Real-time</p>
                        <p class="text-xs text-slate-400">Monitoring</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Auth form --}}
        <div class="flex w-full items-center justify-center px-6 lg:w-1/2">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center lg:hidden">
                    <div class="mb-4 inline-flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-sm font-extrabold text-white">P</span>
                        <span class="text-lg font-bold text-slate-900">PKL-SYSTEM</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-card">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Sistem Monitoring PKL') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
