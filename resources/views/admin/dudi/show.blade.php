<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail DUDI: :name', ['name' => $dudi->nama_perusahaan]) }}
            </h2>
            <a href="{{ route('admin.dudi.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Informasi Akun --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Akun</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Email Login</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->user?->email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status Akun</p>
                        <p class="text-sm font-medium text-gray-800">
                            @if($dudi->user?->email_verified_at)
                                <span class="text-green-600">Terverifikasi</span>
                            @else
                                <span class="text-yellow-600">Belum Verifikasi</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Terakhir Login</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->user?->last_login_at ? $dudi->user->last_login_at->format('d/m/Y H:i') : 'Belum pernah' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Dibuat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->created_at ? $dudi->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>

            {{-- Data Perusahaan --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Data Perusahaan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Nama Instansi/Perusahaan</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $dudi->nama_perusahaan }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">PIC/Pembimbing Industri</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->penanggung_jawab ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">No. Telepon</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->no_telepon ?? '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Alamat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->alamat ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Kecamatan</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->kecamatan ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Kabupaten</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->kabupaten ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Provinsi</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->provinsi ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="text-sm font-medium">
                            @if($dudi->trashed())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Dihapus</span>
                            @else
                                @if($dudi->status_aktif)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Nonaktif</span>
                                @endif
                            @endif
                        </p>
                    </div>
                    @if($dudi->latitude && $dudi->longitude)
                    <div>
                        <p class="text-sm text-gray-500">Latitude</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->latitude }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Longitude</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->longitude }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-500">Total Penempatan PKL</p>
                        <p class="text-sm font-medium text-gray-800">{{ $dudi->penempatan_count ?? $dudi->penempatan()->count() }}</p>
                    </div>

            {{-- Tombol Aksi --}}
            <div class="flex items-center justify-end gap-3">
                @unless($dudi->trashed())
                    @can('update', $dudi)
                        <a href="{{ route('admin.dudi.edit', $dudi->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 transition">
                            Edit DUDI
                        </a>
                    @endcan
                    @can('delete', $dudi)
                        <form method="POST" action="{{ route('admin.dudi.destroy', $dudi->id) }}" class="inline" onsubmit="return confirm('Hapus DUDI {{ $dudi->nama_perusahaan }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                Hapus DUDI
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $dudi)
                        <form method="POST" action="{{ route('admin.dudi.restore', $dudi->id) }}" class="inline" onsubmit="return confirm('Pulihkan DUDI {{ $dudi->nama_perusahaan }}?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                Pulihkan DUDI
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $dudi)
                        <form method="POST" action="{{ route('admin.dudi.force-delete', $dudi->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $dudi->nama_perusahaan }}? Tindakan ini tidak dapat dibatalkan!')">
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
