<script setup lang="ts">
import { ref, watch, onUnmounted, nextTick } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Camera, RefreshCw, Zap, ZapOff, AlertCircle, FlipHorizontal } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title?: string;
        description?: string;
        continuous?: boolean;
    }>(),
    {
        title: 'Scan Barcode Kamera',
        description: 'Arahkan kamera ke barcode atau QR code produk',
        continuous: false,
    }
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'scan', decodedText: string): void;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const isScanning = ref(false);
const errorMsg = ref<string | null>(null);
const cameras = ref<Array<{ id: string; label: string }>>([]);
const currentCameraIndex = ref(0);
const torchOn = ref(false);
const hasTorch = ref(false);
const isMirrored = ref(false);

let mediaStream: MediaStream | null = null;
let scanTimer: any = null;
let lastScannedCode = '';
let lastScannedAt = 0;
let offscreenCanvas: HTMLCanvasElement | null = null;
let zxingDecoder: any = null;
let barcodeDetector: any = null;

const SCAN_FORMATS = ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'qr_code', 'itf', 'data_matrix'];

const ensureZxingDecoder = async () => {
    if (zxingDecoder) return zxingDecoder;
    try {
        const { ZXingHtml5QrcodeDecoder } = await import('html5-qrcode/esm/zxing-html5-qrcode-decoder.js');
        const { Html5QrcodeSupportedFormats } = await import('html5-qrcode');
        zxingDecoder = new ZXingHtml5QrcodeDecoder(
            [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.DATA_MATRIX,
            ],
            false,
            { log: () => {}, logError: () => {}, logWarn: () => {} }
        );
    } catch {
        zxingDecoder = null;
    }
    return zxingDecoder;
};

const ensureBarcodeDetector = async () => {
    if (barcodeDetector || !('BarcodeDetector' in window)) return barcodeDetector;
    try {
        const BD = (window as any).BarcodeDetector;
        let formats = [...SCAN_FORMATS];
        try {
            const supported = await BD.getSupportedFormats();
            if (Array.isArray(supported) && supported.length > 0) {
                const filtered = formats.filter((f) => supported.includes(f));
                if (filtered.length > 0) formats = filtered;
            }
        } catch {
            // pakai daftar default bila query gagal
        }
        barcodeDetector = new BD({ formats });
    } catch {
        barcodeDetector = null;
    }
    return barcodeDetector;
};

const playBeep = () => {
    try {
        const AudioCtx = window.AudioContext || (window as any).webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        const now = ctx.currentTime;
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, now);
        osc.frequency.exponentialRampToValueAtTime(1320, now + 0.08);
        gain.gain.setValueAtTime(0.15, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.08);
        osc.start(now);
        osc.stop(now + 0.08);
    } catch {
        // audio fail safe
    }
};

const stopScanner = () => {
    isScanning.value = false;
    if (scanTimer) {
        clearTimeout(scanTimer);
        scanTimer = null;
    }
    if (mediaStream) {
        mediaStream.getTracks().forEach(t => t.stop());
        mediaStream = null;
    }
    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
    torchOn.value = false;
    hasTorch.value = false;
};

const onScanDetected = (decodedText: string) => {
    const now = Date.now();
    if (decodedText === lastScannedCode && now - lastScannedAt < 1500) return;
    lastScannedCode = decodedText;
    lastScannedAt = now;
    playBeep();
    emit('scan', decodedText);
    if (!props.continuous) {
        closeModal();
    }
};

const scanLoop = async () => {
    if (!isScanning.value) return;
    const video = videoRef.value;
    if (video && video.readyState >= 2 && video.videoWidth > 0) {
        try {
            // 1. BarcodeDetector bawaan browser (instance dipakai ulang)
            const detector = await ensureBarcodeDetector();
            if (detector) {
                const barcodes = await detector.detect(video);
                if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                    onScanDetected(barcodes[0].rawValue);
                    return;
                }
            } else {
                // 2. Fallback ZXing decoder menggunakan canvas
                if (!offscreenCanvas) {
                    offscreenCanvas = document.createElement('canvas');
                }
                offscreenCanvas.width = video.videoWidth;
                offscreenCanvas.height = video.videoHeight;
                const ctx = offscreenCanvas.getContext('2d', { willReadFrequently: true });
                if (ctx) {
                    ctx.drawImage(video, 0, 0, offscreenCanvas.width, offscreenCanvas.height);
                    const decoder = await ensureZxingDecoder();
                    if (decoder) {
                        try {
                            const res = decoder.decode(offscreenCanvas);
                            if (res && res.text) {
                                onScanDetected(res.text);
                                return;
                            }
                        } catch {
                            // no barcode
                        }
                    }
                }
            }
        } catch {
            // ignore scan frame error
        }
    }

    if (isScanning.value) {
        scanTimer = setTimeout(scanLoop, 120);
    }
};

// Scan dari file foto (jalan tanpa izin kamera / di koneksi non-HTTPS)
const onScanPhoto = async (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    try {
        const bitmap = await createImageBitmap(file);
        const canvas = document.createElement('canvas');
        canvas.width = bitmap.width;
        canvas.height = bitmap.height;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) throw new Error('canvas');
        ctx.drawImage(bitmap, 0, 0);
        bitmap.close();

        const detector = await ensureBarcodeDetector();
        if (detector) {
            try {
                const detected = await detector.detect(canvas);
                if (detected && detected.length > 0 && detected[0].rawValue) {
                    playBeep();
                    emit('scan', detected[0].rawValue);
                    if (!props.continuous) closeModal();
                    return;
                }
            } catch {
                // lanjut ke ZXing
            }
        }
        const decoder = await ensureZxingDecoder();
        if (decoder) {
            try {
                const res = decoder.decode(canvas);
                if (res && res.text) {
                    playBeep();
                    emit('scan', res.text);
                    if (!props.continuous) closeModal();
                    return;
                }
            } catch {
                // tidak terbaca
            }
        }
        toast.error('Foto tak terbaca', { description: 'Pastikan barcode jelas, fokus, dan cukup cahaya.' });
    } catch {
        toast.error('Foto tak terbaca', { description: 'Pastikan barcode jelas, fokus, dan cukup cahaya.' });
    }
};

const startScanner = async (deviceId?: string) => {
    errorMsg.value = null;
    stopScanner();
    await nextTick();

    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            errorMsg.value = 'Peramban tidak mendukung akses kamera atau koneksi tidak aman (HTTPS / localhost).';
            return;
        }

        let constraints: MediaStreamConstraints;
        if (deviceId) {
            constraints = {
                video: { deviceId: { exact: deviceId } },
                audio: false,
            };
        } else {
            constraints = {
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            };
        }

        try {
            mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch {
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false,
            });
        }

        await nextTick();

        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
            await videoRef.value.play();
        }

        // Torch support
        try {
            const track = mediaStream.getVideoTracks()[0];
            if (track) {
                const caps = (track.getCapabilities && track.getCapabilities()) || {};
                hasTorch.value = 'torch' in caps;
            }
        } catch {
            hasTorch.value = false;
        }

        // Available cameras
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            cameras.value = devices
                .filter(d => d.kind === 'videoinput')
                .map(d => ({ id: d.deviceId, label: d.label || 'Kamera' }));
        } catch {
            // ignore
        }

        isScanning.value = true;
        scanLoop();
    } catch (err: any) {
        console.error('Camera access error:', err);
        if (err.name === 'NotAllowedError' || err.message?.includes('Permission')) {
            errorMsg.value = 'Izin kamera ditolak. Mohon berikan izin akses kamera di browser Anda.';
        } else {
            errorMsg.value = `Gagal mengakses kamera: ${err.message || 'Terjadi kesalahan'}`;
        }
        isScanning.value = false;
    }
};

const switchCamera = async () => {
    if (cameras.value.length <= 1) return;
    currentCameraIndex.value = (currentCameraIndex.value + 1) % cameras.value.length;
    await startScanner(cameras.value[currentCameraIndex.value].id);
};

const toggleTorch = async () => {
    if (!mediaStream || !hasTorch.value) return;
    try {
        const track = mediaStream.getVideoTracks()[0];
        if (track) {
            torchOn.value = !torchOn.value;
            await (track as any).applyConstraints({
                advanced: [{ torch: torchOn.value }],
            });
        }
    } catch (e) {
        console.error('Failed to toggle torch:', e);
    }
};

const toggleMirror = () => {
    isMirrored.value = !isMirrored.value;
};

const closeModal = () => {
    stopScanner();
    emit('update:open', false);
};

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            // Give DOM time to render dialog
            setTimeout(() => {
                startScanner();
            }, 250);
        } else {
            await stopScanner();
        }
    }
);

onUnmounted(async () => {
    await stopScanner();
});
</script>

<template>
    <Dialog :open="open" @update:open="(v) => !v && closeModal()">
        <DialogContent class="sm:max-w-md bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 p-0 overflow-hidden shadow-2xl rounded-2xl">
            <DialogHeader class="p-4 pb-2 border-b border-zinc-100 dark:border-zinc-800 flex flex-row items-center justify-between">
                <div>
                    <DialogTitle class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <Camera class="w-5 h-5 text-emerald-600" />
                        {{ title }}
                    </DialogTitle>
                    <DialogDescription class="text-xs text-zinc-500 mt-0.5">
                        {{ description }}
                    </DialogDescription>
                </div>
            </DialogHeader>

            <div class="relative bg-black min-h-[300px] flex flex-col items-center justify-center overflow-hidden">
                <!-- Video scanner element -->
                <video
                    ref="videoRef"
                    autoplay
                    playsinline
                    muted
                    class="w-full aspect-square max-h-[340px] object-cover transition-transform duration-150"
                    :style="{ transform: isMirrored ? 'scaleX(-1)' : 'none' }"
                ></video>

                <!-- Laser scanning guideline overlay -->
                <div v-if="isScanning" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                    <div class="relative w-64 h-40 border-2 border-dashed border-emerald-400/80 rounded-xl flex items-center justify-center bg-emerald-500/5 backdrop-blur-[0.5px]">
                        <!-- Corner accents -->
                        <div class="absolute -top-1 -left-1 w-4 h-4 border-t-2 border-l-2 border-emerald-500 rounded-tl"></div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 border-t-2 border-r-2 border-emerald-500 rounded-tr"></div>
                        <div class="absolute -bottom-1 -left-1 w-4 h-4 border-b-2 border-l-2 border-emerald-500 rounded-bl"></div>
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 border-b-2 border-r-2 border-emerald-500 rounded-br"></div>

                        <!-- Animated scanning line -->
                        <div class="w-full h-0.5 bg-red-500 shadow-[0_0_8px_#ef4444] animate-pulse"></div>
                    </div>
                    <span class="mt-3 text-xs text-white/90 bg-black/60 px-3 py-1 rounded-full font-medium tracking-wide">
                        Posisikan barcode di dalam kotak
                    </span>
                </div>

                <!-- Error message overlay -->
                <div v-if="errorMsg" class="absolute inset-0 bg-zinc-900/95 flex flex-col items-center justify-center p-6 text-center text-white">
                    <AlertCircle class="w-10 h-10 text-red-400 mb-2" />
                    <p class="text-sm font-medium mb-4">{{ errorMsg }}</p>
                    <Button variant="outline" size="sm" @click="startScanner" class="text-white border-zinc-700 hover:bg-zinc-800">
                        Coba Lagi
                    </Button>
                </div>
            </div>

            <!-- Controls footer -->
            <div class="p-3 bg-zinc-50 dark:bg-zinc-900/80 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Button
                        v-if="cameras.length > 1"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="switchCamera"
                        class="text-xs h-8 gap-1.5"
                    >
                        <RefreshCw class="w-3.5 h-3.5" />
                        Ganti Kamera
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="toggleMirror"
                        class="text-xs h-8 gap-1.5"
                        :class="{ 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300': isMirrored }"
                        title="Balik orientasi kamera (Mirror / Normal)"
                    >
                        <FlipHorizontal class="w-3.5 h-3.5" />
                        {{ isMirrored ? 'Mirror On' : 'Flip' }}
                    </Button>

                    <Button
                        v-if="hasTorch"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="toggleTorch"
                        class="text-xs h-8 gap-1.5"
                        :class="{ 'bg-amber-100 text-amber-900 border-amber-300': torchOn }"
                    >
                        <Zap v-if="!torchOn" class="w-3.5 h-3.5" />
                        <ZapOff v-else class="w-3.5 h-3.5" />
                        Flash
                    </Button>
                </div>

                    <label
                        for="modal-scan-upload"
                        class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground"
                        title="Ambil foto barcode (jalan tanpa izin kamera)"
                    >
                        <Camera class="w-3.5 h-3.5" />
                        Foto
                    </label>
                    <input
                        id="modal-scan-upload"
                        type="file"
                        accept="image/*"
                        capture="environment"
                        class="hidden"
                        @change="onScanPhoto"
                    />

                    <Button type="button" variant="secondary" size="sm" @click="closeModal" class="text-xs h-8 ml-auto">
                        Tutup
                    </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
