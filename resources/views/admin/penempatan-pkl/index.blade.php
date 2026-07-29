<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Penempatan PKL') }}
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
                    <p class="text-sm text-gray-500">Kelola penempatan siswa PKL ke DUDI</p>
                </div>
                @can('create', App\Models\PenempatanPKL::class)
                    <a href="{{ route('admin.penempatan-pkl.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        + Tambah Penempatan
                    </a>
                @endcan
            </div>

            {{-- Search --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('admin.penempatan-pkl.index') }}" class="flex items-center gap-3">
                    <div class="flex-1">
                        <input type="text" name="search" placeholder="Cari siswa, guru, atau perusahaan..." value="{{ request('search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.penempatan-pkl.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
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
                                <th class="px-4 py-3 font-medium text-gray-600">Siswa</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Guru Pembimbing</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Perusahaan</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Periode</th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('admin.penempatan-pkl.index', array_merge(request()->query(), ['sort' => 'status', 'direction' => request('sort') === 'status' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Status
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($penempatanPkls as $index => $penempatanPkl)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-gray-500">{{ $penempatanPkls->firstItem() + $index }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $penempatanPkl->siswa?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $penempatanPkl->guru?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $penempatanPkl->dudi?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $penempatanPkl->periodePKL?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if($penempatanPkl->status === 'aktif')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                        @elseif($penempatanPkl->status === 'pending')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                        @elseif($penempatanPkl->status === 'selesai')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Selesai</span>
                                        @elseif($penempatanPkl->status === 'dibatalkan')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Dibatalkan</span>
                                        @else
                                            <span class="text-gray-400">{{ $penempatanPkl->status }}</span>
                                        @endif
                                        @if($penempatanPkl->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 ml-1">Dihapus</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @can('view', $penempatanPkl)
                                                <a href="{{ route('admin.penempatan-pkl.show', $penempatanPkl->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-indigo-600 hover:text-indigo-900">Detail</a>
                                            @endcan

                                            @unless($penempatanPkl->trashed())
                                                @can('update', $penempatanPkl)
                                                    <a href="{{ route('admin.penempatan-pkl.edit', $penempatanPkl->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-yellow-600 hover:text-yellow-900">Edit</a>
                                                @endcan

                                                @can('delete', $penempatanPkl)
                                                    <form method="POST" action="{{ route('admin.penempatan-pkl.destroy', $penempatanPkl->id) }}" class="inline" onsubmit="return confirm('Hapus penempatan PKL ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-red-600 hover:text-red-900">Hapus</button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('restore', $penempatanPkl)
                                                    <form method="POST" action="{{ route('admin.penempatan-pkl.restore', $penempatanPkl->id) }}" class="inline" onsubmit="return confirm('Pulihkan penempatan PKL ini?')">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-green-600 hover:text-green-900">Restore</button>
                                                    </form>
                                                @endcan

                                                @can('forceDelete', $penempatanPkl)
                                                    <form method="POST" action="{{ route('admin.penempatan-pkl.force-delete', $penempatanPkl->id) }}" class="inline" onsubmit="return confirm('Hapus permanen penempatan PKL ini?')">
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
                                            Belum ada data penempatan PKL.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- Pagination --}}
            @if($penempatanPkls->hasPages())
                <div class="p-4 border-t border-gray-200">
                    {{ $penempatanPkls->appends(request()->query())->links() }}
                </div>
            @endif
    </div>
</x-app-layout>
