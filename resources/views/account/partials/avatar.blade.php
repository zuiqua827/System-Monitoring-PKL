@php
    $avatarUrl = $user->avatar
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar)
        : null;
    $initials = $user->initials();
@endphp

{{-- Identity + Avatar Card --}}
<div class="flex flex-col items-center text-center">
    {{-- Avatar preview --}}
    <div class="relative">
        @if ($avatarUrl)
            <img
                src="{{ $avatarUrl }}"
                alt="{{ $user->name }}"
                class="h-28 w-28 rounded-2xl object-cover shadow-card-md ring-4 ring-slate-100"
            >
        @else
            <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-4xl font-bold text-white shadow-card-md ring-4 ring-slate-100">
                {{ $initials }}
            </div>
        @endif
    </div>

    {{-- Identity --}}
    <h2 class="mt-4 text-lg font-bold text-slate-900">{{ $user->name }}</h2>
    <p class="text-sm text-slate-500">{{ $user->email }}</p>

    <span class="badge badge-blue mt-3">{{ $role }}</span>

    {{-- Avatar upload --}}
    <form
        method="POST"
        action="{{ route('account.upload-avatar') }}"
        enctype="multipart/form-data"
        class="mt-6 w-full"
    >
        @csrf

        <label class="flex w-full cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-slate-200 px-4 py-4 text-center transition hover:border-blue-300 hover:bg-blue-50/50">
            <svg class="mb-2 h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            <span class="text-sm font-semibold text-slate-700">Unggah Foto Profil</span>
            <span class="mt-0.5 text-xs text-slate-400">JPG, PNG, atau WEBP maks 2MB</span>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only">
        </label>

        @error('avatar')
            <p class="mt-2 text-xs font-medium text-red-500">{{ $message }}</p>
        @enderror


        <button type="submit" class="btn-primary mt-3 w-full">
            Simpan Foto
        </button>
    </form>

    {{-- Delete avatar --}}
    @if ($avatarUrl)
        <form
            method="POST"
            action="{{ route('account.delete-avatar') }}"
            class="mt-3 w-full"
            onsubmit="return confirm('Hapus foto profil ini?');"
        >
            @csrf
            @method('DELETE')

            <button type="submit" class="btn-secondary w-full">
                Hapus Foto
            </button>
        </form>
    @endif
</div>
