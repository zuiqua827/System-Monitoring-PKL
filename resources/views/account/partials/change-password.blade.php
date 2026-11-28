<div>
    <div>
        <h2 class="text-lg font-bold text-slate-900">Keamanan</h2>
        <p class="mt-1 text-sm text-slate-500">Ganti kata sandi akun Anda secara berkala untuk menjaga keamanan.</p>
    </div>

    <form method="POST" action="{{ route('account.update-password') }}" class="mt-6 space-y-5">
        @csrf
        @method('PUT')

        {{-- Current password --}}
        <div>
            <x-input-label for="current_password" value="Kata Sandi Saat Ini" />
            <x-text-input
                id="current_password"
                name="current_password"
                type="password"
                class="mt-1 block w-full"
                autocomplete="current-password"
                required
            />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        {{-- New password --}}
        <div>
            <x-input-label for="password" value="Kata Sandi Baru" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="mt-1 block w-full"
                autocomplete="new-password"
                required
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Confirm new password --}}
        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi Baru" />
            <x-text-input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="mt-1 block w-full"
                autocomplete="new-password"
                required
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-5">
            <button type="submit" class="btn-primary">
                Perbarui Kata Sandi
            </button>
        </div>
    </form>
</div>
