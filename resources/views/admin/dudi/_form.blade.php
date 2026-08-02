@php
    /**
     * @var \App\Models\Dudi|null $dudi
     * @var string $route
     * @var string $method
     */
@endphp

<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ $route }}" class="space-y-6">
        @csrf
        @method($method)

        {{-- Informasi Akun Login --}}
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
                    <input type="email" id="email" name="email" value="{{ old('email', $dudi->user->email ?? '') }}" required
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Data Perusahaan --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Data Perusahaan</h3>
                <p class="mt-1 text-sm text-slate-500">Informasi mitra DUDI</p>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="nama_perusahaan" class="block text-sm font-semibold text-slate-700">
                        Nama Instansi/Perusahaan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nama_perusahaan" name="nama_perusahaan" value="{{ old('nama_perusahaan', $dudi->nama_perusahaan ?? '') }}" required
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('nama_perusahaan')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="penanggung_jawab" class="block text-sm font-semibold text-slate-700">
                        Nama PIC/Pembimbing Industri <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="penanggung_jawab" name="penanggung_jawab" value="{{ old('penanggung_jawab', $dudi->penanggung_jawab ?? '') }}" required
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('penanggung_jawab')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="no_telepon" class="block text-sm font-semibold text-slate-700">
                        Nomor Telepon <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="no_telepon" name="no_telepon" value="{{ old('no_telepon', $dudi->no_telepon ?? '') }}" maxlength="20" required
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('no_telepon')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if(!isset($dudi) || !$dudi)
                        <p class="mt-1.5 text-xs text-slate-500">Nomor telepon akan digunakan sebagai password awal akun DUDI.</p>
                    @endif
                </div>
                <div class="bg-white p-5">
                    <label for="status_aktif" class="block text-sm font-semibold text-slate-700">
                        Status Aktif <span class="text-red-500">*</span>
                    </label>
                    <select id="status_aktif" name="status_aktif" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="1" {{ old('status_aktif', $dudi->status_aktif ?? '1') == '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('status_aktif', $dudi->status_aktif ?? '') == '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                    @error('status_aktif')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="alamat" class="block text-sm font-semibold text-slate-700">
                        Alamat <span class="text-red-500">*</span>
                    </label>
                    <textarea id="alamat" name="alamat" rows="2" required
                              class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('alamat', $dudi->alamat ?? '') }}</textarea>
                    @error('alamat')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="kecamatan" class="block text-sm font-semibold text-slate-700">Kecamatan</label>
                    <input type="text" id="kecamatan" name="kecamatan" value="{{ old('kecamatan', $dudi->kecamatan ?? '') }}" maxlength="255"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('kecamatan')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="kabupaten" class="block text-sm font-semibold text-slate-700">Kabupaten</label>
                    <input type="text" id="kabupaten" name="kabupaten" value="{{ old('kabupaten', $dudi->kabupaten ?? '') }}" maxlength="255"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('kabupaten')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="provinsi" class="block text-sm font-semibold text-slate-700">Provinsi</label>
                    <input type="text" id="provinsi" name="provinsi" value="{{ old('provinsi', $dudi->provinsi ?? '') }}" maxlength="255"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('provinsi')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="latitude" class="block text-sm font-semibold text-slate-700">Latitude</label>
                    <input type="text" id="latitude" name="latitude" value="{{ old('latitude', $dudi->latitude ?? '') }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="-6.2088">
                    @error('latitude')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-white p-5">
                    <label for="longitude" class="block text-sm font-semibold text-slate-700">Longitude</label>
                    <input type="text" id="longitude" name="longitude" value="{{ old('longitude', $dudi->longitude ?? '') }}"
                           class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="106.8456">
                    @error('longitude')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Tombol --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('admin.dudi.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ isset($dudi) && $dudi ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
