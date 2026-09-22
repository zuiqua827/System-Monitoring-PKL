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
                id="avatar-image"
                src="{{ $avatarUrl }}"
                alt="{{ $user->name }}"
                class="h-28 w-28 rounded-2xl object-cover shadow-card-md ring-4 ring-slate-100"
            >
            <div id="avatar-initials" style="display: none;" class="flex h-28 w-28 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-4xl font-bold text-white shadow-card-md ring-4 ring-slate-100">
                {{ $initials }}
            </div>
        @else
            <img
                id="avatar-image"
                src="#"
                alt="{{ $user->name }}"
                style="display: none;"
                class="h-28 w-28 rounded-2xl object-cover shadow-card-md ring-4 ring-slate-100"
            >
            <div id="avatar-initials" class="flex h-28 w-28 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-4xl font-bold text-white shadow-card-md ring-4 ring-slate-100">
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
        id="avatar-upload-form"
        method="POST"
        action="{{ route('account.upload-avatar') }}"
        enctype="multipart/form-data"
        class="mt-6 w-full"
    >
        @csrf

        <label id="avatar-upload-label" class="flex w-full cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-slate-200 px-4 py-4 text-center transition hover:border-blue-300 hover:bg-blue-50/50">
            <svg id="avatar-upload-icon-default" class="mb-2 h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            
            <div id="avatar-upload-icon-selected" style="display: none;" class="mb-2 flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>

            <span id="avatar-upload-text" class="text-sm font-semibold text-slate-700">Unggah Foto Profil</span>
            <span id="avatar-upload-hint" class="mt-0.5 text-xs text-slate-400">JPG, PNG, atau WEBP maks 2MB</span>
            <span id="avatar-upload-filename" style="display: none;" class="mt-0.5 max-w-[220px] truncate text-xs font-medium text-blue-600"></span>

            <input id="avatar-input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only">
        </label>

        @error('avatar')
            <p class="mt-2 text-xs font-medium text-red-500">{{ $message }}</p>
        @enderror

        <button id="avatar-submit-btn" type="submit" class="btn-primary mt-3 w-full disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Simpan Foto
        </button>
    </form>

    {{-- Delete avatar --}}
    @if ($avatarUrl)
        <form
            id="avatar-delete-form"
            method="POST"
            action="{{ route('account.delete-avatar') }}"
            class="mt-3 w-full"
            data-confirm="Apakah Anda yakin ingin menghapus foto profil ini?"
            data-confirm-title="Hapus Foto Profil"
            data-confirm-type="danger"
            data-confirm-btn="Ya, Hapus"
        >
            @csrf
            @method('DELETE')

            <button type="submit" class="btn-secondary w-full">
                Hapus Foto
            </button>
        </form>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('avatar-input');
    const imageEl = document.getElementById('avatar-image');
    const initialsEl = document.getElementById('avatar-initials');
    const submitBtn = document.getElementById('avatar-submit-btn');
    const uploadForm = document.getElementById('avatar-upload-form');
    
    const iconDefault = document.getElementById('avatar-upload-icon-default');
    const iconSelected = document.getElementById('avatar-upload-icon-selected');
    const textLabel = document.getElementById('avatar-upload-text');
    const hintLabel = document.getElementById('avatar-upload-hint');
    const filenameLabel = document.getElementById('avatar-upload-filename');
    const uploadLabelContainer = document.getElementById('avatar-upload-label');
    
    let currentPreviewUrl = null;
    let originalAvatarUrl = '{{ $avatarUrl }}' || null;

    if (fileInput) {
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            
            if (!file) {
                resetPreview();
                return;
            }

            const allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            const allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            const ext = (file.name.split('.').pop() || '').toLowerCase();

            // Format validation
            if (!allowedMimes.includes(file.type) && !allowedExts.includes(ext)) {
                fileInput.value = '';
                if (window.showAlert) {
                    window.showAlert({
                        type: 'warning',
                        title: 'Format Tidak Valid',
                        message: 'Format file tidak didukung. Harap pilih gambar dengan format JPG, JPEG, PNG, atau WEBP.',
                        buttonText: 'Mengerti'
                    });
                } else if (window.showToast) {
                    window.showToast({
                        type: 'warning',
                        title: 'Format Tidak Valid',
                        message: 'Harap pilih gambar dengan format JPG, JPEG, PNG, atau WEBP.'
                    });
                } else {
                    alert('Format file tidak didukung. Harap pilih gambar dengan format JPG, JPEG, PNG, atau WEBP.');
                }
                resetPreview();
                return;
            }

            const maxBytes = 2 * 1024 * 1024;
            if (file.size > maxBytes) {
                fileInput.value = '';
                if (window.showAlert) {
                    window.showAlert({
                        type: 'warning',
                        title: 'Ukuran Terlalu Besar',
                        message: 'Ukuran file foto melebihi batas maksimal 2 MB.',
                        buttonText: 'Mengerti'
                    });
                } else if (window.showToast) {
                    window.showToast({
                        type: 'warning',
                        title: 'Ukuran Terlalu Besar',
                        message: 'Ukuran file foto tidak boleh lebih dari 2 MB.'
                    });
                } else {
                    alert('Ukuran file foto melebihi batas maksimal 2 MB.');
                }
                resetPreview();
                return;
            }

            // Create preview
            if (currentPreviewUrl) {
                URL.revokeObjectURL(currentPreviewUrl);
            }
            
            currentPreviewUrl = URL.createObjectURL(file);
            
            // Show image, hide initials
            imageEl.src = currentPreviewUrl;
            imageEl.style.display = 'block';
            initialsEl.style.display = 'none';
            
            // Update UI state
            submitBtn.disabled = false;
            
            iconDefault.style.display = 'none';
            iconSelected.style.display = 'flex';
            
            textLabel.textContent = 'Ganti Foto Pilihan';
            hintLabel.style.display = 'none';
            
            filenameLabel.textContent = file.name + ' (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB)';
            filenameLabel.style.display = 'block';
            
            uploadLabelContainer.classList.add('border-blue-500', 'bg-blue-50/60', 'ring-2', 'ring-blue-100');
            uploadLabelContainer.classList.remove('border-slate-200', 'hover:border-blue-300', 'hover:bg-blue-50/50');
        });
    }

    function resetPreview() {
        if (currentPreviewUrl) {
            URL.revokeObjectURL(currentPreviewUrl);
            currentPreviewUrl = null;
        }
        
        if (originalAvatarUrl) {
            imageEl.src = originalAvatarUrl;
            imageEl.style.display = 'block';
            initialsEl.style.display = 'none';
        } else {
            imageEl.src = '#';
            imageEl.style.display = 'none';
            initialsEl.style.display = 'flex';
        }
        
        submitBtn.disabled = true;
        
        iconDefault.style.display = 'block';
        iconSelected.style.display = 'none';
        
        textLabel.textContent = 'Unggah Foto Profil';
        hintLabel.style.display = 'block';
        
        filenameLabel.textContent = '';
        filenameLabel.style.display = 'none';
        
        uploadLabelContainer.classList.remove('border-blue-500', 'bg-blue-50/60', 'ring-2', 'ring-blue-100');
        uploadLabelContainer.classList.add('border-slate-200', 'hover:border-blue-300', 'hover:bg-blue-50/50');
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const file = fileInput.files[0];
            if (!file) return;

            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Menyimpan...';

            try {
                const formData = new FormData(uploadForm);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || uploadForm.querySelector('input[name="_token"]')?.value
                    || '';

                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json().catch(() => null);

                if (response.ok && data && (data.success || data.avatar_url)) {
                    // Update original avatar URL
                    originalAvatarUrl = data.avatar_url;
                    
                    // Reset file input
                    fileInput.value = '';
                    resetPreview();

                    if (window.showToast) {
                        window.showToast({
                            type: 'success',
                            title: 'Berhasil',
                            message: data.message || 'Foto profil berhasil diperbarui.',
                            duration: 3000
                        });
                    }

                    // Reload page to update header avatar and show delete button properly
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000); 
                } else {
                    // Error handling
                    let errorMsg = 'Gagal mengunggah foto profil.';
                    if (data && data.errors && data.errors.avatar) {
                        errorMsg = Array.isArray(data.errors.avatar) ? data.errors.avatar[0] : data.errors.avatar;
                    } else if (data && data.message) {
                        errorMsg = data.message;
                    }

                    if (window.showAlert) {
                        window.showAlert({
                            type: 'error',
                            title: 'Gagal Mengunggah',
                            message: errorMsg
                        });
                    } else if (window.showToast) {
                        window.showToast({
                            type: 'error',
                            title: 'Gagal Mengunggah',
                            message: errorMsg,
                            duration: 5000
                        });
                    } else {
                        alert(errorMsg);
                    }
                }
            } catch (err) {
                console.error(err);
                if (window.showToast) {
                    window.showToast({
                        type: 'error',
                        title: 'Kesalahan Jaringan',
                        message: 'Terjadi kesalahan saat mengunggah foto.',
                        duration: 5000
                    });
                }
            } finally {
                if (fileInput.files.length > 0) {
                    submitBtn.disabled = false;
                }
                submitBtn.textContent = originalText;
            }
        });
    }
});
</script>
