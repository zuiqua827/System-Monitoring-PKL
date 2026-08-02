@extends('layouts.app')

@section('title', 'Edit Siswa')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        {{-- Page header --}}
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Siswa</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Edit Siswa: {{ $siswa->nama }}</h1>
            <p class="mt-2 text-sm text-slate-500">Perbarui data siswa peserta PKL</p>
        </div>

        @include('admin.siswa._form', [
            'siswa' => $siswa,
            'route' => route('admin.siswa.update', $siswa->id),
            'method' => 'PUT',
        ])
    </div>
</div>
@endsection
