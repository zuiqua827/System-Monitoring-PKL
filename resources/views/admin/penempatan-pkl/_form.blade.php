@php
    /**
     * @var \App\Models\PenempatanPKL|null $penempatanPkl
     * @var string $route
     * @var string $method
     */

    $selectedSiswa = $penempatanPkl?->siswa;
@endphp

<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ $route }}" class="space-y-6">
        @csrf
        @method($method)

        {{-- Data Penempatan --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Informasi Penempatan PKL</h3>
                <p class="mt-1 text-sm text-slate-500">Data penempatan siswa PKL</p>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                <div class="bg-white p-5" x-data="{
                    query: '',
                    open: false,
                    loading: false,
                    results: [],
                    selected: @js($selectedSiswa ? [
                        'id' => $selectedSiswa->id,
                        'nama' => $selectedSiswa->nama,
                        'nis' => $selectedSiswa->nis,
                        'nisn' => $selectedSiswa->nisn,
                        'kelas' => $selectedSiswa->kelas?->nama,
                        'jurusan' => $selectedSiswa->kelas?->jurusan?->nama,
                    ] : null),
                    timer: null,
                    async search() {
                        if (this.selected) return;
                        if (this.query.trim().length < 2) {
                            this.results = [];
                            this.open = false;
                            return;
                        }
                        this.loading = true;
                        this.open = true;
                        const url = '{{ route('admin.penempatan-pkl.students') }}?q=' + encodeURIComponent(this.query);
                        try {
                            const res = await fetch(url);
                            const json = await res.json();
                            this.results = json.data ?? [];
                        } catch (e) {
                            this.results = [];
                        } finally {
                            this.loading = false;
                        }
                    },
                    debouncedSearch() {
                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => this.search(), 300);
                    },
                    selectStudent(s) {
                        this.selected = s;
                        this.open = false;
                        this.query = '';
                        this.results = [];
                    },
                    clearStudent() {
                        this.selected = null;
                    }
                }">
                    <label for="siswa_search" class="block text-sm font-semibold text-slate-700">
                        Siswa <span class="text-red-500">*</span>
                    </label>

                    {{-- Hidden field carries selected siswa_id --}}
                    <input type="hidden" name="siswa_id" :value="selected ? selected.id : ''">

                    {{-- Searchable input --}}
                    <template x-if="!selected">
                        <div class="relative mt-1.5">
                            <input type="text" id="siswa_search" x-model="query"
                                   @input="debouncedSearch()"
                                   @focus="if (query.trim().length >= 2) open = true"
                                   @click.outside="open = false"
                                   autocomplete="off"
                                   placeholder="Ketik nama, NIS, NISN, kelas, atau jurusan…"
                                   class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 @error('siswa_id') border-red-500 @enderror">

                            <div x-show="open" x-cloak
                                 class="absolute z-20 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                <div x-show="loading" class="px-4 py-3 text-sm text-slate-500">
                                    Mencari data…
                                </div>
                                <template x-if="!loading && results.length === 0">
                                    <div class="px-4 py-3 text-sm text-slate-500">Tidak ada siswa ditemukan.</div>
                                </template>
                                <ul>
                                    <template x-for="s in results" :key="s.id">
                                        <li>
                                            <button type="button" @click="selectStudent(s)"
                                                    class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-blue-50"
                                                    x-on:keydown.enter.prevent="selectStudent(s)">
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-semibold text-slate-900" x-text="s.nama"></p>
                                                    <p class="mt-0.5 text-xs text-slate-500">
                                                        <span x-text="'NIS: ' + (s.nis || '-')"></span>
                                                        <span x-show="s.kelas" class="ml-1">· <span x-text="s.kelas"></span></span>
                                                        <span x-show="s.jurusan" class="ml-1">· <span x-text="s.jurusan"></span></span>
                                                    </p>
                                                </div>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </template>

                    {{-- Selected student card --}}
                    <template x-if="selected">
                        <div class="mt-1.5 rounded-xl border border-blue-200 bg-blue-50 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900" x-text="selected.nama"></p>
                                    <p class="mt-1 text-xs text-slate-600">
                                        <span x-text="'NIS: ' + (selected.nis || '-')"></span>
                                        <span x-show="selected.kelas" class="ml-1">· <span x-text="selected.kelas"></span></span>
                                        <span x-show="selected.jurusan" class="ml-1">· <span x-text="selected.jurusan"></span></span>
                                    </p>
                                </div>
                                <button type="button" @click="clearStudent()"
                                        class="rounded-md p-1 text-slate-400 transition hover:bg-white hover:text-red-600"
                                        title="Pilih siswa lain">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    @error('siswa_id')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="dudi_id" class="block text-sm font-semibold text-slate-700">
                        Perusahaan/DUDI <span class="text-red-500">*</span>
                    </label>
                    <select id="dudi_id" name="dudi_id" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih DUDI --</option>
                        @foreach(\App\Models\Dudi::all() as $dudi)
                            <option value="{{ $dudi->id }}" {{ old('dudi_id', $penempatanPkl->dudi_id ?? '') == $dudi->id ? 'selected' : '' }}>
                                {{ $dudi->nama_perusahaan }}
                            </option>
                        @endforeach
                    </select>
                    @error('dudi_id')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="guru_id" class="block text-sm font-semibold text-slate-700">
                        Guru Pembimbing <span class="text-red-500">*</span>
                    </label>
                    <select id="guru_id" name="guru_id" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih Guru --</option>
                        @foreach(\App\Models\Guru::all() as $guru)
                            <option value="{{ $guru->id }}" {{ old('guru_id', $penempatanPkl->guru_id ?? '') == $guru->id ? 'selected' : '' }}>
                                {{ $guru->nama }} {{ $guru->nip ? '('.$guru->nip.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('guru_id')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="periode_pkl_id" class="block text-sm font-semibold text-slate-700">
                        Periode PKL <span class="text-red-500">*</span>
                    </label>
                    <select id="periode_pkl_id" name="periode_pkl_id" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih Periode --</option>
                        @foreach(\App\Models\PeriodePKL::orderBy('created_at', 'desc')->get() as $periode)
                            <option value="{{ $periode->id }}" {{ old('periode_pkl_id', $penempatanPkl->periode_pkl_id ?? '') == $periode->id ? 'selected' : '' }}>
                                {{ $periode->nama }} ({{ $periode->tahun_ajaran }})
                            </option>
                        @endforeach
                    </select>
                    @error('periode_pkl_id')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="nomor_surat" class="block text-sm font-semibold text-slate-700">Nomor Surat</label>
                    <input type="text" id="nomor_surat" name="nomor_surat" value="{{ old('nomor_surat', $penempatanPkl->nomor_surat ?? '') }}" maxlength="100"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('nomor_surat')
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
                        <option value="pending" {{ old('status', $penempatanPkl->status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="aktif" {{ old('status', $penempatanPkl->status ?? '') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="selesai" {{ old('status', $penempatanPkl->status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="dibatalkan" {{ old('status', $penempatanPkl->status ?? '') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    @error('status')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="tanggal_mulai" class="block text-sm font-semibold text-slate-700">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                           value="{{ old('tanggal_mulai', isset($penempatanPkl->tanggal_mulai) ? $penempatanPkl->tanggal_mulai->format('Y-m-d') : '') }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('tanggal_mulai')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="tanggal_selesai" class="block text-sm font-semibold text-slate-700">Tanggal Selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                           value="{{ old('tanggal_selesai', isset($penempatanPkl->tanggal_selesai) ? $penempatanPkl->tanggal_selesai->format('Y-m-d') : '') }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('tanggal_selesai')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="catatan" class="block text-sm font-semibold text-slate-700">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="3"
                              class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('catatan', $penempatanPkl->catatan ?? '') }}</textarea>
                    @error('catatan')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Tombol --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('admin.penempatan-pkl.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ isset($penempatanPkl) && $penempatanPkl ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
