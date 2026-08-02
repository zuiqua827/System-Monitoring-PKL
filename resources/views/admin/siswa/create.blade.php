@extends('layouts.app')

@section('title', 'Tambah Siswa')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        {{-- Page header --}}
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Siswa</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Tambah Siswa</h1>
            <p class="mt-2 text-sm text-slate-500">Tambahkan data siswa baru peserta PKL</p>
        </div>

        @include('admin.siswa._form', [
            'siswa' => null,
            'route' => route('admin.siswa.store'),
            'method' => 'POST',
        ])
    </div>
</div>
@endsection
