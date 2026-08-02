@extends('layouts.app')

@section('title', 'Edit Penilaian')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Penilaian</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Edit Penilaian</h1>
            <p class="mt-2 text-sm text-slate-500">Perbarui penilaian PKL siswa bimbingan</p>
        </div>
        @include('guru.penilaian._form', [
            'penilaian' => $penilaian,
            'route' => route('guru.penilaian.update', $penilaian->id),
            'method' => 'PUT',
            'isFinal' => $penilaian->status === 'final',
        ])
    </div>
</div>
@endsection
