@php
    /**
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $absensis
     */
    use App\Enums\AbsensiStatus;
    use App\Models\PeriodePKL;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Absensi Siswa Bimbingan') }}
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

            {{-- Search & Filter --}}
            <div class="mb-4 bg-white p-4 rounded-lg shadow-sm">
                <form method="GET" action="{{ route('guru.absensi.index') }}" class="flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="search" placeholder="Cari siswa atau perusahaan..." value="{{ request('search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="w-40">
                        <input type="date" name="tanggal" value="{{ request('tanggal') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="w-36">
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Status</option>
                            @foreach(AbsensiStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-48">
                        <select name="periode_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Periode</option>
                            @foreach(PeriodePKL::orderBy('created_at', 'desc')->get() as $periode)
                                <option value="{{ $periode->id }}" {{ request('periode_id') == $periode->id ? 'selected' : '' }}>
                                    {{ $periode->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700 transition">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'tanggal', 'status', 'periode_id']))
                        <a href="{{ route('guru.absensi.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase hover:bg-gray-50 transition">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600 w-16">No</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Tanggal</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Siswa</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Perusahaan</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Jam Masuk</th>
                                <th class="px-4 py-3 font-medium text-gray-600">Jam Pulang</th>
                                <th class="px-4 py-3 font-medium text-gray-600">
                                    <a href="{{ route('guru.absensi.index', array_merge(request()->query(), ['sort' => 'status', 'direction' => request('sort') === 'status' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-900">
                                        Status
                                    </a>
                                </th>
                                <th class="px-4 py-3 font-medium text-gray-600 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($absensis as $index => $absensi)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-gray-500">{{ $absensis->firstItem() + $index }}</td>
                                    <td class="px-4 py-3 text-gray-800">{{ $absensi->tanggal ? $absensi->tanggal->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $absensi->penempatanPKL?->siswa?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $absensi->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</td>
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
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('guru.absensi.show', $absensi->id) }}" class="inline-flex items-center px-2 py-1 text-xs text-indigo-600 hover:text-indigo-900">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        @if(request()->anyFilled(['search', 'tanggal', 'status', 'periode_id']))
                                            Tidak ada hasil untuk filter yang dipilih.
                                        @else
                                            Belum ada data absensi siswa bimbingan.
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

