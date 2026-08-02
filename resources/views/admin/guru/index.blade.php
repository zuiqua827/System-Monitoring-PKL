@extends('layouts.app')

@section('title', 'Kelola Guru')

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
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Data Guru</h1>
                <p class="mt-2 text-sm text-slate-500">Kelola data guru pembimbing PKL</p>
            </div>
            @can('create', App\Models\Guru::class)
                <a href="{{ route('admin.guru.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Guru
                </a>
            @endcan
        </div>

        {{-- Search --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm">
            <form method="GET" action="{{ route('admin.guru.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center ps-3.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" name="search" placeholder="Cari guru berdasarkan NIP atau nama..." value="{{ request('search') }}"
                               class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 ps-10 pe-4 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Cari</button>
                    @if(request('search'))
                        <a href="{{ route('admin.guru.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Reset</a>
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
                                <a href="{{ route('admin.guru.index', array_merge(request()->query(), ['sort' => 'nip', 'direction' => request('sort') === 'nip' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-slate-700">
                                    NIP
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                                </a>
                            </th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <a href="{{ route('admin.guru.index', array_merge(request()->query(), ['sort' => 'nama', 'direction' => request('sort') === 'nama' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-slate-700">
                                    Nama
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                                </a>
                            </th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Jenis Kelamin</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No. HP</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($gurus as $index => $guru)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ $gurus->firstItem() + $index }}</td>
                                <td class="px-4 py-3.5 text-sm font-semibold text-slate-900">{{ $guru->nip ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-sm font-medium text-slate-800">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-sm font-bold text-violet-700">{{ strtoupper(substr($guru->nama, 0, 1)) }}</span>
                                        <span class="font-medium text-slate-900">{{ $guru->nama }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($guru->jenis_kelamin === 'L')
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">Laki-laki</span>
                                    @elseif($guru->jenis_kelamin === 'P')
                                        <span class="inline-flex items-center rounded-full bg-pink-100 px-2.5 py-1 text-xs font-bold text-pink-800">Perempuan</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ $guru->no_hp ?? '-' }}</td>
                                <td class="px-4 py-3.5">
                                    @if($guru->trashed())
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Dihapus</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Aktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        @can('view', $guru)
                                            <a href="{{ route('admin.guru.show', $guru->id) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Detail</a>
                                        @endcan

                                        @unless($guru->trashed())
                                            @can('update', $guru)
                                                <a href="{{ route('admin.guru.edit', $guru->id) }}" class="inline-flex items-center rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">Edit</a>
                                            @endcan

                                            @can('delete', $guru)
                                                <form method="POST" action="{{ route('admin.guru.destroy', $guru->id) }}" class="inline" onsubmit="return confirm('Hapus guru {{ $guru->nama }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">Hapus</button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('restore', $guru)
                                                <form method="POST" action="{{ route('admin.guru.restore', $guru->id) }}" class="inline" onsubmit="return confirm('Pulihkan guru {{ $guru->nama }}?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Restore</button>
                                                </form>
                                            @endcan

                                            @can('forceDelete', $guru)
                                                <form method="POST" action="{{ route('admin.guru.force-delete', $guru->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $guru->nama }}?')">
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
                                <td colspan="7">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14.5c3.2 0 5.8-1.35 5.8-3V6.7L12 9.5 6.2 6.7v4.8c0 1.65 2.6 3 5.8 3Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5 12 2l8 3.5-8 3.5L4 5.5Z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">
                                            @if(request('search'))
                                                Tidak ada hasil untuk pencarian "{{ request('search') }}"
                                            @else
                                                Belum ada data guru.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($gurus->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $gurus->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

