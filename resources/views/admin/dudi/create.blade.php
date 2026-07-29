<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah DUDI') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('admin.dudi._form', [
                'dudi' => null,
                'route' => route('admin.dudi.store'),
                'method' => 'POST',
            ])
        </div>
</x-app-layout>
