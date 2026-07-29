@php
    /**
     * @var \App\Models\Absensi|null $absensi
     */
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Absensi') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('admin.absensi._form', [
                'absensi' => null,
                'route' => route('admin.absensi.store'),
                'method' => 'POST',
            ])
        </div>
    </div>
</x-app-layout>

