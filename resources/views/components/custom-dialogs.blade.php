<div
    id="global-custom-dialogs-root"
    x-data="globalDialogManager()"
    x-init="initDialogs()"
    @keydown.escape.window="handleEscape()"
>
    {{-- ========================================================================= --}}
    {{-- 1. TOAST NOTIFICATION CONTAINER (Top-Right Desktop, Top-Center Mobile)   --}}
    {{-- ========================================================================= --}}
    <div
        class="custom-toast-container fixed top-4 right-4 sm:top-5 sm:right-5 z-[100000] flex flex-col gap-3 pointer-events-none"
        aria-live="polite"
        role="region"
        aria-label="Notifikasi Sistem"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="toast.visible"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 translate-y-[-12px] sm:translate-x-8 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 translate-y-[-8px] scale-95"
                @mouseenter="pauseToast(toast)"
                @mouseleave="resumeToast(toast)"
                class="custom-toast-card pointer-events-auto relative flex items-start gap-3.5 rounded-2xl border bg-white/95 p-4 shadow-xl backdrop-blur-md transition-all duration-200 sm:p-4.5 overflow-hidden"
                :class="{
                    'border-emerald-200 bg-gradient-to-r from-emerald-50/90 to-white': toast.type === 'success',
                    'border-rose-200 bg-gradient-to-r from-rose-50/90 to-white': toast.type === 'error',
                    'border-amber-200 bg-gradient-to-r from-amber-50/90 to-white': toast.type === 'warning',
                    'border-blue-200 bg-gradient-to-r from-blue-50/90 to-white': toast.type === 'info'
                }"
                role="alert"
            >
                {{-- Icon --}}
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm"
                    :class="{
                        'bg-emerald-500 text-white shadow-emerald-200': toast.type === 'success',
                        'bg-rose-500 text-white shadow-rose-200': toast.type === 'error',
                        'bg-amber-500 text-white shadow-amber-200': toast.type === 'warning',
                        'bg-blue-500 text-white shadow-blue-200': toast.type === 'info'
                    }"
                >
                    {{-- Success Checkmark --}}
                    <template x-if="toast.type === 'success'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </template>
                    {{-- Error Cross --}}
                    <template x-if="toast.type === 'error'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                    {{-- Warning Exclamation --}}
                    <template x-if="toast.type === 'warning'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>
                    {{-- Info Circle --}}
                    <template x-if="toast.type === 'info'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </template>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0 pt-0.5">
                    <p
                        class="text-sm font-bold tracking-tight text-slate-900"
                        x-text="toast.title || (toast.type === 'success' ? 'Berhasil' : (toast.type === 'error' ? 'Terjadi Kesalahan' : (toast.type === 'warning' ? 'Perhatian' : 'Informasi')))"
                    ></p>
                    <p class="mt-0.5 text-xs sm:text-sm font-normal text-slate-600 leading-relaxed break-words" x-text="toast.message"></p>
                </div>

                {{-- Close Button --}}
                <button
                    type="button"
                    @click="removeToast(toast.id)"
                    class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-300"
                    aria-label="Tutup notifikasi"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- Animated Progress Bar --}}
                <div x-show="toast.duration > 0" class="absolute bottom-0 left-0 right-0 h-1 overflow-hidden bg-black/5">
                    <div
                        class="custom-toast-progress-bar h-full"
                        :class="{
                            'bg-emerald-500': toast.type === 'success',
                            'bg-rose-500': toast.type === 'error',
                            'bg-amber-500': toast.type === 'warning',
                            'bg-blue-500': toast.type === 'info',
                            'is-paused': toast.paused
                        }"
                        :style="`animation-duration: ${toast.duration}ms;`"
                    ></div>
                </div>
            </div>
        </template>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. CONFIRMATION MODAL (Replaces confirm() globally)                       --}}
    {{-- ========================================================================= --}}
    <div
        x-show="confirmModal.open"
        x-cloak
        class="fixed inset-0 z-[9998] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title"
        aria-describedby="confirm-modal-desc"
    >
        {{-- Backdrop --}}
        <div
            x-show="confirmModal.open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="cancelConfirm()"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
        ></div>

        {{-- Modal Dialog Panel --}}
        <div
            x-show="confirmModal.open"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 sm:p-7 text-left shadow-2xl transition-all border border-slate-100 z-10"
        >
            <div class="flex items-start gap-4">
                {{-- Dynamic Icon --}}
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm"
                    :class="{
                        'bg-red-50 text-red-600 ring-8 ring-red-50/50': confirmModal.type === 'danger',
                        'bg-amber-50 text-amber-600 ring-8 ring-amber-50/50': confirmModal.type === 'warning',
                        'bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50': confirmModal.type === 'success',
                        'bg-blue-50 text-blue-600 ring-8 ring-blue-50/50': confirmModal.type === 'info'
                    }"
                >
                    {{-- Trash for Danger --}}
                    <template x-if="confirmModal.type === 'danger'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </template>
                    {{-- Warning Exclamation --}}
                    <template x-if="confirmModal.type === 'warning'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>
                    {{-- Emerald Restore / Checkmark --}}
                    <template x-if="confirmModal.type === 'success'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </template>
                    {{-- Blue Info --}}
                    <template x-if="confirmModal.type === 'info'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                        </svg>
                    </template>
                </div>

                {{-- Header & Message --}}
                <div class="flex-1 min-w-0">
                    <h3
                        id="confirm-modal-title"
                        class="text-lg font-bold text-slate-900 tracking-tight"
                        x-text="confirmModal.title || 'Konfirmasi Tindakan'"
                    ></h3>
                    <p
                        id="confirm-modal-desc"
                        class="mt-2 text-sm text-slate-600 leading-relaxed break-words"
                        x-text="confirmModal.message"
                    ></p>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-7 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button
                    type="button"
                    @click="cancelConfirm()"
                    class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
                    x-text="confirmModal.cancelText || 'Batal'"
                ></button>
                <button
                    type="button"
                    @click="executeConfirm()"
                    class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1"
                    :class="{
                        'bg-red-600 hover:bg-red-700 focus:ring-red-500': confirmModal.type === 'danger',
                        'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500': confirmModal.type === 'warning',
                        'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500': confirmModal.type === 'success',
                        'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500': confirmModal.type === 'info'
                    }"
                    x-text="confirmModal.confirmText || 'Ya, Lanjutkan'"
                ></button>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. SINGLE ALERT MODAL (Replaces alert() globally)                         --}}
    {{-- ========================================================================= --}}
    <div
        x-show="alertModal.open"
        x-cloak
        class="fixed inset-0 z-[9998] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="alert-modal-title"
        aria-describedby="alert-modal-desc"
    >
        {{-- Backdrop --}}
        <div
            x-show="alertModal.open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeAlert()"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
        ></div>

        {{-- Modal Dialog Panel --}}
        <div
            x-show="alertModal.open"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 sm:p-7 text-left shadow-2xl transition-all border border-slate-100 z-10"
        >
            <div class="flex items-start gap-4">
                {{-- Alert Icon --}}
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm"
                    :class="{
                        'bg-red-50 text-red-600 ring-8 ring-red-50/50': alertModal.type === 'error',
                        'bg-amber-50 text-amber-600 ring-8 ring-amber-50/50': alertModal.type === 'warning',
                        'bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50': alertModal.type === 'success',
                        'bg-blue-50 text-blue-600 ring-8 ring-blue-50/50': alertModal.type === 'info'
                    }"
                >
                    <template x-if="alertModal.type === 'error'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </template>
                    <template x-if="alertModal.type === 'warning'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>
                    <template x-if="alertModal.type === 'success'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="alertModal.type === 'info'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </template>
                </div>

                {{-- Alert Content --}}
                <div class="flex-1 min-w-0">
                    <h3
                        id="alert-modal-title"
                        class="text-lg font-bold text-slate-900 tracking-tight"
                        x-text="alertModal.title || 'Perhatian'"
                    ></h3>
                    <p
                        id="alert-modal-desc"
                        class="mt-2 text-sm text-slate-600 leading-relaxed break-words"
                        x-text="alertModal.message"
                    ></p>
                </div>
            </div>

            {{-- Alert Button --}}
            <div class="mt-7 flex justify-end">
                <button
                    type="button"
                    @click="closeAlert()"
                    class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                    x-text="alertModal.buttonText || 'Mengerti'"
                ></button>
            </div>
        </div>
    </div>
</div>

<script>
    function globalDialogManager() {
        return {
            toasts: [],
            nextToastId: 1,
            confirmModal: {
                open: false,
                type: 'danger',
                title: '',
                message: '',
                confirmText: 'Ya, Lanjutkan',
                cancelText: 'Batal',
                onConfirm: null,
                onCancel: null,
            },
            alertModal: {
                open: false,
                type: 'info',
                title: '',
                message: '',
                buttonText: 'Mengerti',
                onClose: null,
            },

            initDialogs() {
                // Register global functions
                window.showToast = (config, message, title) => {
                    let toastObj = {};
                    if (typeof config === 'string') {
                        toastObj = {
                            type: config,
                            message: message || '',
                            title: title || ''
                        };
                    } else if (typeof config === 'object') {
                        toastObj = { ...config };
                    }
                    this.addToast(toastObj);
                };

                window.showAlert = (config, message, title) => {
                    let alertObj = {};
                    if (typeof config === 'string') {
                        alertObj = {
                            type: config,
                            message: message || '',
                            title: title || ''
                        };
                    } else if (typeof config === 'object') {
                        alertObj = { ...config };
                    }
                    this.triggerAlert(alertObj);
                };

                window.showConfirm = (config) => {
                    this.triggerConfirm(config || {});
                };

                // Catch Laravel Flash Messages on page load
                this.checkServerFlashMessages();

                // Setup global declarative form confirmation interceptor
                this.setupFormConfirmationInterceptor();
            },

            addToast({ type = 'info', title = '', message = '', duration = 4500 }) {
                const id = this.nextToastId++;
                const toast = {
                    id,
                    type: ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info',
                    title,
                    message,
                    duration,
                    visible: true,
                    paused: false,
                    timer: null,
                    remaining: duration,
                    startTime: Date.now()
                };

                // Limit maximum simultaneous toasts to 4
                if (this.toasts.length >= 4) {
                    this.removeToast(this.toasts[0].id);
                }

                this.toasts.push(toast);

                if (duration > 0) {
                    toast.timer = setTimeout(() => {
                        this.removeToast(id);
                    }, duration);
                }
            },

            pauseToast(toast) {
                if (toast.timer) {
                    clearTimeout(toast.timer);
                    toast.timer = null;
                    toast.remaining -= (Date.now() - toast.startTime);
                    toast.paused = true;
                }
            },

            resumeToast(toast) {
                if (!toast.timer && toast.remaining > 0) {
                    toast.startTime = Date.now();
                    toast.paused = false;
                    toast.timer = setTimeout(() => {
                        this.removeToast(toast.id);
                    }, toast.remaining);
                }
            },

            removeToast(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) {
                    const toast = this.toasts[index];
                    if (toast.timer) clearTimeout(toast.timer);
                    toast.visible = false;
                    setTimeout(() => {
                        const curIdx = this.toasts.findIndex(t => t.id === id);
                        if (curIdx !== -1) this.toasts.splice(curIdx, 1);
                    }, 250);
                }
            },

            triggerConfirm({ type = 'danger', title = '', message = '', confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', onConfirm = null, onCancel = null }) {
                this.confirmModal = {
                    open: true,
                    type,
                    title: title || (type === 'danger' ? 'Hapus Data?' : 'Konfirmasi Tindakan'),
                    message: message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
                    confirmText,
                    cancelText,
                    onConfirm,
                    onCancel
                };
            },

            cancelConfirm() {
                if (typeof this.confirmModal.onCancel === 'function') {
                    try { this.confirmModal.onCancel(); } catch (e) { console.error(e); }
                }
                this.confirmModal.open = false;
            },

            executeConfirm() {
                const cb = this.confirmModal.onConfirm;
                this.confirmModal.open = false;
                if (typeof cb === 'function') {
                    try { cb(); } catch (e) { console.error(e); }
                }
            },

            triggerAlert({ type = 'info', title = '', message = '', buttonText = 'Mengerti', onClose = null }) {
                this.alertModal = {
                    open: true,
                    type: ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info',
                    title: title || (type === 'error' ? 'Terjadi Kesalahan' : (type === 'warning' ? 'Peringatan' : 'Informasi')),
                    message: message || '',
                    buttonText,
                    onClose
                };
            },

            closeAlert() {
                if (typeof this.alertModal.onClose === 'function') {
                    try { this.alertModal.onClose(); } catch (e) { console.error(e); }
                }
                this.alertModal.open = false;
            },

            handleEscape() {
                if (this.confirmModal.open) {
                    this.cancelConfirm();
                } else if (this.alertModal.open) {
                    this.closeAlert();
                }
            },

            checkServerFlashMessages() {
                @if (session('success'))
                    this.addToast({
                        type: 'success',
                        title: 'Berhasil',
                        message: @json(session('success'))
                    });
                @endif

                @if (session('error'))
                    this.addToast({
                        type: 'error',
                        title: 'Gagal',
                        message: @json(session('error'))
                    });
                @endif

                @if (session('warning'))
                    this.addToast({
                        type: 'warning',
                        title: 'Perhatian',
                        message: @json(session('warning'))
                    });
                @endif

                @if (session('info'))
                    this.addToast({
                        type: 'info',
                        title: 'Informasi',
                        message: @json(session('info'))
                    });
                @endif

                @if (session('status'))
                    this.addToast({
                        type: 'info',
                        title: 'Status',
                        message: @json(session('status'))
                    });
                @endif

                @if ($errors->any())
                    this.addToast({
                        type: 'error',
                        title: 'Validasi Gagal',
                        message: 'Terdapat {{ $errors->count() }} kolom formulir yang perlu diperiksa kembali.',
                        duration: 6000
                    });
                @endif
            },

            setupFormConfirmationInterceptor() {
                document.addEventListener('submit', (e) => {
                    const form = e.target;
                    if (!form || !form.hasAttribute('data-confirm')) return;

                    // If user already confirmed in modal, let it submit natively
                    if (form.dataset.confirmed === 'true') {
                        return;
                    }

                    e.preventDefault();

                    const message = form.getAttribute('data-confirm');
                    const title = form.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
                    const type = form.getAttribute('data-confirm-type') || (message.toLowerCase().includes('hapus') || message.toLowerCase().includes('permanen') ? 'danger' : 'warning');
                    const confirmText = form.getAttribute('data-confirm-btn') || (type === 'danger' ? 'Ya, Hapus' : 'Ya, Lanjutkan');
                    const cancelText = form.getAttribute('data-cancel-btn') || 'Batal';

                    this.triggerConfirm({
                        type,
                        title,
                        message,
                        confirmText,
                        cancelText,
                        onConfirm: () => {
                            form.dataset.confirmed = 'true';
                            form.submit();
                        }
                    });
                }, true);
            }
        };
    }
</script>
