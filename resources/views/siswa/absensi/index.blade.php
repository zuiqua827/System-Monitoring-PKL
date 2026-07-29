@php
    /**
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $absensis
     * @var \App\Models\PenempatanPKL|null $penempatanAktif
     * @var \App\Models\Absensi|null $todayAbsensi
     * @var \App\Models\Siswa $siswa
     */
    use App\Enums\AbsensiStatus;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Absensi Saya') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Card Check In / Check Out --}}
            @if($penempatanAktif)
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Status Hari Ini</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Penempatan: {{ $penempatanAktif->dudi?->nama_perusahaan ?? '-' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($todayAbsensi === null)
                                    {{-- Belum Check In --}}
                                    <form method="POST" action="{{ route('siswa.absensi.check-in') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                        @csrf
                                        <div>
                                            <input type="file" name="foto_masuk" accept="image/*" class="text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                            @error('foto_masuk')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                            Check In
                                        </button>
                                    </form>
                                @elseif($todayAbsensi->jam_keluar === null)
                                    {{-- Sudah Check In, belum Check Out --}}
                                    <div class="text-sm text-gray-500">
                                        <span class="text-green-600 font-semibold">✓ Check In</span> {{ $todayAbsensi->jam_masuk }}
                                    </div>
                                    <form method="POST" action="{{ route('siswa.absensi.check-out') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                        @csrf
                                        <div>
                                            <input type="file" name="foto_pulang" accept="image/*" class="text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                            @error('foto_pulang')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600 transition">
                                            Check Out
                                        </button>
                                    </form>
                                @else
                                    {{-- Sudah Check In dan Check Out --}}
                                    <div class="text-sm text-gray-500">
                                        <span class="text-green-600 font-semibold">✓ Check In</span> {{ $todayAbsensi->jam_masuk }}
                                        <span class="mx-2">|</span>
                                        <span class="text-orange-600 font-semibold">✓ Check Out</span> {{ $todayAbsensi->jam_keluar }}
                                    </div>
                                    @php
                                        $sEnum = AbsensiStatus::tryFrom($todayAbsensi->status);
                                    @endphp
                                    @if($sEnum)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sEnum->color() }}-100 text-{{ $sEnum->color() }}-800">
                                            {{ $sEnum->label() }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
                    Anda belum memiliki penempatan PKL yang aktif. Silahkan hubungi admin.
                </div>
            @endif

            {{-- Filter --}}
            <div class="mb-4 bg-white p-4 rounded-lg shadow-sm">
                <form method="GET" action="{{ route('siswa.absensi.index') }}" class="flex flex-wrap items-center gap-3">
                    <div class="w-40">
                        <input type="date" name="tanggal" value="{{ request('tanggal') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="w-36">
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Semua Status</option>
                            @foreach(AbsensiStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700 transition">
                        Filter
                    </button>
                    @if(request('tanggal') || request('status'))
                        <a href="{{ route('siswa.absensi.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase hover:bg-gray-50 transition">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            {{-- Riwayat Absensi --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-md font-semibold text-gray-700">Riwayat Absensi</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600 w-16">No</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Tanggal</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Jam Masuk</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Jam Pulang</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($absensis as $index => $absensi)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-gray-500">{{ $absensis->firstItem() + $index }}</td>
                                    <td class="px-4 py-3 text-gray-800">{{ $absensi->tanggal ? $absensi->tanggal->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $absensi->jam_masuk ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $absensi->jam_keluar ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $sEnum = AbsensiStatus::tryFrom($absensi->status);
                                        @endphp
                                        @if($sEnum)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $sEnum->color() }}-100 text-{{ $sEnum->color() }}-800">
                                                {{ $sEnum->label() }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">{{ $absensi->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('siswa.absensi.show', $absensi->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-indigo-600 hover:text-indigo-900">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        @if(request('tanggal') || request('status'))
                                            Tidak ada hasil untuk filter yang dipilih.
                                        @else
                                            Belum ada data absensi.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($absensis->hasPages())
                    <div class="p-4 border-t border-gray-200">
                        {{ $absensis->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

