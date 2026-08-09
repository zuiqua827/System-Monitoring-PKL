@extends('layouts.app')

@section('title', 'Pengaturan Akun')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        {{-- Header --}}
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Pengaturan Akun</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pengaturan Akun</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">Kelola informasi profil, foto, dan keamanan akun Anda.</p>
        </div>

        @if (session('success'))
            <div class="alert-success mb-6">
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert-error mb-6">
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-12">
            {{-- Left column: avatar + identity --}}
            <div class="lg:col-span-4">
                <div class="card p-6">
                    @include('account.partials.avatar', ['user' => $user])
                </div>
            </div>

            {{-- Right column: profile form + security --}}
            <div class="space-y-6 lg:col-span-8">
                <div class="card p-6">
                    @include('account.partials.account-info', ['user' => $user, 'role' => $role])
                </div>

                <div class="card p-6">
                    @include('account.partials.change-password')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
