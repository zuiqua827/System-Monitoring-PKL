@php
    /**
     * @var \App\Models\Siswa|null $siswa
     * @var string $route
     * @var string $method
     */
@endphp

<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ $route }}" class="space-y-6">
        @csrf
        @method($method)

        {{-- Informasi Akun --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Informasi Akun Login</h3>
                <p class="mt-1 text-sm text-slate-500">Data akun untuk login sistem</p>
            </div>
            <div class="space-y-5 px-6 py-6">
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">
                        Email Login <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $siswa->user->email ?? '') }}" required
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if(!isset($siswa) || !$siswa)
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" required
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <p class="mt-1.5 text-xs text-slate-500">Password default, siswa akan diminta mengganti saat login pertama.</p>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif
            </div>
        </div>

        {{-- Data Siswa --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Data Siswa</h3>
                <p class="mt-1 text-sm text-slate-500">Data diri lengkap siswa</p>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                <div class="bg-white p-5">
                    <label for="nis" class="block text-sm font-semibold text-slate-700">
                        NIS <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nis" name="nis" value="{{ old('nis', $siswa->nis ?? '') }}" required maxlength="30"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('nis')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="nisn" class="block text-sm font-semibold text-slate-700">NISN</label>
                    <input type="text" id="nisn" name="nisn" value="{{ old('nisn', $siswa->nisn ?? '') }}" maxlength="30"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('nisn')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="nama" class="block text-sm font-semibold text-slate-700">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama', $siswa->nama ?? '') }}" required
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('nama')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="jenis_kelamin" class="block text-sm font-semibold text-slate-700">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin"
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih --</option>
                        <option value="L" {{ old('jenis_kelamin', $siswa->jenis_kelamin ?? '') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('jenis_kelamin', $siswa->jenis_kelamin ?? '') === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    @error('jenis_kelamin')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="class_id" class="block text-sm font-semibold text-slate-700">
                        Kelas <span class="text-red-500">*</span>
                    </label>
                    <select id="class_id" name="class_id" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($kelasList ?? \App\Models\Kelas::with('jurusan')->get() as $kelas)
                            <option value="{{ $kelas->id }}" {{ old('class_id', $siswa->class_id ?? '') == $kelas->id ? 'selected' : '' }}>
                                {{ $kelas->nama }} ({{ $kelas->jurusan?->kode ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('class_id')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="tanggal_lahir" class="block text-sm font-semibold text-slate-700">Tanggal Lahir</label>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir"
                           value="{{ old('tanggal_lahir', isset($siswa->tanggal_lahir) ? $siswa->tanggal_lahir->format('Y-m-d') : '') }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('tanggal_lahir')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="no_telepon" class="block text-sm font-semibold text-slate-700">No. Telepon</label>
                    <input type="text" id="no_telepon" name="no_telepon" value="{{ old('no_telepon', $siswa->no_telepon ?? '') }}" maxlength="20"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('no_telepon')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="alamat" class="block text-sm font-semibold text-slate-700">Alamat</label>
                    <textarea id="alamat" name="alamat" rows="3"
                              class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('alamat', $siswa->alamat ?? '') }}</textarea>
                    @error('alamat')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Tombol --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('admin.siswa.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ isset($siswa) && $siswa ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
