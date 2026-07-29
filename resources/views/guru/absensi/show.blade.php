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
            <a href="{{ route('guru.absensi.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Informasi Absensi --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Absensi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Siswa</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $absensi->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                        @if($absensi->penempatanPKL?->siswa)
                            <p class="text-xs text-gray-500">NIS: {{ $absensi->penempatanPKL->siswa->nis }}</p>
                        @endif
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
                        @else
                            <span class="text-gray-400">{{ $absensi->status }}</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->tanggal ? $absensi->tanggal->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Perusahaan</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
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
                        <a href="{{ asset('storage/' . $absensi->foto_masuk) }}" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-900">Lihat Foto</a>
                    </div>
                    @endif
                    @if($absensi->foto_pulang)
                    <div>
                        <p class="text-sm text-gray-500">Foto Pulang</p>
                        <a href="{{ asset('storage/' . $absensi->foto_pulang) }}" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-900">Lihat Foto</a>
                    </div>
                    @endif
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Keterangan</p>
                        <p class="text-sm font-medium text-gray-800">{{ $absensi->keterangan ?: '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Form Validasi --}}
            @can('verify', $absensi)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Validasi Absensi</h3>
                <form method="POST" action="{{ route('guru.absensi.verify', $absensi->id) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Ubah Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(AbsensiStatus::cases() as $status)
                                    <option value="{{ $status->value }}" {{ $absensi->status == $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('keterangan', $absensi->keterangan ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Simpan Validasi
                        </button>
                    </div>
                </form>
            </div>
            @endcan
        </div>
    </div>
</x-app-layout>

