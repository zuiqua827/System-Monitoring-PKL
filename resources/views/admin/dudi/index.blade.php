<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Data DUDI') }}
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
                    <p class="text-sm text-gray-500">Kelola data DUDI tempat PKL</p>
                </div>
                @can('create', App\Models\Dudi::class)
                    <a href="{{ route('admin.dudi.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        + Tambah DUDI
                    </a>
                @endcan
            </div>

            {{-- Search --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('admin.dudi.index') }}" class="flex items-center gap-3">
                    <div class="flex-1">
                        <input type="text" name="search" placeholder="Cari DUDI..." value="{{ request('search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.dudi.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
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
                                    <a href="{{ route('admin.dudi.index', array_merge(request()->query(), ['sort' => 'nama_perusahaan', 'direction' => request('sort') === 'nama_perusahaan' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Instansi/Perusahaan
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('admin.dudi.index', array_merge(request()->query(), ['sort' => 'penanggung_jawab', 'direction' => request('sort') === 'penanggung_jawab' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        PIC
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600">Telepon</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Lokasi</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($dudis as $index => $dudi)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-gray-500">{{ $dudis->firstItem() + $index }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $dudi->nama_perusahaan }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $dudi->penanggung_jawab ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $dudi->no_telepon ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        @php
                                            $lokasi = collect([$dudi->kecamatan, $dudi->kabupaten, $dudi->provinsi])->filter()->implode(', ');
                                        @endphp
                                        {{ $lokasi ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($dudi->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Dihapus</span>
                                        @else
                                            @if($dudi->status_aktif)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Nonaktif</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @can('view', $dudi)
                                                <a href="{{ route('admin.dudi.show', $dudi->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-indigo-600 hover:text-indigo-900">Detail</a>
                                            @endcan

                                            @unless($dudi->trashed())
                                                @can('update', $dudi)
                                                    <a href="{{ route('admin.dudi.edit', $dudi->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-yellow-600 hover:text-yellow-900">Edit</a>
                                                @endcan

                                                @can('delete', $dudi)
                                                    <form method="POST" action="{{ route('admin.dudi.destroy', $dudi->id) }}" class="inline" onsubmit="return confirm('Hapus DUDI {{ $dudi->nama_perusahaan }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-red-600 hover:text-red-900">Hapus</button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('restore', $dudi)
                                                    <form method="POST" action="{{ route('admin.dudi.restore', $dudi->id) }}" class="inline" onsubmit="return confirm('Pulihkan DUDI {{ $dudi->nama_perusahaan }}?')">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-green-600 hover:text-green-900">Restore</button>
                                                    </form>
                                                @endcan

                                                @can('forceDelete', $dudi)
                                                    <form method="POST" action="{{ route('admin.dudi.force-delete', $dudi->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $dudi->nama_perusahaan }}?')">
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
                                            Belum ada data DUDI.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- Pagination --}}
            @if($dudis->hasPages())
                <div class="p-4 border-t border-gray-200">
                    {{ $dudis->appends(request()->query())->links() }}
                </div>
            @endif
    </div>
</x-app-layout>
