@extends('layouts.app')

@section('title', 'Pengaturan Lokasi Absensi WFO/WFH')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Penempatan PKL</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pengaturan Lokasi WFO / WFH</h1>
                <p class="mt-1 text-sm text-slate-500">Atur mode kerja dan validasi geofence GPS siswa PKL</p>
            </div>
        </div>

        @include('partials.penempatan-location-settings', [
            'penempatanPkl' => $penempatanPkl,
            'updateRoute' => route('admin.penempatan-pkl.location.update', $penempatanPkl),
            'backRoute' => route('admin.penempatan-pkl.show', $penempatanPkl),
        ])
    </div>
</div>
@endsection
