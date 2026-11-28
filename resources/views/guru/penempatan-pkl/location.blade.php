@extends('layouts.app')

@section('title', 'Atur Lokasi WFO/WFH Siswa Bimbingan')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Guru Pembimbing</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pengaturan Lokasi WFO / WFH</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola mode absensi dan radius lokasi khusus siswa bimbingan</p>
        </div>

        @include('partials.penempatan-location-settings', [
            'penempatanPkl' => $penempatanPkl,
            'updateRoute' => route('guru.penempatan-pkl.location.update', $penempatanPkl),
            'backRoute' => route('guru.penempatan-pkl.index'),
        ])
    </div>
</div>
@endsection
