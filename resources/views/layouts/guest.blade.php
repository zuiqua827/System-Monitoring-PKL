<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ config('app.name', 'SIMONGAN') }}</title>
<link rel="icon" type="image/png" href="{{ asset('images/simongan-logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen bg-cover bg-center bg-no-repeat bg-fixed font-sans text-slate-900 antialiased" style="background-image: url('{{ asset('asset/login/background-smk.png') }}');">
    <div class="flex min-h-screen w-full">
{{-- Left: Brand panel (hidden on small screens) --}}
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden p-12 lg:flex">

            <div class="relative">
                <div class="flex items-center gap-3">
<img src="{{ asset('images/simongan-logo.png') }}" alt="SIMONGAN Logo" class="h-12 w-12 rounded-xl bg-white/10 object-contain ring-1 ring-white/20">
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-white drop-shadow-lg">SIMONGAN</h1>
                        <p class="text-xs text-white drop-shadow-lg">SMK Negeri 1 Bangsri</p>
                    </div>
                </div>
            </div>

            <div class="relative">
<h2 class="text-3xl font-bold leading-tight text-white drop-shadow-lg">
                    Sistem Monitoring<br>
                    <span class="text-white">Lapangan</span>
                </h2>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-white drop-shadow-lg">
                    Platform terintegrasi untuk mengelola Praktik Kerja Lapangan (PKL)
                    secara digital, transparan, dan real-time.
                </p>
            </div>

            <div class="relative">
                <p class="text-xs font-semibold tracking-wide text-white drop-shadow-lg">SMK Negeri 1 Bangsri</p>
            </div>
        </div>

        {{-- Right: Auth form --}}
        <div class="flex w-full items-center justify-center px-6 lg:w-1/2">
            <div class="w-full max-w-md lg:max-w-[560px]">
                <div class="mb-8 text-center lg:hidden">
<img src="{{ asset('images/simongan-logo.png') }}" alt="SIMONGAN Logo" class="mx-auto h-14 w-14 object-contain">
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white/60 backdrop-blur-md p-8 shadow-card">
                    {{ $slot }}
                </div>

                @if(!request()->routeIs('login') && !request()->routeIs('admin.login'))
                <p class="mt-6 text-center text-xs text-slate-400 bg-white/80 lg:bg-transparent px-2 py-1 rounded inline-block lg:p-0">
&copy; {{ date('Y') }} SIMONGAN {{ config('app.name', 'Sistem Monitoring Lapangan') }}. All rights reserved.
                </p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
