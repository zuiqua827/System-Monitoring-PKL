<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Guru: :name', ['name' => $guru->nama]) }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('admin.guru._form', [
                'guru' => $guru,
                'route' => route('admin.guru.update', $guru->id),
                'method' => 'PUT',
            ])
        </div>
</x-app-layout>

