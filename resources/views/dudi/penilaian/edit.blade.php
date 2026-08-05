@extends('layouts.app')

@section('title', 'Edit Penilaian Siswa')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Penilaian</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Edit Penilaian</h1>
                <p class="mt-2 text-sm text-slate-500">Edit penilaian untuk siswa PKL</p>
            </div>
            <a href="{{ route('dudi.penilaian.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>
        @include('dudi.penilaian._form', [
            'penilaian' => $penilaian,
            'route' => route('dudi.penilaian.update', $penilaian->id),
            'method' => 'PUT',
            'isFinal' => false,
        ])
    </div>
</div>
@endsection
