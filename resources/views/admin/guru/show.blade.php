<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Guru: :name', ['name' => $guru->nama]) }}
            </h2>
            <a href="{{ route('admin.guru.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
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
                        <p class="text-sm font-medium text-gray-800">{{ $guru->user?->email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status Akun</p>
                        <p class="text-sm font-medium text-gray-800">
                            @if($guru->user?->email_verified_at)
                                <span class="text-green-600">Terverifikasi</span>
                            @else
                                <span class="text-yellow-600">Belum Verifikasi</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Terakhir Login</p>
                        <p class="text-sm font-medium text-gray-800">{{ $guru->user?->last_login_at ? $guru->user->last_login_at->format('d/m/Y H:i') : 'Belum pernah' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Dibuat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $guru->created_at ? $guru->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
            </div>

            {{-- Data Guru --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Data Pribadi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">NIP</p>
                        <p class="text-sm font-medium text-gray-800">{{ $guru->nip ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="text-sm font-medium">
                            @if($guru->trashed())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Dihapus</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                            @endif
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Nama Lengkap</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $guru->nama }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Jenis Kelamin</p>
                        <p class="text-sm font-medium text-gray-800">
                            @if($guru->jenis_kelamin === 'L') Laki-laki
                            @elseif($guru->jenis_kelamin === 'P') Perempuan
                            @else -
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">No. HP</p>
                        <p class="text-sm font-medium text-gray-800">{{ $guru->no_hp ?? '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Alamat</p>
                        <p class="text-sm font-medium text-gray-800">{{ $guru->alamat ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Pembimbingan PKL</p>
                        <p class="text-sm font-medium text-gray-800">{{ $guru->penempatan_count ?? $guru->penempatan()->count() }}</p>
                    </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex items-center justify-end gap-3">
                @unless($guru->trashed())
                    @can('update', $guru)
                        <a href="{{ route('admin.guru.edit', $guru->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 transition">
                            Edit Guru
                        </a>
                    @endcan
                    @can('delete', $guru)
                        <form method="POST" action="{{ route('admin.guru.destroy', $guru->id) }}" class="inline" onsubmit="return confirm('Hapus guru {{ $guru->nama }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                                Hapus Guru
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $guru)
                        <form method="POST" action="{{ route('admin.guru.restore', $guru->id) }}" class="inline" onsubmit="return confirm('Pulihkan guru {{ $guru->nama }}?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                Pulihkan Guru
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $guru)
                        <form method="POST" action="{{ route('admin.guru.force-delete', $guru->id) }}" class="inline" onsubmit="return confirm('Hapus permanen {{ $guru->nama }}? Tindakan ini tidak dapat dibatalkan!')">
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

