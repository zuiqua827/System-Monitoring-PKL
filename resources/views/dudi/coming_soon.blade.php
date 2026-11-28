@extends('layouts.app')

@section('title', $title ?? 'Coming Soon')

@section('content')
<div class="px-4 py-16 sm:px-6 lg:px-8 text-center flex flex-col items-center justify-center min-h-[50vh]">
    <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
    </div>
    <h1 class="text-3xl font-bold text-slate-900">{{ $title ?? 'Fitur dalam Pengembangan' }}</h1>
    <p class="mt-4 text-base text-slate-500 max-w-md mx-auto">
        Modul ini sedang dalam tahap pengembangan dan akan tersedia pada update berikutnya. 
    </p>
    <a href="{{ route('dudi.dashboard') }}" class="mt-8 inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
        Kembali ke Dashboard
    </a>
</div>
@endsection
