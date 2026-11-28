@php
    /**
     * @var \App\Models\PenempatanPKL $penempatanAktif
     * @var \App\Models\Siswa $siswa
     */
@endphp

@extends('layouts.app')

@section('title', 'Tambah Aktivitas')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Aktivitas</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Tambah Aktivitas Harian</h1>
            <p class="mt-2 text-sm text-slate-500">Catat aktivitas harian PKL Anda</p>
        </div>

        <form method="POST" action="{{ route('siswa.aktivitas.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <input type="hidden" name="penempatan_pkl_id" value="{{ $penempatanAktif->id }}">

            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Data Aktivitas</h3>
                    <p class="mt-1 text-sm text-slate-500">Isi detail aktivitas yang dilakukan</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-1 sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <label for="tanggal" class="block text-sm font-semibold text-slate-700">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" value="{{ \Carbon\Carbon::now(config('app.timezone'))->format('Y-m-d') }}" readonly
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-500 shadow-sm transition cursor-not-allowed focus:outline-none">
                        @error('tanggal')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5">
                        <label for="judul" class="block text-sm font-semibold text-slate-700">Judul Aktivitas <span class="text-red-500">*</span></label>
                        <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required maxlength="255"
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                               placeholder="Contoh: Membantu penginputan data">
                        @error('judul')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5">
                        <label for="jam_mulai" class="block text-sm font-semibold text-slate-700">Jam Mulai</label>
                        <input type="time" id="jam_mulai" name="jam_mulai" value="{{ old('jam_mulai') }}"
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('jam_mulai')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5">
                        <label for="jam_selesai" class="block text-sm font-semibold text-slate-700">Jam Selesai</label>
                        <input type="time" id="jam_selesai" name="jam_selesai" value="{{ old('jam_selesai') }}"
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('jam_selesai')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="deskripsi" class="block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                  placeholder="Jelaskan aktivitas yang dilakukan">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="hasil" class="block text-sm font-semibold text-slate-700">Hasil</label>
                        <textarea id="hasil" name="hasil" rows="2"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                  placeholder="Hasil yang dicapai">{{ old('hasil') }}</textarea>
                        @error('hasil')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="kendala" class="block text-sm font-semibold text-slate-700">Kendala</label>
                        <textarea id="kendala" name="kendala" rows="2"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                  placeholder="Kendala yang dihadapi">{{ old('kendala') }}</textarea>
                        @error('kendala')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="solusi" class="block text-sm font-semibold text-slate-700">Solusi</label>
                        <textarea id="solusi" name="solusi" rows="2"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                  placeholder="Solusi yang dilakukan">{{ old('solusi') }}</textarea>
                        @error('solusi')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700">Foto Kegiatan</label>
                        <div class="mt-1.5 flex items-center gap-3">
                            <button type="button" id="btn-open-camera" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                📷 Ambil Foto
                            </button>
                            <!-- Input asli disembunyikan total -->
                            <input type="file" id="foto_kegiatan" name="foto_kegiatan" accept="image/jpeg,image/jpg,image/png" class="hidden" style="display: none !important;" capture="environment">
                            <span id="camera-status" class="text-sm text-slate-500">Belum ada foto diambil</span>
                        </div>
                        
                        <!-- Preview container in the form itself -->
                        <div id="form-photo-preview-container" class="mt-3 hidden">
                            <img id="form-photo-preview" class="h-32 w-auto rounded-lg object-cover shadow-sm border border-slate-200">
                        </div>

                        <p class="mt-1.5 text-xs text-slate-400">Gunakan kamera perangkat. Format: JPEG, JPG, PNG. Maksimal 2 MB.</p>
                        @error('foto_kegiatan')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Tombol --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('siswa.aktivitas.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Kamera --}}
<div id="camera-modal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm p-4 sm:p-6 items-center justify-center">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between shrink-0">
            <h3 class="text-lg font-bold text-slate-900" id="modal-title">Ambil Foto Kegiatan</h3>
            <button type="button" id="btn-close-modal" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body -->
        <div class="relative bg-black flex-1 min-h-[300px] sm:min-h-[400px] flex items-center justify-center overflow-hidden">
            <!-- Viewfinder -->
            <video id="camera-stream" autoplay playsinline class="absolute inset-0 w-full h-full object-contain"></video>
            
            <!-- Result Image -->
            <img id="camera-result" class="absolute inset-0 w-full h-full object-contain hidden">
            
            <!-- Hidden Canvas -->
            <canvas id="camera-canvas" class="hidden"></canvas>
            
            <!-- Loading indicator -->
            <div id="camera-loading" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/50 text-white">
                <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-white mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-sm font-medium">Membuka kamera...</span>
            </div>
            
            <!-- Error message -->
            <div id="camera-error" class="absolute inset-0 hidden flex-col items-center justify-center bg-slate-900 text-white p-6 text-center">
                <svg class="h-12 w-12 text-red-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p id="camera-error-text" class="text-sm font-medium">Kamera tidak dapat diakses.</p>
            </div>
        </div>

        <!-- Footer / Actions -->
        <div class="border-t border-slate-100 px-5 py-4 flex gap-3 justify-center bg-slate-50 shrink-0">
            <!-- Mode Video -->
            <div id="action-capture" class="w-full flex justify-center">
                <button type="button" id="btn-capture" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-8 py-3 text-sm font-bold text-white shadow-card-sm transition hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    📷 Ambil Foto
                </button>
            </div>
            
            <!-- Mode Hasil -->
            <div id="action-result" class="w-full flex justify-between gap-3 hidden">
                <button type="button" id="btn-retake" class="w-1/2 inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Foto Ulang
                </button>
                <button type="button" id="btn-use-photo" class="w-1/2 inline-flex items-center justify-center gap-2 rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white shadow-card-sm transition hover:bg-green-700">
                    Gunakan Foto
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnOpenCamera = document.getElementById('btn-open-camera');
    const cameraModal = document.getElementById('camera-modal');
    const btnCloseModal = document.getElementById('btn-close-modal');
    
    const videoElement = document.getElementById('camera-stream');
    const resultImage = document.getElementById('camera-result');
    const canvasElement = document.getElementById('camera-canvas');
    
    const loadingIndicator = document.getElementById('camera-loading');
    const errorIndicator = document.getElementById('camera-error');
    const errorText = document.getElementById('camera-error-text');
    
    const actionCapture = document.getElementById('action-capture');
    const actionResult = document.getElementById('action-result');
    
    const btnCapture = document.getElementById('btn-capture');
    const btnRetake = document.getElementById('btn-retake');
    const btnUsePhoto = document.getElementById('btn-use-photo');
    
    const inputFoto = document.getElementById('foto_kegiatan');
    const cameraStatusText = document.getElementById('camera-status');
    const formPhotoPreviewContainer = document.getElementById('form-photo-preview-container');
    const formPhotoPreview = document.getElementById('form-photo-preview');
    
    let stream = null;
    let capturedBlob = null;
    
    btnOpenCamera.addEventListener('click', function() {
        openCameraModal();
    });
    
    btnCloseModal.addEventListener('click', function() {
        closeCameraModal();
    });
    
    function openCameraModal() {
        cameraModal.classList.remove('hidden');
        cameraModal.classList.add('flex');
        
        // Reset state
        showVideoMode();
        startCamera();
    }
    
    function closeCameraModal() {
        cameraModal.classList.add('hidden');
        cameraModal.classList.remove('flex');
        stopCamera();
    }
    
    function showVideoMode() {
        videoElement.classList.remove('hidden');
        resultImage.classList.add('hidden');
        actionCapture.classList.remove('hidden');
        actionResult.classList.add('hidden');
        document.getElementById('modal-title').textContent = 'Ambil Foto Kegiatan';
        btnCapture.disabled = true;
    }
    
    function showResultMode() {
        videoElement.classList.add('hidden');
        resultImage.classList.remove('hidden');
        actionCapture.classList.add('hidden');
        actionResult.classList.remove('hidden');
        document.getElementById('modal-title').textContent = 'Preview Foto';
    }
    
    async function startCamera() {
        errorIndicator.classList.add('hidden');
        loadingIndicator.classList.remove('hidden');
        
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error("Browser Anda tidak mendukung akses kamera secara langsung (MediaDevices API tidak tersedia).");
            }
            
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: "environment" }
                },
                audio: false
            });
            
            videoElement.srcObject = stream;
            
            videoElement.onloadedmetadata = () => {
                loadingIndicator.classList.add('hidden');
                btnCapture.disabled = false;
            };
        } catch (err) {
            console.error("Camera error:", err);
            loadingIndicator.classList.add('hidden');
            errorIndicator.classList.remove('hidden');
            
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                errorText.textContent = "Izin kamera ditolak. Mohon izinkan akses kamera pada pengaturan browser Anda, lalu muat ulang halaman.";
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                errorText.textContent = "Kamera tidak ditemukan pada perangkat Anda.";
            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                errorText.textContent = "Kamera sedang digunakan oleh aplikasi lain, atau akses diblokir oleh sistem.";
            } else {
                errorText.textContent = "Kamera tidak dapat diakses: " + err.message;
            }
        }
    }
    
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        videoElement.srcObject = null;
    }
    
    btnCapture.addEventListener('click', function() {
        if (!stream) return;
        
        const videoWidth = videoElement.videoWidth;
        const videoHeight = videoElement.videoHeight;
        
        // Set canvas dimensions to match video
        canvasElement.width = videoWidth;
        canvasElement.height = videoHeight;
        
        // Draw video frame to canvas
        const ctx = canvasElement.getContext('2d');
        ctx.drawImage(videoElement, 0, 0, videoWidth, videoHeight);
        
        // Convert to blob and show result
        canvasElement.toBlob(function(blob) {
            capturedBlob = blob;
            const imageUrl = URL.createObjectURL(blob);
            resultImage.src = imageUrl;
            
            stopCamera();
            showResultMode();
        }, 'image/jpeg', 0.85); // 0.85 quality for good balance of size and quality
    });
    
    btnRetake.addEventListener('click', function() {
        if (capturedBlob) {
            URL.revokeObjectURL(resultImage.src);
            capturedBlob = null;
        }
        showVideoMode();
        startCamera();
    });
    
    btnUsePhoto.addEventListener('click', function() {
        if (!capturedBlob) return;
        
        const fileName = 'kegiatan_' + new Date().getTime() + '.jpg';
        const file = new File([capturedBlob], fileName, { type: 'image/jpeg' });
        
        // Put the file into the hidden input using DataTransfer
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        inputFoto.files = dataTransfer.files;
        
        // Update form UI
        cameraStatusText.textContent = 'Foto berhasil diambil';
        cameraStatusText.classList.remove('text-slate-500');
        cameraStatusText.classList.add('text-green-600', 'font-medium');
        
        // Show preview in form
        formPhotoPreview.src = resultImage.src;
        formPhotoPreviewContainer.classList.remove('hidden');
        
        closeCameraModal();
    });
});
</script>
@endpush
@endsection
