<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-slate-900">Hapus Akun</h2>
        <p class="mt-1 text-sm text-slate-500">Hapus akun Anda secara permanen. Semua data terkait akan dihapus.</p>
    </header>

    <button type="button" x-data x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" class="btn-danger">
        Hapus Akun
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="flex items-center gap-3 text-red-600">
                <svg class="h-8 w-8 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Yakin ingin menghapus akun?</h2>
                    <p class="mt-1 text-sm text-slate-500">Data akan dihapus permanen. Masukkan password untuk konfirmasi.</p>
                </div>
            </div>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" />
                <x-text-input id="password" name="password" type="password" class="mt-1.5 block w-full" placeholder="Masukkan password" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary">
                    Batal
                </button>
                <button type="submit" class="btn-danger">
                    Hapus Akun Saya
                </button>
            </div>
        </form>
    </x-modal>
</section>
