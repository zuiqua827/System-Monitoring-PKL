<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title', config('app.name', 'SIMONGAN'))</title>
<link rel="icon" type="image/png" href="{{ asset('images/simongan-logo.png') }}?v={{ file_exists(public_path('images/simongan-logo.png')) ? filemtime(public_path('images/simongan-logo.png')) : '20260914' }}">

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

        <div x-data="{ sidebarOpen: false, profileOpen: false }" @keydown.escape.window="sidebarOpen = false" class="min-h-screen flex bg-slate-50">
            
            {{-- Main Content --}}
            <div class="flex-1 min-w-0 flex flex-col lg:pl-[280px]">
                
                @include('layouts.navigation')

                {{-- Page Content --}}
                <main class="flex-1 overflow-x-hidden">
                    <div class="mx-auto w-full max-w-[1600px]">
                        {{-- Page Heading (only for @extends layouts usage) --}}
                        @isset($header)
                            <div class="px-4 pt-8 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        @endisset

                        @isset($slot)
                            <div class="p-4 sm:p-6 lg:p-8">
                                {{ $slot }}
                            </div>
                        @else
                            @yield('content')
                        @endisset
                    </div>
                </main>
            </div>
        </div>
        <x-custom-dialogs />
        @stack('scripts')
    </body>
</html>

