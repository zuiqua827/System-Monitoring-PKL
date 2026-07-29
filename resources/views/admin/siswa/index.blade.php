<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Data Siswa') }}
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
                    <p class="text-sm text-gray-500">Kelola data siswa peserta PKL</p>
                </div>
                @can('create', App\Models\Siswa::class)
                    <a href="{{ route('admin.siswa.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        + Tambah Siswa
                    </a>
                @endcan
            </div>

            {{-- Search --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('admin.siswa.index') }}" class="flex items-center gap-3">
                    <div class="flex-1">
                        <input type="text" name="search" placeholder="Cari siswa..." value="{{ request('search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.siswa.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
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
                                    <a href="{{ route('admin.siswa.index', array_merge(request()->query(), ['sort' => 'nis', 'direction' => request('sort') === 'nis' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        NIS
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600">NISN</th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('admin.siswa.index', array_merge(request()->query(), ['sort' => 'nama', 'direction' => request('sort') === 'nama' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Nama
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600">Jenis Kelamin</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Kelas</th>
                                <th class="px-4 py-3 font-medium text-gray-600">No. HP</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($siswas as $index => $siswa)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-gray-500">{{ $siswas->firstItem() + $index }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $siswa->nis }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $siswa->nisn ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-800">{{ $siswa->nama }}</td>
                                    <td class="px-4 py-3">
                                        @if($siswa->jenis_kelamin === 'L')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Laki-laki</span>
                                        @elseif($siswa->jenis_kelamin === 'P')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-800">Perempuan</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $siswa->kelas?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $siswa->no_telepon ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if($siswa->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Dihapus</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @can('view', $siswa)
                                                <a href="{{ route('admin.siswa.show', $siswa->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-indigo-600 hover:text-indigo-900">Detail</a>
                                            @endcan

                                            @unless($siswa->trashed())
                                                @can('update', $siswa)
                                                    <a href="{{ route('admin.siswa.edit', $siswa->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-yellow-600 hover:text-yellow-900">Edit</a>
                                                @endcan

                                                @can('delete', $siswa)
                                                    <form method="POST" action="{{ route('admin.siswa.destroy', $siswa->id) }}" class="inline" onsubmit="return confirm('Hapus siswa {{ $siswa->nama }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-red-600 hover:text-red-900">Hapus</button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('restore', $siswa)
                                                    <form method="POST" action="{{ route('admin.siswa.restore', $siswa->id) }}" class="inline" onsubmit="return confirm('Pulihkan siswa {{ $siswa->nama }}?')">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-2 py-1 text-xs text-green-600 hover:text-green-900">Restore</button>
                                                    </form>
                                                @endcan

                                                @can('forceDelete', $siswa)
                                                    <form method="POST" action="{{ route('admin.siswa.force-delete', $siswa->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $siswa->nama }}?')">
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
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                        @if(request('search'))
                                            Tidak ada hasil untuk pencarian "{{ request('search') }}"
                                        @else
                                            Belum ada data siswa.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $siswas->appends(request()->query())->links() }}
            </div>
    </div>
</x-app-layout>
