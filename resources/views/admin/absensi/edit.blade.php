@php
    /**
     * @var \App\Models\Absensi $absensi
     */
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Absensi') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('admin.absensi._form', [
                'absensi' => $absensi,
                'route' => route('admin.absensi.update', $absensi->id),
                'method' => 'PUT',
            ])
        </div>
    </div>
</x-app-layout>

