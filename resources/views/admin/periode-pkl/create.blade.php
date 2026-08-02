@extends('layouts.app')

@section('title', 'Tambah Periode PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Periode PKL</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Tambah Periode PKL</h1>
            <p class="mt-2 text-sm text-slate-500">Tambahkan periode pelaksanaan PKL baru</p>
        </div>
        @include('admin.periode-pkl._form', [
            'periodePkl' => null,
            'route' => route('admin.periode-pkl.store'),
            'method' => 'POST',
        ])
    </div>
</div>
@endsection
