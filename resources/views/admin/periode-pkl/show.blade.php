<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Periode PKL: :name', ['name' => $periodePkl->nama]) }}
            </h2>
            <a href="{{ route('admin.periode-pkl.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Data Periode PKL --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Periode PKL</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Nama Periode</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $periodePkl->nama }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tahun Ajaran</p>
                        <p class="text-sm font-medium text-gray-800">{{ $periodePkl->tahun_ajaran }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="text-sm font-medium">
                            @if($periodePkl->status === 'Aktif')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                            @elseif($periodePkl->status === 'Persiapan')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Persiapan</span>
                            @elseif($periodePkl->status === 'Selesai')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Selesai</span>
                            @elseif($periodePkl->status === 'Ditutup')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Ditutup</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                            @if($periodePkl->trashed())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-1">Dihapus</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Mulai</p>
                        <p class="text-sm font-medium text-gray-800">{{ $periodePkl->tanggal_mulai ? $periodePkl->tanggal_mulai->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Selesai</p>
                        <p class="text-sm font-medium text-gray-800">{{ $periodePkl->tanggal_selesai ? $periodePkl->tanggal_selesai->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Penempatan PKL</p>
                        <p class="text-sm font-medium text-gray-800">{{ $periodePkl->penempatan_count ?? $periodePkl->penempatan()->count() }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Dibuat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $periodePkl->created_at ? $periodePkl->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Keterangan</p>
                        <p class="text-sm font-medium text-gray-800">{{ $periodePkl->keterangan ?: '-' }}</p>
                    </div>

            {{-- Tombol Aksi --}}
            <div class="flex items-center justify-end gap-3">
                @unless($periodePkl->trashed())
                    @can('update', $periodePkl)
                        <a href="{{ route('admin.periode-pkl.edit', $periodePkl->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 transition">
                            Edit Periode PKL
                        </a>
                    @endcan
                    @can('delete', $periodePkl)
                        <form method="POST" action="{{ route('admin.periode-pkl.destroy', $periodePkl->id) }}" class="inline" onsubmit="return confirm('Hapus periode {{ $periodePkl->nama }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                Hapus Periode PKL
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $periodePkl)
                        <form method="POST" action="{{ route('admin.periode-pkl.restore', $periodePkl->id) }}" class="inline" onsubmit="return confirm('Pulihkan periode {{ $periodePkl->nama }}?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                Pulihkan Periode PKL
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $periodePkl)
                        <form method="POST" action="{{ route('admin.periode-pkl.force-delete', $periodePkl->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $periodePkl->nama }}? Tindakan ini tidak dapat dibatalkan!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-900 transition">
                                Hapus Permanen
                            </button>
                        </form>
                    @endcan
                @endunless
            </div>
</x-app-layout>
