@php
    /**
     * @var \App\Models\Penilaian|null $penilaian
     * @var string $route
     * @var string $method
     * @var bool $isFinal
     */
@endphp

<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ $route }}" class="space-y-6">
        @csrf
        @method($method)

        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Nilai Aspek PKL</h3>
                <p class="mt-1 text-sm text-slate-500">Berikan nilai 0-100 untuk setiap aspek penilaian</p>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                {{-- Penempatan PKL Info --}}
                @if(isset($penilaian) && $penilaian)
                    <div class="bg-white p-5 sm:col-span-2">
                        <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }} (Guru)</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="penempatan_pkl_id" class="block text-sm font-semibold text-slate-700">
                            Pilih Siswa <span class="text-red-500">*</span>
                        </label>
                        <select id="penempatan_pkl_id" name="penempatan_pkl_id" required
                                class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach(\App\Models\PenempatanPKL::with(['siswa', 'guru'])->whereDoesntHave('penilaian')->where('dudi_id', auth()->user()->dudi?->id ?? 0)->get() as $p)
                                <option value="{{ $p->id }}" {{ old('penempatan_pkl_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->siswa?->nama ?? '-' }} (Nisn: {{ $p->siswa?->nisn ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        @error('penempatan_pkl_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @php
                    $fields = [
                        'nilai_disiplin' => 'Disiplin',
                        'nilai_kehadiran' => 'Kehadiran',
                        'nilai_tanggung_jawab' => 'Tanggung Jawab',
                        'nilai_komunikasi' => 'Komunikasi',
                        'nilai_kerjasama' => 'Kerjasama',
                        'nilai_inisiatif' => 'Inisiatif',
                        'nilai_teknis' => 'Teknis',
                    ];
                @endphp

                @foreach($fields as $field => $label)
                <div class="bg-white p-5">
                    <label for="{{ $field }}" class="block text-sm font-semibold text-slate-700">
                        {{ $label }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="{{ $field }}" name="{{ $field }}" min="0" max="100" required
                           value="{{ old($field, $penilaian->$field ?? '') }}"
                           class="nilai-input mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                           {{ $isFinal ? 'disabled' : '' }}>
                    @error($field)
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endforeach

                {{-- Nilai Akhir & Predikat Preview --}}
                <div class="bg-white p-5">
                    <label class="block text-sm font-semibold text-slate-700">Nilai Akhir</label>
                    <p id="nilai_akhir_preview" class="mt-1.5 text-2xl font-bold text-blue-600">{{ $penilaian->nilai_akhir ?? '-' }}</p>
                </div>
                <div class="bg-white p-5">
                    <label class="block text-sm font-semibold text-slate-700">Predikat</label>
                    <p id="predikat_preview" class="mt-1.5 text-2xl font-bold text-blue-600">{{ $penilaian->predikat ?? '-' }}</p>
                </div>

                {{-- Catatan DUDI --}}
                <div class="bg-white p-5 sm:col-span-2">
                    <label for="catatan" class="block text-sm font-semibold text-slate-700">Catatan Evaluasi / Komentar</label>
                    <textarea id="catatan" name="catatan" rows="3"
                              class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                              {{ $isFinal ? 'disabled' : '' }}>{{ old('catatan', $penilaian->catatan ?? '') }}</textarea>
                    @error('catatan')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Tombol --}}
        @unless($isFinal)
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('dudi.penilaian.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ isset($penilaian) && $penilaian ? 'Simpan Perubahan' : 'Simpan Penilaian' }}
            </button>
        </div>
        @endunless
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.nilai-input:not([disabled])');

    function calculateNilaiAkhir() {
        let total = 0;
        let count = 0;
        inputs.forEach(function(input) {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val >= 0 && val <= 100) {
                total += val;
                count++;
            }
        });

        const previewNilai = document.getElementById('nilai_akhir_preview');
        const previewPredikat = document.getElementById('predikat_preview');

        if (count > 0) {
            const avg = (total / count).toFixed(2);
            previewNilai.textContent = avg;

            const avgNum = parseFloat(avg);
            if (avgNum >= 90) previewPredikat.textContent = 'A (Sangat Baik)';
            else if (avgNum >= 80) previewPredikat.textContent = 'B (Baik)';
            else if (avgNum >= 70) previewPredikat.textContent = 'C (Cukup)';
            else if (avgNum >= 60) previewPredikat.textContent = 'D (Kurang)';
            else previewPredikat.textContent = 'E (Sangat Kurang)';
        } else {
            previewNilai.textContent = '{{ $penilaian->nilai_akhir ?? '-' }}';
            previewPredikat.textContent = '{{ $penilaian->predikat ?? '-' }}';
        }
    }

    inputs.forEach(function(input) {
        input.addEventListener('input', calculateNilaiAkhir);
    });
});
</script>
@endpush
