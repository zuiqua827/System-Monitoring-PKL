@php
    /**
     * @var \App\Models\PenempatanPKL $penempatanPkl
     * @var string $updateRoute
     * @var string $backRoute
     */
    $currentMode = old('mode_kerja', $penempatanPkl->getEffectiveModeKerja());
    $currentValidasi = (string) old('validasi_lokasi', ($penempatanPkl->validasi_lokasi ?? true) ? '1' : '0');
@endphp

<div class="space-y-6" x-data="{ mode: '{{ $currentMode }}', validasi: '{{ $currentValidasi }}' }">
    {{-- Ringkasan Penempatan & Kebijakan Aktif --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">{{ $penempatanPkl->siswa?->nama ?? '-' }}</h2>
                <p class="mt-1 text-xs text-slate-500">
                    NIS: {{ $penempatanPkl->siswa?->nis ?? '-' }}
                    · Kelas: {{ $penempatanPkl->siswa?->kelas?->nama ?? '-' }}
                    · DUDI: {{ $penempatanPkl->dudi?->nama_perusahaan ?? '-' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $penempatanPkl->getEffectiveModeKerja() === 'WFH' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800' }}">
                    Mode: {{ $penempatanPkl->getEffectiveModeKerja() }}
                </span>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ ($penempatanPkl->validasi_lokasi ?? true) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    Geofence: {{ ($penempatanPkl->validasi_lokasi ?? true) ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
        </div>

        <div class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 text-xs sm:grid-cols-2">
            <div>
                <span class="font-semibold text-slate-500">Koordinat Referensi WFO (DUDI):</span>
                <p class="mt-0.5 font-mono text-slate-800">
                    @if ($penempatanPkl->dudi?->latitude && $penempatanPkl->dudi?->longitude)
                        {{ $penempatanPkl->dudi->latitude }}, {{ $penempatanPkl->dudi->longitude }} (Radius maks 100 m)
                    @else
                        Belum diatur pada data DUDI
                    @endif
                </p>
            </div>
            <div>
                <span class="font-semibold text-slate-500">Koordinat Referensi WFH Saat Ini:</span>
                <p class="mt-0.5 font-mono text-slate-800">
                    @if ($penempatanPkl->wfh_latitude && $penempatanPkl->wfh_longitude)
                        {{ $penempatanPkl->wfh_latitude }}, {{ $penempatanPkl->wfh_longitude }} (Radius {{ $penempatanPkl->getEffectiveWfhRadius() }} m)
                    @else
                        Belum dikonfigurasi
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Form Pengaturan Mode Kerja & Validasi Geofence --}}
    <form method="POST" action="{{ $updateRoute }}" class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
        @csrf
        @method('PUT')

        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-base font-bold text-slate-900">Pengaturan Mode Kerja & Validasi Lokasi Absensi</h3>
            <p class="mt-1 text-xs text-slate-500">
                Pengaturan berlaku spesifik untuk penempatan PKL ini. Menonaktifkan geofence tidak menonaktifkan validasi akun, status penempatan aktif, jadwal, maupun pencegahan duplikasi presensi.
            </p>
        </div>

        <div class="space-y-5 p-6">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="mode_kerja" class="block text-sm font-semibold text-slate-700">
                        Mode Kerja <span class="text-red-500">*</span>
                    </label>
                    <select id="mode_kerja" name="mode_kerja" x-model="mode" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="WFO">WFO — Kerja di Lokasi DUDI</option>
                        <option value="WFH">WFH — Kerja dari Rumah / Lokasi Khusus</option>
                    </select>
                    @error('mode_kerja')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="validasi_lokasi" class="block text-sm font-semibold text-slate-700">
                        Validasi Lokasi (Geofence GPS) <span class="text-red-500">*</span>
                    </label>
                    <select id="validasi_lokasi" name="validasi_lokasi" x-model="validasi" required
                            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="1">Aktif — Wajib di dalam radius lokasi yang berlaku</option>
                        <option value="0">Nonaktif — Izinkan absensi tanpa batas radius</option>
                    </select>
                    @error('validasi_lokasi')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Konfigurasi Koordinat WFH --}}
            <div x-show="mode === 'WFH'" x-cloak class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-5 space-y-4">
                <div>
                    <h4 class="text-sm font-bold text-indigo-950">Lokasi WFH yang Disetujui Sekolah / Pembimbing</h4>
                    <p class="mt-0.5 text-xs text-indigo-800">
                        Saat mode WFH dengan Validasi Lokasi Aktif, jarak siswa dihitung terhadap titik koordinat di bawah ini (bukan koordinat DUDI).
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="wfh_latitude" class="block text-xs font-semibold text-slate-700">
                            Latitude WFH <span x-show="validasi === '1'" class="text-red-500">*</span>
                        </label>
                        <input type="text" id="wfh_latitude" name="wfh_latitude"
                               value="{{ old('wfh_latitude', $penempatanPkl->wfh_latitude) }}"
                               placeholder="-6.5888900"
                               class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-mono text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('wfh_latitude')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="wfh_longitude" class="block text-xs font-semibold text-slate-700">
                            Longitude WFH <span x-show="validasi === '1'" class="text-red-500">*</span>
                        </label>
                        <input type="text" id="wfh_longitude" name="wfh_longitude"
                               value="{{ old('wfh_longitude', $penempatanPkl->wfh_longitude) }}"
                               placeholder="110.6684000"
                               class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-mono text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('wfh_longitude')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="wfh_radius" class="block text-xs font-semibold text-slate-700">
                            Radius WFH (meter) <span x-show="validasi === '1'" class="text-red-500">*</span>
                        </label>
                        <input type="number" min="10" max="5000" id="wfh_radius" name="wfh_radius"
                               value="{{ old('wfh_radius', $penempatanPkl->wfh_radius ?? 100) }}"
                               class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('wfh_radius')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="wfh_alamat" class="block text-xs font-semibold text-slate-700">Alamat / Keterangan Lokasi WFH</label>
                    <input type="text" id="wfh_alamat" name="wfh_alamat" maxlength="255"
                           value="{{ old('wfh_alamat', $penempatanPkl->wfh_alamat) }}"
                           placeholder="Contoh: Rumah Siswa / Lab Sekolah / Lokasi Alternatif Disetujui"
                           class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('wfh_alamat')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="reason" class="block text-sm font-semibold text-slate-700">
                    Alasan Perubahan Pengaturan Lokasi <span class="text-red-500">*</span>
                </label>
                <textarea id="reason" name="reason" rows="2" required minlength="3" maxlength="500"
                          placeholder="Tuliskan alasan perubahan kebijakan lokasi (dicatat permanen ke riwayat audit)..."
                          class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
            <a href="{{ $backRoute }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Kembali
            </a>
            <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Simpan Pengaturan Lokasi
            </button>
        </div>
    </form>

    {{-- Riwayat Perubahan Pengaturan Lokasi --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-base font-bold text-slate-900">Riwayat Perubahan Pengaturan Lokasi</h3>
            <p class="mt-1 text-xs text-slate-500">Audit log setiap perubahan mode kerja WFO/WFH dan status geofence</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Waktu</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Pengubah</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Nilai Lama</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Nilai Baru</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Alasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($penempatanPkl->locationLogs as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-600">
                                {{ $log->created_at?->format('d M Y H:i:s') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs">
                                <div class="font-semibold text-slate-800">{{ $log->changed_by_name }}</div>
                                <span class="inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">
                                    {{ $log->changed_by_role ?? '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">
                                <div>Mode: <strong>{{ $log->old_values['mode_kerja'] ?? 'WFO' }}</strong></div>
                                <div>Geofence: {{ !empty($log->old_values['validasi_lokasi']) ? 'Aktif' : 'Nonaktif' }}</div>
                                @if (!empty($log->old_values['wfh_latitude']))
                                    <div class="font-mono text-[11px] text-slate-500">{{ $log->old_values['wfh_latitude'] }}, {{ $log->old_values['wfh_longitude'] }} ({{ $log->old_values['wfh_radius'] ?? 100 }}m)</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-800">
                                <div>Mode: <strong>{{ $log->new_values['mode_kerja'] ?? 'WFO' }}</strong></div>
                                <div>Geofence: {{ !empty($log->new_values['validasi_lokasi']) ? 'Aktif' : 'Nonaktif' }}</div>
                                @if (!empty($log->new_values['wfh_latitude']))
                                    <div class="font-mono text-[11px] text-indigo-700">{{ $log->new_values['wfh_latitude'] }}, {{ $log->new_values['wfh_longitude'] }} ({{ $log->new_values['wfh_radius'] ?? 100 }}m)</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-700">
                                {{ $log->reason ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-xs text-slate-500">
                                Belum ada riwayat perubahan pengaturan lokasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
