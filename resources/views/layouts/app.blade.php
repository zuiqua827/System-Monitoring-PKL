<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'SIPKL'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- Inter font for the design system --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased text-slate-900">
        <div class="min-h-screen bg-background">
            @php
                // Derive a page title from common sources:
                // 1. $pageTitle prop (explicitly passed)
                // 2. $title variable
                // 3. $header slot (x-app-layout child views)
                // 4. @yield('title') (layouts.app @extends child views)
                $__pageTitle = $pageTitle ?? null;
                if (!$__pageTitle && isset($title)) {
                    $__pageTitle = $title;
                }
                if (!$__pageTitle && isset($header)) {
                    $__pageTitle = trim(strip_tags((string) $header));
                }
                if (!$__pageTitle) {
                    $__pageTitle = trim(strip_tags((string) $__env->yieldContent('title')));
                }
                if (!$__pageTitle || trim($__pageTitle) === '') {
                    $__pageTitle = 'Dashboard';
                }
            @endphp

            @include('layouts.navigation')

            {{-- Page Heading (only for @extends layouts usage) --}}
            @isset($header)
                <header class="lg:pl-[280px] pt-[72px]">
                    <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- Page Content --}}
            <main class="lg:pl-[280px] pt-[72px]">
                @isset($slot)
                    <div class="px-4 py-8 sm:px-6 lg:px-8">
                        {{ $slot }}
                    </div>
                @else
                    @yield('content')
                @endisset
            </main>
        </div>
        @stack('scripts')
    </body>
</html>

