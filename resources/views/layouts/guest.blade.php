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
<body class="flex min-h-screen bg-[#F7F9FC] font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen w-full">
{{-- Left: Brand panel (hidden on small screens) --}}
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-800 via-blue-700 to-blue-900 p-12 lg:flex">
            {{-- Blue overlay over decorative background --}}
            <div class="absolute inset-0 bg-gradient-to-br from-blue-800/90 via-blue-700/85 to-blue-900/90"></div>

            {{-- Decorative blobs --}}
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-16 h-80 w-80 rounded-full bg-blue-400/20 blur-3xl"></div>

            <div class="relative">
                <div class="flex items-center gap-3">
<img src="{{ asset('images/simongan-logo.png') }}" alt="SIMONGAN Logo" class="h-12 w-12 rounded-xl bg-white/10 object-contain ring-1 ring-white/20">
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-white">SIMONGAN</h1>
                        <p class="text-xs text-blue-100">SMK Negeri 1 Bangsri</p>
                    </div>
                </div>
            </div>

            <div class="relative">
<h2 class="text-3xl font-bold leading-tight text-white">
                    Sistem Monitoring<br>
                    <span class="text-blue-200">Lapangan</span>
                </h2>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-blue-100">
                    Platform terintegrasi untuk mengelola Praktik Kerja Lapangan (PKL)
                    secara digital, transparan, dan real-time.
                </p>
            </div>

            <div class="relative">
                <p class="text-xs font-semibold tracking-wide text-blue-100">SMK Negeri 1 Bangsri</p>
            </div>
        </div>

        {{-- Right: Auth form --}}
        <div class="flex w-full items-center justify-center px-6 lg:w-1/2">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center lg:hidden">
<img src="{{ asset('images/simongan-logo.png') }}" alt="SIMONGAN Logo" class="mx-auto h-14 w-14 object-contain">
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-card">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">
&copy; {{ date('Y') }} SIMONGAN {{ config('app.name', 'Sistem Monitoring Lapangan') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
