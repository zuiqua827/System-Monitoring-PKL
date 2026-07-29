@php
    /**
     * @var \App\Models\PenempatanPKL|null $penempatanPkl
     * @var string $route
     * @var string $method
     */
@endphp

<div class="max-w-3xl mx-auto">
    <form method="POST" action="{{ $route }}" class="space-y-6">
        @csrf
        @method($method)

        {{-- Data Penempatan --}}
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Penempatan PKL</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="siswa_id" class="block text-sm font-medium text-gray-700">Siswa <span class="text-red-500">*</span></label>
                    <select id="siswa_id" name="siswa_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Siswa --</option>
                        @foreach(\App\Models\Siswa::with('kelas')->get() as $siswa)
                            <option value="{{ $siswa->id }}" {{ old('siswa_id', $penempatanPkl->siswa_id ?? '') == $siswa->id ? 'selected' : '' }}>
                                {{ $siswa->nama }} ({{ $siswa->nis }} - {{ $siswa->kelas?->nama ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('siswa_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="dudi_id" class="block text-sm font-medium text-gray-700">Perusahaan/DUDI <span class="text-red-500">*</span></label>
                    <select id="dudi_id" name="dudi_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih DUDI --</option>
                        @foreach(\App\Models\Dudi::all() as $dudi)
                            <option value="{{ $dudi->id }}" {{ old('dudi_id', $penempatanPkl->dudi_id ?? '') == $dudi->id ? 'selected' : '' }}>
                                {{ $dudi->nama_perusahaan }} ({{ $dudi->nama_pimpinan ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('dudi_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="guru_id" class="block text-sm font-medium text-gray-700">Guru Pembimbing <span class="text-red-500">*</span></label>
                    <select id="guru_id" name="guru_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Guru --</option>
                        @foreach(\App\Models\Guru::all() as $guru)
                            <option value="{{ $guru->id }}" {{ old('guru_id', $penempatanPkl->guru_id ?? '') == $guru->id ? 'selected' : '' }}>
                                {{ $guru->nama }} {{ $guru->nip ? '('.$guru->nip.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('guru_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="periode_pkl_id" class="block text-sm font-medium text-gray-700">Periode PKL <span class="text-red-500">*</span></label>
                    <select id="periode_pkl_id" name="periode_pkl_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Periode --</option>
                        @foreach(\App\Models\PeriodePKL::orderBy('created_at', 'desc')->get() as $periode)
                            <option value="{{ $periode->id }}" {{ old('periode_pkl_id', $penempatanPkl->periode_pkl_id ?? '') == $periode->id ? 'selected' : '' }}>
                                {{ $periode->nama }} ({{ $periode->tahun_ajaran }})
                            </option>
                        @endforeach
                    </select>
                    @error('periode_pkl_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nomor_surat" class="block text-sm font-medium text-gray-700">Nomor Surat</label>
                    <input type="text" id="nomor_surat" name="nomor_surat" value="{{ old('nomor_surat', $penempatanPkl->nomor_surat ?? '') }}" maxlength="100"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('nomor_surat')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Status --</option>
                        <option value="pending" {{ old('status', $penempatanPkl->status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="aktif" {{ old('status', $penempatanPkl->status ?? '') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="selesai" {{ old('status', $penempatanPkl->status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="dibatalkan" {{ old('status', $penempatanPkl->status ?? '') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                           value="{{ old('tanggal_mulai', isset($penempatanPkl->tanggal_mulai) ? $penempatanPkl->tanggal_mulai->format('Y-m-d') : '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('tanggal_mulai')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                           value="{{ old('tanggal_selesai', isset($penempatanPkl->tanggal_selesai) ? $penempatanPkl->tanggal_selesai->format('Y-m-d') : '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('tanggal_selesai')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="catatan" class="block text-sm font-medium text-gray-700">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('catatan', $penempatanPkl->catatan ?? '') }}</textarea>
                    @error('catatan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

        {{-- Tombol --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.penempatan-pkl.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                {{ isset($penempatanPkl) && $penempatanPkl ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
