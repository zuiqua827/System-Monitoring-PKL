<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIMONGAN') }} — SMK Negeri 1 Bangsri</title>
    <link rel="icon" type="image/png" href="{{ asset('images/simongan-logo.png') }}?v={{ file_exists(public_path('images/simongan-logo.png')) ? filemtime(public_path('images/simongan-logo.png')) : '20260914' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('asset/login/css/login.css') }}">
</head>
<body class="font-sans antialiased text-slate-900">
    <div class="login-page-root">
        {{-- Background school photo & overlay --}}
        <div class="login-bg-layer" style="background-image: url('{{ asset('asset/login/images/background-smk.png') }}');"></div>
        <div class="login-overlay-layer"></div>

        {{-- Top Marquee Branding Banner --}}
        <header class="marquee-banner" aria-label="Branding Banner SMK Negeri 1 Bangsri">
            <div class="marquee-track">
                @for ($i = 0; $i < 4; $i++)
                    <div class="marquee-group" @if($i >= 2) aria-hidden="true" @endif>
                        <div class="marquee-logos-cluster">
                            {{-- 1. Logo SMK Negeri 1 Bangsri --}}
                            <div class="marquee-logo-item" title="SMK Negeri 1 Bangsri">
                                <img src="{{ asset('asset/login/images/logo-smk.png') }}" alt="Logo SMK Negeri 1 Bangsri" loading="eager">
                            </div>

                            {{-- 2. Logo Tut Wuri Handayani --}}
                            <div class="marquee-logo-item" title="Tut Wuri Handayani">
                                <img src="{{ asset('asset/login/images/logo-tut-wuri.png') }}" alt="Logo Tut Wuri Handayani" loading="eager">
                            </div>

                            {{-- 3. Logo Provinsi Jawa Tengah --}}
                            <div class="marquee-logo-item" title="Pemerintah Provinsi Jawa Tengah">
                                <img src="{{ asset('asset/login/images/logo-jateng.png') }}" alt="Logo Provinsi Jawa Tengah" loading="eager">
                            </div>

                            {{-- 4. Logo Kabupaten Jepara --}}
                            <div class="marquee-logo-item" title="Pemerintah Kabupaten Jepara">
                                <img src="{{ asset('asset/login/images/logo-jepara.png') }}" alt="Logo Kabupaten Jepara" loading="eager">
                            </div>

                            {{-- 5. Logo SMK Bisa-Hebat --}}
                            <div class="marquee-logo-item wide-logo" title="SMK Bisa Hebat">
                                <img src="{{ asset('asset/login/images/logo-smk-bisa-hebat.png') }}" alt="Logo SMK Bisa Hebat" loading="eager">
                            </div>
                        </div>

                        {{-- School Identity Text --}}
                        <div class="marquee-school-text">
                            <span class="marquee-school-sub">Sekolah Menengah Kejuruan</span>
                            <span class="marquee-school-name">SMK Negeri 1 Bangsri</span>
                        </div>

                        <span class="marquee-divider"></span>
                    </div>
                @endfor
            </div>
        </header>

        {{-- Center Content Area --}}
        <main class="login-content-area">
            <div class="login-card-wrapper">
                <div class="login-glass-card">
                    {{ $slot }}
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="login-page-footer">
            <p>&copy; {{ date('Y') }} SMK Negeri 1 Bangsri &bull; Hak Cipta Dilindungi Undang-Undang.</p>
            <p class="text-white/70">Sistem Monitoring Praktik Kerja Lapangan (SIMONGAN)</p>
        </footer>
    </div>
</body>
</html>
