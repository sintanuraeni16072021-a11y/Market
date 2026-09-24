<script setup lang="ts">
import { ref, watch, onUnmounted, nextTick } from 'vue';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Camera, RefreshCw, Zap, ZapOff, AlertCircle } from 'lucide-vue-next';

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

const scannerContainerId = 'barcode-camera-scanner-view';
let html5QrCode: Html5Qrcode | null = null;

const isScanning = ref(false);
const errorMsg = ref<string | null>(null);
const cameras = ref<Array<{ id: string; label: string }>>([]);
const currentCameraIndex = ref(0);
const torchOn = ref(false);
const hasTorch = ref(false);

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

const stopScanner = async () => {
    if (html5QrCode && isScanning.value) {
        try {
            await html5QrCode.stop();
            await html5QrCode.clear();
        } catch (e) {
            console.error('Error stopping scanner:', e);
        }
    }
    isScanning.value = false;
    torchOn.value = false;
    hasTorch.value = false;
};

const startScanner = async () => {
    errorMsg.value = null;
    await nextTick();

    try {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(scannerContainerId, {
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.DATA_MATRIX,
                    Html5QrcodeSupportedFormats.ITF,
                    Html5QrcodeSupportedFormats.CODABAR,
                ],
                verbose: false,
            });
        }

        const devices = await Html5Qrcode.getCameras();
        if (!devices || devices.length === 0) {
            errorMsg.value = 'Tidak ada kamera yang terdeteksi di perangkat Anda.';
            return;
        }

        cameras.value = devices;

        // Prefer back / environment camera by default
        let selectedCameraId = devices[0].id;
        const backCameraIdx = devices.findIndex(d =>
            d.label.toLowerCase().includes('back') ||
            d.label.toLowerCase().includes('rear') ||
            d.label.toLowerCase().includes('environment') ||
            d.label.toLowerCase().includes('belakang')
        );
        if (backCameraIdx !== -1) {
            currentCameraIndex.value = backCameraIdx;
            selectedCameraId = devices[backCameraIdx].id;
        }

        const config = {
            fps: 15,
            qrbox: { width: 260, height: 160 },
            aspectRatio: 1.0,
        };

        await html5QrCode.start(
            selectedCameraId,
            config,
            (decodedText) => {
                playBeep();
                emit('scan', decodedText);
                if (!props.continuous) {
                    closeModal();
                }
            },
            () => {
                // ignore scanning frame errors
            }
        );

        isScanning.value = true;

        // Check torch capabilities
        try {
            const capabilities = html5QrCode.getRunningTrackCapabilities();
            if (capabilities && 'torch' in capabilities) {
                hasTorch.value = true;
            }
        } catch {
            hasTorch.value = false;
        }
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
    await stopScanner();
    currentCameraIndex.value = (currentCameraIndex.value + 1) % cameras.value.length;
    await startScanner();
};

const toggleTorch = async () => {
    if (!html5QrCode || !isScanning.value || !hasTorch.value) return;
    try {
        torchOn.value = !torchOn.value;
        await html5QrCode.applyVideoConstraints({
            advanced: [{ torch: torchOn.value } as any],
        });
    } catch (e) {
        console.error('Failed to toggle torch:', e);
    }
};

const closeModal = async () => {
    await stopScanner();
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
                <!-- Video scanner container -->
                <div
                    id="barcode-camera-scanner-view"
                    class="w-full aspect-square max-h-[340px] flex items-center justify-center overflow-hidden [&_video]:w-full [&_video]:h-full [&_video]:object-cover"
                ></div>

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

                <Button type="button" variant="secondary" size="sm" @click="closeModal" class="text-xs h-8 ml-auto">
                    Tutup
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
