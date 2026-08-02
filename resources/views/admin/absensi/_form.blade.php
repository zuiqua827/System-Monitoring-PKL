@php
    /**
     * @var \App\Models\Absensi|null $absensi
     * @var string $route
     * @var string $method
     */
    use App\Enums\AbsensiStatus;
    use App\Models\PenempatanPKL;
@endphp

<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ $route }}" class="space-y-6">
        @csrf
        @method($method)

        {{-- Data Absensi --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Informasi Absensi</h3>
                <p class="mt-1 text-sm text-slate-500">Data absensi harian siswa</p>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="penempatan_pkl_id" class="block text-sm font-semibold text-slate-700">
                        Penempatan PKL <span class="text-red-500">*</span>
                    </label>
                    <select id="penempatan_pkl_id" name="penempatan_pkl_id" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih Penempatan --</option>
                        @foreach(PenempatanPKL::with(['siswa', 'dudi', 'periodePKL'])->where('status', 'aktif')->get() as $penempatan)
                            <option value="{{ $penempatan->id }}" {{ old('penempatan_pkl_id', $absensi->penempatan_pkl_id ?? '') == $penempatan->id ? 'selected' : '' }}>
                                {{ $penempatan->siswa?->nama ?? '-' }} - {{ $penempatan->dudi?->nama_perusahaan ?? '-' }} ({{ $penempatan->periodePKL?->nama ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('penempatan_pkl_id')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="tanggal" class="block text-sm font-semibold text-slate-700">
                        Tanggal <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="tanggal" name="tanggal" required
                           value="{{ old('tanggal', isset($absensi->tanggal) ? $absensi->tanggal->format('Y-m-d') : date('Y-m-d')) }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('tanggal')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="status" class="block text-sm font-semibold text-slate-700">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih Status --</option>
                        @foreach(AbsensiStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ old('status', $absensi->status ?? '') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="jam_masuk" class="block text-sm font-semibold text-slate-700">Jam Masuk</label>
                    <input type="time" id="jam_masuk" name="jam_masuk"
                           value="{{ old('jam_masuk', isset($absensi->jam_masuk) ? $absensi->jam_masuk : '') }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('jam_masuk')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="jam_pulang" class="block text-sm font-semibold text-slate-700">Jam Pulang</label>
                    <input type="time" id="jam_pulang" name="jam_pulang"
                           value="{{ old('jam_pulang', isset($absensi->jam_keluar) ? $absensi->jam_keluar : '') }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('jam_pulang')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="keterangan" class="block text-sm font-semibold text-slate-700">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="3"
                              class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('keterangan', $absensi->keterangan ?? '') }}</textarea>
                    @error('keterangan')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Tombol --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('admin.absensi.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ isset($absensi) && $absensi ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
