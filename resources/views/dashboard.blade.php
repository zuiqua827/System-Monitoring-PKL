@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-card-sm">
            <div class="flex flex-col items-center gap-4 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">You're logged in!</h1>
                    <p class="mt-1 text-sm text-slate-500">Welcome to the System Monitoring PKL application.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
