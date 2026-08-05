@extends('layouts.app')

@section('title', 'Kelola Jurusan')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800 shadow-sm">{{ session('error') }}</div>
        @endif

        {{-- Header & Tombol Tambah --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Master Data</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Data Jurusan</h1>
                <p class="mt-2 text-sm text-slate-500">Kelola data jurusan</p>
            </div>
            @can('create', App\Models\Jurusan::class)
                <a href="{{ route('admin.jurusan.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Jurusan
                </a>
            @endcan
        </div>

        {{-- Search --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm">
            <form method="GET" action="{{ route('admin.jurusan.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center ps-3.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" name="search" placeholder="Cari jurusan berdasarkan kode atau nama..." value="{{ request('search') }}"
                               class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 ps-10 pe-4 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Cari</button>
                    @if(request('search'))
                        <a href="{{ route('admin.jurusan.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <a href="{{ route('admin.jurusan.index', array_merge(request()->query(), ['sort' => 'kode', 'direction' => request('sort') === 'kode' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-slate-700">
                                    Kode
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                                </a>
                            </th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <a href="{{ route('admin.jurusan.index', array_merge(request()->query(), ['sort' => 'nama', 'direction' => request('sort') === 'nama' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-slate-700">
                                    Nama Jurusan
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                                </a>
                            </th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Deskripsi</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($jurusans as $index => $jurusan)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ $jurusans->firstItem() + $index }}</td>
                                <td class="px-4 py-3.5 text-sm font-medium text-slate-900">{{ $jurusan->kode }}</td>
                                <td class="px-4 py-3.5 text-sm font-medium text-slate-800">{{ $jurusan->nama }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ Str::limit($jurusan->deskripsi, 50) }}</td>
                                <td class="px-4 py-3.5">
                                    @if($jurusan->trashed())
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Dihapus</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Aktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        @can('view', $jurusan)
                                            <a href="{{ route('admin.jurusan.show', $jurusan->id) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Detail</a>
                                        @endcan

                                        @unless($jurusan->trashed())
                                            @can('update', $jurusan)
                                                <a href="{{ route('admin.jurusan.edit', $jurusan->id) }}" class="inline-flex items-center rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">Edit</a>
                                            @endcan

                                            @can('delete', $jurusan)
                                                <form method="POST" action="{{ route('admin.jurusan.destroy', $jurusan->id) }}" class="inline" onsubmit="return confirm('Hapus jurusan {{ $jurusan->nama }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">Hapus</button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('restore', $jurusan)
                                                <form method="POST" action="{{ route('admin.jurusan.restore', $jurusan->id) }}" class="inline" onsubmit="return confirm('Pulihkan jurusan {{ $jurusan->nama }}?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Restore</button>
                                                </form>
                                            @endcan

                                            @can('forceDelete', $jurusan)
                                                <form method="POST" action="{{ route('admin.jurusan.force-delete', $jurusan->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $jurusan->nama }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center rounded-lg bg-red-800 px-2.5 py-1.5 text-xs font-semibold text-red-100 transition hover:bg-red-900">Force Delete</button>
                                                </form>
                                            @endcan
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">
                                            @if(request('search'))
                                                Tidak ada hasil untuk pencarian "{{ request('search') }}"
                                            @else
                                                Belum ada data jurusan.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($jurusans->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $jurusans->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
