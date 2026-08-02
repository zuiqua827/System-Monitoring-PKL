@extends('layouts.app')

@section('title', 'Tambah Aktivitas')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Aktivitas</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Tambah Aktivitas</h1>
            <p class="mt-2 text-sm text-slate-500">Tambahkan data aktivitas harian PKL siswa</p>
        </div>
        @include('admin.aktivitas._form', [
            'aktivitas' => null,
            'route' => route('admin.aktivitas.store'),
            'method' => 'POST',
        ])
    </div>
</div>
@endsection
