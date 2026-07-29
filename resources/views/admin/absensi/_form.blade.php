@php
    /**
     * @var \App\Models\Absensi|null $absensi
     * @var string $route
     * @var string $method
     */
    use App\Enums\AbsensiStatus;
    use App\Models\PenempatanPKL;
@endphp

<div class="max-w-3xl mx-auto">
    <form method="POST" action="{{ $route }}" class="space-y-6">
        @csrf
        @method($method)

        {{-- Data Absensi --}}
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Absensi</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="penempatan_pkl_id" class="block text-sm font-medium text-gray-700">Penempatan PKL <span class="text-red-500">*</span></label>
                    <select id="penempatan_pkl_id" name="penempatan_pkl_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Penempatan --</option>
                        @foreach(PenempatanPKL::with(['siswa', 'dudi', 'periodePKL'])->where('status', 'aktif')->get() as $penempatan)
                            <option value="{{ $penempatan->id }}" {{ old('penempatan_pkl_id', $absensi->penempatan_pkl_id ?? '') == $penempatan->id ? 'selected' : '' }}>
                                {{ $penempatan->siswa?->nama ?? '-' }} - {{ $penempatan->dudi?->nama_perusahaan ?? '-' }} ({{ $penempatan->periodePKL?->nama ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('penempatan_pkl_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal" name="tanggal" required
                           value="{{ old('tanggal', isset($absensi->tanggal) ? $absensi->tanggal->format('Y-m-d') : date('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('tanggal')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="jam_masuk" class="block text-sm font-medium text-gray-700">Jam Masuk</label>
                    <input type="time" id="jam_masuk" name="jam_masuk"
                           value="{{ old('jam_masuk', isset($absensi->jam_masuk) ? $absensi->jam_masuk : '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('jam_masuk')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="jam_pulang" class="block text-sm font-medium text-gray-700">Jam Pulang</label>
                    <input type="time" id="jam_pulang" name="jam_pulang"
                           value="{{ old('jam_pulang', isset($absensi->jam_keluar) ? $absensi->jam_keluar : '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('jam_pulang')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Status --</option>
                        @foreach(AbsensiStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ old('status', $absensi->status ?? '') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('keterangan', $absensi->keterangan ?? '') }}</textarea>
                    @error('keterangan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Tombol --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.absensi.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                {{ isset($absensi) && $absensi ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>

