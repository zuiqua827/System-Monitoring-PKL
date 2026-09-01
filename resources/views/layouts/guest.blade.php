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
    <style>
        .guest-auth-main {
            display: flex;
            width: 100%;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .guest-auth-shell {
            display: flex;
            width: 100%;
            max-width: 96rem;
            overflow: hidden;
            border: 1px solid rgb(255 255 255 / 0.28);
            border-radius: 1.75rem;
            background: rgb(15 23 42 / 0.38);
            box-shadow: 0 28px 80px rgb(15 23 42 / 0.32);
            backdrop-filter: blur(18px) saturate(125%);
            -webkit-backdrop-filter: blur(18px) saturate(125%);
        }

        .guest-auth-brand-panel,
        .guest-auth-form-panel {
            display: flex;
            flex-direction: column;
        }

        .guest-auth-brand-panel {
            justify-content: space-between;
            width: 50%;
            padding: 3rem;
            background: linear-gradient(135deg, rgb(15 23 42 / 0.32), rgb(30 64 175 / 0.22));
        }

        .guest-auth-form-panel {
            align-items: center;
            justify-content: center;
            width: 50%;
            padding: 3rem;
            background: rgb(255 255 255 / 0.12);
        }

        .guest-auth-card {
            width: 100%;
            max-width: 33rem;
        }

        .guest-auth-card h2 {
            color: rgb(255 255 255);
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .guest-auth-card .text-slate-500,
        .guest-auth-card .text-slate-600,
        .guest-auth-card .text-slate-400 {
            color: rgb(255 255 255 / 0.76);
        }

        .guest-auth-card .label {
            display: block;
            color: rgb(255 255 255 / 0.94);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .guest-auth-card .input {
            border-color: rgb(255 255 255 / 0.28);
            background: rgb(255 255 255 / 0.14);
            color: rgb(255 255 255);
            box-shadow: none;
        }

        .guest-auth-card .input::placeholder {
            color: rgb(255 255 255 / 0.58);
        }

        .guest-auth-card .input:focus {
            border-color: rgb(255 255 255 / 0.72);
            background: rgb(255 255 255 / 0.2);
            --tw-ring-color: rgb(255 255 255 / 0.22);
        }

        .guest-auth-card .bg-slate-100 {
            background: rgb(15 23 42 / 0.28);
        }

        .guest-auth-card .bg-white {
            background: rgb(255 255 255 / 0.94);
        }

        .guest-auth-card input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: #ffffff;
        }

        .guest-auth-card .btn-primary {
            min-height: 3.25rem;
            border: 1px solid rgb(255 255 255 / 0.55);
            background: rgb(255 255 255);
            color: #1e3a8a;
            box-shadow: 0 12px 26px rgb(15 23 42 / 0.2);
        }

        .guest-auth-card .btn-primary:hover {
            background: rgb(239 246 255);
        }

        .guest-auth-card a {
            color: rgb(219 234 254);
        }

        .guest-auth-card a:hover {
            color: rgb(255 255 255);
        }

        @media (max-width: 1023px) {
            .guest-auth-brand-panel {
                display: none;
            }

            .guest-auth-form-panel {
                width: 100%;
                padding: 1.5rem;
                background: transparent;
            }
        }

        @media (min-width: 1024px) {
            .guest-auth-main {
                padding: 3rem;
            }

            .guest-auth-card {
                width: 25rem;
                max-width: 100%;
            }

            .guest-auth-shell {
                min-height: min(45rem, calc(100vh - 6rem));
                border-radius: 2rem;
            }
        }
    </style>
</head>
<body class="guest-auth-page flex min-h-screen bg-cover bg-center bg-no-repeat bg-fixed font-sans text-slate-900 antialiased" style="background-image: url('{{ asset('asset/login/background-smk.png') }}');">
    <main class="guest-auth-main">
        <div class="guest-auth-shell">
            {{-- Brand panel (desktop) --}}
            <section class="guest-auth-brand-panel">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/simongan-logo.png') }}" alt="SIMONGAN Logo" class="h-12 w-12 rounded-xl bg-white/15 object-contain p-1 ring-1 ring-white/30">
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-white drop-shadow-lg">SIMONGAN</h1>
                        <p class="text-xs text-white/85 drop-shadow-lg">SMK Negeri 1 Bangsri</p>
                    </div>
                </div>

                <div>
                    <h2 class="text-4xl font-bold leading-tight text-white drop-shadow-lg">
                    Sistem Monitoring<br>
                    <span class="text-white">Lapangan</span>
                    </h2>
                    <p class="mt-5 max-w-md text-base leading-relaxed text-white/90 drop-shadow-lg">
                        Platform terintegrasi untuk mengelola Praktik Kerja Lapangan (PKL)
                        secara digital, transparan, dan real-time.
                    </p>
                </div>

                <p class="text-xs font-semibold tracking-wide text-white/90 drop-shadow-lg">SMK Negeri 1 Bangsri</p>
            </section>

            {{-- Auth form --}}
            <section class="guest-auth-form-panel">
                <div class="guest-auth-card">
                    <div class="mb-7 text-center lg:hidden">
                        <img src="{{ asset('images/simongan-logo.png') }}" alt="SIMONGAN Logo" class="mx-auto h-14 w-14 rounded-2xl bg-white/15 p-1 object-contain ring-1 ring-white/30">
                        <p class="mt-3 text-lg font-bold tracking-tight text-white">SIMONGAN</p>
                        <p class="mt-0.5 text-xs text-white/80">SMK Negeri 1 Bangsri</p>
                    </div>
                </div>

                    {{ $slot }}

                @if(!request()->routeIs('login') && !request()->routeIs('admin.login'))
                    <p class="mt-6 text-center text-xs text-white/70">
                        &copy; {{ date('Y') }} SIMONGAN {{ config('app.name', 'Sistem Monitoring Lapangan') }}. All rights reserved.
                    </p>
                @endif
                </div>
            </section>
        </div>
    </main>
</body>
</html>
