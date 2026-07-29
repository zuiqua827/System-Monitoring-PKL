<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Periode PKL: :name', ['name' => $periodePkl->nama]) }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('admin.periode-pkl._form', [
                'periodePkl' => $periodePkl,
                'route' => route('admin.periode-pkl.update', $periodePkl->id),
                'method' => 'PUT',
            ])
        </div>
</x-app-layout>
