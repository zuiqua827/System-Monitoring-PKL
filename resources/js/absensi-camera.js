/**
 * Absensi Camera & GPS Module
 *
 * Features:
 * - Real-time camera capture using getUserMedia()
 * - GPS auto-detection using getCurrentPosition()
 * - HTML5 Canvas watermark with student info
 * - Haversine radius validation
 * - Fallback to file upload if camera not supported
 */

class AbsensiCamera {
    constructor(options = {}) {
        this.videoElement = document.getElementById(
            options.videoId || "camera-preview",
        );
        this.canvasElement = document.getElementById(
            options.canvasId || "camera-canvas",
        );
        this.photoInput = document.getElementById(
            options.photoInputId || "foto_base64",
        );
        this.latInput = document.getElementById(
            options.latInputId || "latitude",
        );
        this.lngInput = document.getElementById(
            options.lngInputId || "longitude",
        );
        this.accuracyInput = document.getElementById(
            options.accuracyInputId || "accuracy",
        );
        this.statusElement = document.getElementById(
            options.statusId || "camera-status",
        );
        this.captureBtn = document.getElementById(
            options.captureBtnId || "btn-capture",
        );
        this.retakeBtn = document.getElementById(
            options.retakeBtnId || "btn-retake",
        );
        this.confirmBtn = document.getElementById(
            options.confirmBtnId || "btn-confirm",
        );
        this.fileUpload = document.getElementById(
            options.fileUploadId || "file-upload",
        );
        this.photoPreview = document.getElementById(
            options.photoPreviewId || "photo-preview",
        );

        this.stream = null;
        this.capturedImage = null;
        this.currentPosition = null;
        this.watermarkData = options.watermarkData || {};
        this.onComplete = options.onComplete || null;
        this.mode = options.mode || "checkin"; // 'checkin' or 'checkout'

        this.mode = options.mode || "checkin"; // 'checkin' or 'checkout'

        this._bindEvents();
        this.init();
    }

    _bindEvents() {
        if (this.captureBtn) {
            this.captureBtn.addEventListener("click", () => this.capturePhoto());
        }
        if (this.retakeBtn) {
            this.retakeBtn.addEventListener("click", () => this.retakePhoto());
        }
        if (this.confirmBtn) {
            this.confirmBtn.addEventListener("click", () => this.confirmAndSubmit());
        }
    }

    async init() {
        try {
            // Try to get GPS position first
            await this.getGPSPosition();

            // Try to start camera
            await this.startCamera();
        } catch (error) {
            console.warn("Camera init warning:", error.message);
            this.showStatus(
                "Camera tidak tersedia, gunakan upload file",
                "warning",
            );
            if (this.fileUpload) {
                this.fileUpload.style.display = "block";
            }
        }
    }

    async getGPSPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                this.showStatus("GPS tidak didukung browser ini", "error");
                reject(new Error("Geolocation not supported"));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.currentPosition = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                    };

                    if (this.latInput)
                        this.latInput.value = position.coords.latitude;
                    if (this.lngInput)
                        this.lngInput.value = position.coords.longitude;
                    if (this.accuracyInput)
                        this.accuracyInput.value = position.coords.accuracy;

                    this.showStatus(
                        `GPS: ${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`,
                        "success",
                    );
                    resolve(position);
                },
                (error) => {
                    let message = "Gagal mendapatkan lokasi";
                    if (error.code === 1)
                        message = "Izin lokasi ditolak. Silakan aktifkan GPS.";
                    else if (error.code === 2) message = "GPS tidak tersedia.";
                    else if (error.code === 3)
                        message = "Waktu permintaan lokasi habis.";

                    this.showStatus(message, "error");
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                },
            );
        });
    }

    async startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error("Camera API not supported");
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user",
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });

            if (this.videoElement) {
                this.videoElement.srcObject = this.stream;
                this.videoElement.style.display = "block";
                this.videoElement.play();
            }

            if (this.captureBtn) this.captureBtn.style.display = "inline-flex";
            if (this.canvasElement) this.canvasElement.style.display = "none";
            if (this.fileUpload) this.fileUpload.style.display = "none";

            this.showStatus(
                "Kamera siap. Ambil foto untuk Check " +
                    (this.mode === "checkin" ? "In" : "Out"),
                "success",
            );
        } catch (error) {
            if (error.name === "NotAllowedError") {
                throw new Error(
                    "Izin kamera ditolak. Silakan izinkan akses kamera.",
                );
            } else if (error.name === "NotFoundError") {
                throw new Error("Kamera tidak ditemukan.");
            } else {
                throw new Error("Gagal mengakses kamera: " + error.message);
            }
        }
    }

    capturePhoto() {
        if (!this.videoElement || !this.canvasElement) return;

        const context = this.canvasElement.getContext("2d");
        const width = this.videoElement.videoWidth;
        const height = this.videoElement.videoHeight;

        this.canvasElement.width = width;
        this.canvasElement.height = height;

        // Draw video frame to canvas
        context.drawImage(this.videoElement, 0, 0, width, height);

        // Add watermark
        this.addWatermark(context, width, height);

        // Stop camera stream
        this.stopCamera();

        // Store captured image
        this.capturedImage = this.canvasElement.toDataURL("image/jpeg", 0.85);

        // Show preview
        if (this.photoPreview) {
            this.photoPreview.src = this.capturedImage;
            this.photoPreview.style.display = "block";
        }

        // Set base64 to hidden input
        if (this.photoInput) {
            this.photoInput.value = this.capturedImage;
        }

        // Update UI
        if (this.videoElement) this.videoElement.style.display = "none";
        if (this.captureBtn) this.captureBtn.style.display = "none";
        if (this.retakeBtn) this.retakeBtn.style.display = "inline-flex";
        if (this.confirmBtn) this.confirmBtn.style.display = "inline-flex";

        this.showStatus("Foto berhasil diambil dengan watermark", "success");
    }

    addWatermark(context, width, height) {
        const data = this.watermarkData;

        // Semi-transparent overlay at bottom
        context.fillStyle = "rgba(0, 0, 0, 0.6)";
        context.fillRect(0, height - 130, width, 130);

        // Watermark text
        context.fillStyle = "white";
        context.font = `bold ${Math.max(14, width * 0.025)}px Arial, sans-serif`;
        context.textAlign = "left";
        context.textBaseline = "bottom";

        const now = new Date();
        const dateStr = now.toLocaleDateString("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });
        const timeStr = now.toLocaleTimeString("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });

        let y = height - 100;
        const lineHeight = Math.max(18, width * 0.028);

        // Line 1: Student name
        if (data.nama_siswa) {
            context.fillText(`Siswa: ${data.nama_siswa}`, 15, y);
            y += lineHeight;
        }

        // Line 2: DUDI name
        if (data.nama_dudi) {
            context.fillText(`DUDI: ${data.nama_dudi}`, 15, y);
            y += lineHeight;
        }

        // Line 3: Date & Time
        context.fillText(`Tanggal: ${dateStr} | Jam: ${timeStr}`, 15, y);
        y += lineHeight;

        // Line 4: GPS coordinates
        if (this.currentPosition) {
            const lat = this.currentPosition.latitude.toFixed(6);
            const lng = this.currentPosition.longitude.toFixed(6);
            context.fillText(
                `Lokasi: ${lat}, ${lng} (Akurasi: ${this.currentPosition.accuracy.toFixed(0)}m)`,
                15,
                y,
            );
            y += lineHeight;
        }

        // Type indicator (Check In/Out)
        context.fillStyle = this.mode === "checkin" ? "#4CAF50" : "#FF9800";
        context.font = `bold ${Math.max(16, width * 0.03)}px Arial, sans-serif`;
        context.textAlign = "right";
        context.fillText(
            this.mode === "checkin" ? "CHECK IN" : "CHECK OUT",
            width - 15,
            height - 100,
        );
    }

    confirmAndSubmit() {
        if (this.confirmBtn) {
            this.confirmBtn.style.display = "none";
            this.confirmBtn.disabled = true;
        }
        if (this.retakeBtn) this.retakeBtn.style.display = "none";

        this.showStatus("Mengirim data...", "success");

        if (typeof this.onComplete === "function") {
            this.onComplete({
                foto_base64: this.capturedImage,
                latitude: this.currentPosition?.latitude || null,
                longitude: this.currentPosition?.longitude || null,
                accuracy: this.currentPosition?.accuracy || null,
            });
        }
    }

    retakePhoto() {
        this.capturedImage = null;
        if (this.photoInput) this.photoInput.value = "";
        if (this.photoPreview) this.photoPreview.style.display = "none";
        if (this.confirmBtn) this.confirmBtn.style.display = "none";
        if (this.retakeBtn) this.retakeBtn.style.display = "none";

        // Restart camera
        this.startCamera().catch((err) => {
            this.showStatus(
                "Gagal memulai ulang kamera: " + err.message,
                "error",
            );
        });
    }

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
    }

    showStatus(message, type = "info") {
        if (this.statusElement) {
            this.statusElement.textContent = message;
            this.statusElement.className = `mt-2 text-sm p-2 rounded ${
                type === "error"
                    ? "bg-red-100 text-red-700"
                    : type === "warning"
                      ? "bg-yellow-100 text-yellow-700"
                      : type === "success"
                        ? "bg-green-100 text-green-700"
                        : "bg-blue-100 text-blue-700"
            }`;
            this.statusElement.style.display = "block";
        }
    }

    destroy() {
        this.stopCamera();
        if (this.videoElement) this.videoElement.srcObject = null;
    }
}

// Export for use in Blade
window.AbsensiCamera = AbsensiCamera;
