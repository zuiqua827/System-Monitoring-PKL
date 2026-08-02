@extends('layouts.app')

@section('title', 'Tambah Absensi')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Absensi</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Tambah Absensi</h1>
            <p class="mt-2 text-sm text-slate-500">Tambahkan data absensi harian siswa</p>
        </div>
        @include('admin.absensi._form', [
            'absensi' => null,
            'route' => route('admin.absensi.store'),
            'method' => 'POST',
        ])
    </div>
</div>
@endsection
