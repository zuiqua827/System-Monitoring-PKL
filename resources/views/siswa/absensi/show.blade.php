@php
    /**
     * @var \App\Models\Absensi $absensi
     */
    use App\Enums\AbsensiStatus;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Absensi') }}
            </h2>
            <a href="{{ route('siswa.absensi.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Absensi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Tanggal</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->tanggal ? $absensi->tanggal->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        @php
                            $statusEnum = AbsensiStatus::tryFrom($absensi->status);
                        @endphp
                        @if($statusEnum)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusEnum->color() }}-100 text-{{ $statusEnum->color() }}-800">
                                {{ $statusEnum->label() }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Jam Masuk</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->jam_masuk ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Jam Pulang</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->jam_keluar ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Lokasi Masuk</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->lokasi_masuk ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Lokasi Pulang</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->lokasi_pulang ?? '-' }}</p>
                    </div>
                    @if($absensi->foto_masuk)
                    <div>
                        <p class="text-sm text-gray-500">Foto Masuk</p>
                        <img src="{{ asset('storage/' . $absensi->foto_masuk) }}" alt="Foto Masuk" class="mt-1 max-w-xs rounded-lg shadow-sm">
                    </div>
                    @endif
                    @if($absensi->foto_pulang)
                    <div>
                        <p class="text-sm text-gray-500">Foto Pulang</p>
                        <img src="{{ asset('storage/' . $absensi->foto_pulang) }}" alt="Foto Pulang" class="mt-1 max-w-xs rounded-lg shadow-sm">
                    </div>
                    @endif
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Keterangan</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->keterangan ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Guru Pembimbing</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->penempatanPKL?->guru?->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Perusahaan</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

