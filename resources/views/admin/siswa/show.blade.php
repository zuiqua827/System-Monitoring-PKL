<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Siswa: :name', ['name' => $siswa->nama]) }}
            </h2>
            <a href="{{ route('admin.siswa.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
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
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->user?->email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status Akun</p>
                        <p class="text-sm font-medium text-gray-800">
                            @if($siswa->user?->email_verified_at)
                                <span class="text-green-600">Terverifikasi</span>
                            @else
                                <span class="text-yellow-600">Belum Verifikasi</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Terakhir Login</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->user?->last_login_at ? $siswa->user->last_login_at->format('d/m/Y H:i') : 'Belum pernah' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Dibuat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->created_at ? $siswa->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
            </div>

            {{-- Data Siswa --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Data Pribadi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">NIS</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->nis }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">NISN</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->nisn ?? '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Nama Lengkap</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $siswa->nama }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Jenis Kelamin</p>
                        <p class="text-sm font-medium text-gray-800">
                            @if($siswa->jenis_kelamin === 'L') Laki-laki
                            @elseif($siswa->jenis_kelamin === 'P') Perempuan
                            @else -
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Kelas</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->kelas?->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Lahir</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">No. Telepon</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->no_telepon ?? '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Alamat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->alamat ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="text-sm font-medium">
                            @if($siswa->trashed())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Dihapus</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Penempatan PKL</p>
                        <p class="text-sm font-medium text-gray-800">{{ $siswa->penempatan_count ?? $siswa->penempatan()->count() }}</p>
                    </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex items-center justify-end gap-3">
                @unless($siswa->trashed())
                    @can('update', $siswa)
                        <a href="{{ route('admin.siswa.edit', $siswa->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 transition">
                            Edit Siswa
                        </a>
                    @endcan
                    @can('delete', $siswa)
                        <form method="POST" action="{{ route('admin.siswa.destroy', $siswa->id) }}" class="inline" onsubmit="return confirm('Hapus siswa {{ $siswa->nama }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                Hapus Siswa
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $siswa)
                        <form method="POST" action="{{ route('admin.siswa.restore', $siswa->id) }}" class="inline" onsubmit="return confirm('Pulihkan siswa {{ $siswa->nama }}?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                Pulihkan Siswa
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $siswa)
                        <form method="POST" action="{{ route('admin.siswa.force-delete', $siswa->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $siswa->nama }}? Tindakan ini tidak dapat dibatalkan!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-900 transition">
                                Hapus Permanen
                            </button>
                        </form>
                    @endcan
                @endunless
            </div>
    </div>
</x-app-layout>
