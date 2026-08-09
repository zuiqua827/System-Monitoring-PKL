<div>
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Informasi Akun</h2>
            <p class="mt-1 text-sm text-slate-500">Perbarui informasi profil Anda.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('account.update-info') }}" class="mt-6 space-y-5">
        @csrf
        @method('PATCH')

        {{-- Read-only identity fields --}}
        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Email (read-only) --}}
            <div>
                <x-input-label value="Email" />
                <input type="email" value="{{ $user->email }}" disabled class="input bg-slate-50 text-slate-500">
                <p class="mt-1 text-xs text-slate-400">Email tidak dapat diubah.</p>
            </div>

            {{-- Role (read-only) --}}
            <div>
                <x-input-label value="Role" />
                <input type="text" value="{{ $role }}" disabled class="input bg-slate-50 text-slate-500">
                <p class="mt-1 text-xs text-slate-400">Role tidak dapat diubah.</p>
            </div>
        </div>

        {{-- Role-specific read-only identifiers --}}
        @if ($role === 'Siswa' && $user->siswa)
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label value="NIS" />
                    <input type="text" value="{{ $user->siswa->nis }}" disabled class="input bg-slate-50 text-slate-500">
                </div>
                <div>
                    <x-input-label value="NISN" />
                    <input type="text" value="{{ $user->siswa->nisn ?? '-' }}" disabled class="input bg-slate-50 text-slate-500">
                </div>
            </div>
        @endif

        @if ($role === 'Guru' && $user->guru)
            <div>
                <x-input-label value="NIP" />
                <input type="text" value="{{ $user->guru->nip ?? '-' }}" disabled class="input bg-slate-50 text-slate-500">
            </div>
        @endif

        @if ($role === 'DUDI' && $user->dudi)
            <div>
                <x-input-label value="Nama Perusahaan" />
                <input type="text" value="{{ $user->dudi->nama_perusahaan ?? '-' }}" disabled class="input bg-slate-50 text-slate-500">
            </div>
        @endif

        <hr class="border-slate-200">

        {{-- Editable profile fields --}}
        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Full name --}}
            <div>
                <x-input-label for="name" value="Nama Lengkap" />
                <x-text-input
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('name', $user->name)"
                    required
                    autofocus
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            {{-- Phone --}}
            <div>
                <x-input-label for="phone" value="No. Telepon" />
                <x-text-input
                    id="phone"
                    name="phone"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('phone', $user->phone)"
                />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            {{-- Department / Bidang --}}
            <div>
                <x-input-label for="department" value="Departemen / Bidang" />
                <x-text-input
                    id="department"
                    name="department"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('department', $user->department)"
                />
                <x-input-error :messages="$errors->get('department')" class="mt-2" />
            </div>

            {{-- Gender --}}
            <div>
                <x-input-label for="gender" value="Jenis Kelamin" />
                <select id="gender" name="gender" class="input mt-1 block w-full">
                    <option value="">-- Pilih --</option>
                    <option value="L" @selected(old('gender', $user->gender) === 'L')>Laki-Laki</option>
                    <option value="P" @selected(old('gender', $user->gender) === 'P')>Perempuan</option>
                </select>
                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
            </div>

            {{-- Birth date --}}
            <div class="sm:col-span-2">
                <x-input-label for="birth_date" value="Tanggal Lahir" />
                <x-text-input
                    id="birth_date"
                    name="birth_date"
                    type="date"
                    class="mt-1 block w-full"
                    :value="old('birth_date', optional($user->birth_date)->format('Y-m-d'))"
                />
                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
            </div>

            {{-- Address --}}
            <div class="sm:col-span-2">
                <x-input-label for="address" value="Alamat" />
                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    class="input mt-1 block w-full"
                >{{ old('address', $user->address) }}</textarea>
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-5">
            <button type="submit" class="btn-primary">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
