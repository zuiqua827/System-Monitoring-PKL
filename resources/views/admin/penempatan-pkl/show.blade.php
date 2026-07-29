<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Penempatan PKL') }}
            </h2>
            <a href="{{ route('admin.penempatan-pkl.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Informasi Penempatan --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Penempatan PKL</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Siswa</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $penempatanPkl->siswa?->nama ?? '-' }}</p>
                        @if($penempatanPkl->siswa)
                            <p class="text-xs text-gray-500">NIS: {{ $penempatanPkl->siswa->nis }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="text-sm font-medium">
                            @if($penempatanPkl->status === 'aktif')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                            @elseif($penempatanPkl->status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                            @elseif($penempatanPkl->status === 'selesai')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Selesai</span>
                            @elseif($penempatanPkl->status === 'dibatalkan')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Dibatalkan</span>
                            @else
                                <span class="text-gray-400">{{ $penempatanPkl->status }}</span>
                            @endif
                            @if($penempatanPkl->trashed())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-1">Dihapus</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Guru Pembimbing</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->guru?->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Perusahaan (DUDI)</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->dudi?->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Periode PKL</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->periodePKL?->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Dibuat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->created_at ? $penempatanPkl->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Nomor Surat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->nomor_surat ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Dibuat Oleh</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->dibuatOleh?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Mulai</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->tanggal_mulai ? $penempatanPkl->tanggal_mulai->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Selesai</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->tanggal_selesai ? $penempatanPkl->tanggal_selesai->format('d/m/Y') : '-' }}</p>
                    </div>
                    @if($penempatanPkl->approvedBy)
                    <div>
                        <p class="text-sm text-gray-500">Disetujui Oleh</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->approvedBy->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Disetujui</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->approved_at ? $penempatanPkl->approved_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    @endif
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Catatan</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->catatan ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Absensi</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->absensi_count ?? $penempatanPkl->absensi()->count() }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Aktivitas</p>
                        <p class="text-sm font-medium text-gray-800">{{ $penempatanPkl->aktivitas_count ?? $penempatanPkl->aktivitas()->count() }}</p>
                    </div>

            {{-- Tombol Aksi --}}
            <div class="flex items-center justify-end gap-3">
                @unless($penempatanPkl->trashed())
                    @can('update', $penempatanPkl)
                        <a href="{{ route('admin.penempatan-pkl.edit', $penempatanPkl->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 transition">
                            Edit Penempatan
                        </a>
                    @endcan
                    @can('delete', $penempatanPkl)
                        <form method="POST" action="{{ route('admin.penempatan-pkl.destroy', $penempatanPkl->id) }}" class="inline" onsubmit="return confirm('Hapus penempatan PKL ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                Hapus Penempatan
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $penempatanPkl)
                        <form method="POST" action="{{ route('admin.penempatan-pkl.restore', $penempatanPkl->id) }}" class="inline" onsubmit="return confirm('Pulihkan penempatan PKL ini?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                Pulihkan Penempatan
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $penempatanPkl)
                        <form method="POST" action="{{ route('admin.penempatan-pkl.force-delete', $penempatanPkl->id) }}" class="inline" onsubmit="return confirm('Hapus permanen penempatan PKL ini? Tindakan ini tidak dapat dibatalkan!')">
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
