<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Periode PKL') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header & Tombol Tambah --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-sm text-gray-500">Kelola periode pelaksanaan PKL</p>
                </div>
                @can('create', App\Models\PeriodePKL::class)
                    <a href="{{ route('admin.periode-pkl.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        + Tambah Periode PKL
                    </a>
                @endcan
            </div>

            {{-- Search --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('admin.periode-pkl.index') }}" class="flex items-center gap-3">
                    <div class="flex-1">
                        <input type="text" name="search" placeholder="Cari periode PKL..." value="{{ request('search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.periode-pkl.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600 w-16">No</th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('admin.periode-pkl.index', array_merge(request()->query(), ['sort' => 'nama', 'direction' => request('sort') === 'nama' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Nama Periode
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('admin.periode-pkl.index', array_merge(request()->query(), ['sort' => 'tahun_ajaran', 'direction' => request('sort') === 'tahun_ajaran' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Tahun Ajaran
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('admin.periode-pkl.index', array_merge(request()->query(), ['sort' => 'tanggal_mulai', 'direction' => request('sort') === 'tanggal_mulai' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Tanggal Mulai
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('admin.periode-pkl.index', array_merge(request()->query(), ['sort' => 'tanggal_selesai', 'direction' => request('sort') === 'tanggal_selesai' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Tanggal Selesai
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('admin.periode-pkl.index', array_merge(request()->query(), ['sort' => 'status', 'direction' => request('sort') === 'status' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Status
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($periodePkls as $index => $periodePkl)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-gray-500">{{ $periodePkls->firstItem() + $index }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $periodePkl->nama }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $periodePkl->tahun_ajaran }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $periodePkl->tanggal_mulai ? $periodePkl->tanggal_mulai->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $periodePkl->tanggal_selesai ? $periodePkl->tanggal_selesai->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if($periodePkl->status === 'Aktif')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                        @elseif($periodePkl->status === 'Persiapan')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Persiapan</span>
                                        @elseif($periodePkl->status === 'Selesai')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Selesai</span>
                                        @elseif($periodePkl->status === 'Ditutup')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Ditutup</span>
                                        @else
                                            <span class="text-gray-400">{{ $periodePkl->status }}</span>
                                        @endif
                                        @if($periodePkl->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 ml-1">Dihapus</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @can('view', $periodePkl)
                                                <a href="{{ route('admin.periode-pkl.show', $periodePkl->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-indigo-600 hover:text-indigo-900">Detail</a>
                                            @endcan

                                            @unless($periodePkl->trashed())
                                                @can('update', $periodePkl)
                                                    <a href="{{ route('admin.periode-pkl.edit', $periodePkl->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-yellow-600 hover:text-yellow-900">Edit</a>
                                                @endcan

                                                @can('delete', $periodePkl)
                                                    <form method="POST" action="{{ route('admin.periode-pkl.destroy', $periodePkl->id) }}" class="inline" onsubmit="return confirm('Hapus periode {{ $periodePkl->nama }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-red-600 hover:text-red-900">Hapus</button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('restore', $periodePkl)
                                                    <form method="POST" action="{{ route('admin.periode-pkl.restore', $periodePkl->id) }}" class="inline" onsubmit="return confirm('Pulihkan periode {{ $periodePkl->nama }}?')">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-green-600 hover:text-green-900">Restore</button>
                                                    </form>
                                                @endcan

                                                @can('forceDelete', $periodePkl)
                                                    <form method="POST" action="{{ route('admin.periode-pkl.force-delete', $periodePkl->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $periodePkl->nama }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-red-800 hover:text-red-900">Force Delete</button>
                                                    </form>
                                                @endcan
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        @if(request('search'))
                                            Tidak ada hasil untuk pencarian "{{ request('search') }}"
                                        @else
                                            Belum ada data periode PKL.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- Pagination --}}
            @if($periodePkls->hasPages())
                <div class="p-4 border-t border-gray-200">
                    {{ $periodePkls->appends(request()->query())->links() }}
                </div>
            @endif
    </div>
</x-app-layout>
