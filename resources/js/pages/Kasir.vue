<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';
import {
    Search,
    Plus,
    Minus,
    Trash2,
    CreditCard,
    Banknote,
    CheckCircle,
    XCircle,
    Package,
    User,
    ShoppingCart,
    PauseCircle,
    PlayCircle,
    Printer,
    ScanBarcode,
    Send,
    Volume2,
    VolumeX,
    Calculator,
    Sparkles,
    Tag,
    RotateCcw,
    Camera,
    RefreshCw,
    Zap,
    ZapOff,
    AlertCircle,
    FlipHorizontal
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface BarangItem {
    id_barang: number;
    barcode: string;
    nama: string;
    harga_jual: number;
    stok: number;
    id_kategori: number;
    kategori?: { nama: string } | null;
}

interface KategoriItem {
    id_kategori: number;
    nama: string;
}

interface PelangganItem {
    id_pelanggan: number;
    nama_pelanggan: string;
    telepon: string;
    alamat: string;
}

interface CartItem {
    id_barang: number;
    barcode?: string;
    nama: string;
    harga_jual: number;
    stok: number;
    qty: number;
}

interface ParkedTransaction {
    id: string;
    waktu: string;
    items: CartItem[];
    pelangganId: number | null;
    pelangganNama: string;
    subtotal: number;
}

const props = defineProps<{
    barangList: Array<BarangItem>;
    kategoriList?: Array<KategoriItem>;
    pelangganList: Array<PelangganItem>;
    user: {
        id_user: number;
        nama_lengkap: string;
        id_sekolah: number;
    };
}>();

const page = usePage();
const sekolah = computed(() => page.props.sekolah as { id_sekolah: number; nama_sekolah: string; kode_sekolah: string; alamat?: string; telepon?: string } | null);

const activeTab = ref<'transaksi' | 'pelanggan' | 'antrean'>('transaksi');
const selectedCategory = ref<number | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const soundEnabled = ref(true);
const showNumpad = ref(false);

// Web Audio API Synth Sound Generator (Zero external file dependencies)
const playSound = (type: 'scan' | 'success' | 'error' | 'click') => {
    if (!soundEnabled.value) return;
    try {
        const AudioCtx = window.AudioContext || (window as any).webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);

        const now = ctx.currentTime;

        if (type === 'scan') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, now); // A5
            osc.frequency.exponentialRampToValueAtTime(1320, now + 0.08); // E6
            gain.gain.setValueAtTime(0.15, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.08);
            osc.start(now);
            osc.stop(now + 0.08);
        } else if (type === 'success') {
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(523.25, now); // C5
            osc.frequency.setValueAtTime(659.25, now + 0.1); // E5
            osc.frequency.setValueAtTime(783.99, now + 0.2); // G5
            osc.frequency.setValueAtTime(1046.50, now + 0.3); // C6
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.45);
            osc.start(now);
            osc.stop(now + 0.45);
        } else if (type === 'error') {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, now);
            osc.frequency.setValueAtTime(180, now + 0.1);
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.25);
            osc.start(now);
            osc.stop(now + 0.25);
        } else if (type === 'click') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(600, now);
            gain.gain.setValueAtTime(0.05, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.04);
            osc.start(now);
            osc.stop(now + 0.04);
        }
    } catch {
        // Audio error handle
    }
};

// Search & Category Filtering
const searchProduk = ref('');
const filteredProduk = computed(() => {
    let list = props.barangList;

    if (selectedCategory.value !== null) {
        list = list.filter(b => b.id_kategori === selectedCategory.value);
    }

    if (searchProduk.value) {
        const query = searchProduk.value.toLowerCase();
        list = list.filter(b =>
            b.barcode?.toLowerCase().includes(query) ||
            b.nama.toLowerCase().includes(query)
        );
    }

    return list;
});

// Cart items
const cartItems = ref<CartItem[]>([]);

const addToCart = (barang: BarangItem) => {
    if (barang.stok <= 0) {
        playSound('error');
        toast.error('Stok Habis', { description: `${barang.nama} sudah tidak memiliki stok tersedia` });
        return;
    }
    
    const existing = cartItems.value.find(item => item.id_barang === barang.id_barang);
    if (existing) {
        if (existing.qty >= barang.stok) {
            playSound('error');
            toast.error('Batas Stok', { description: `Stok maksimum ${barang.nama}: ${barang.stok}` });
            return;
        }
        existing.qty++;
    } else {
        cartItems.value.push({
            id_barang: barang.id_barang,
            barcode: barang.barcode,
            nama: barang.nama,
            harga_jual: barang.harga_jual,
            stok: barang.stok,
            qty: 1,
        });
    }

    playSound('scan');
    searchProduk.value = '';
    toast.success('Masuk Keranjang', { description: barang.nama });
};

// Quick enter on barcode scanner
const onSearchEnter = () => {
    if (filteredProduk.value.length === 1) {
        addToCart(filteredProduk.value[0]);
    }
};

// Kamera barcode scanner (getUserMedia + BarcodeDetector & Html5Qrcode fallback)
const showScanner = ref(false);
const scannerError = ref('');
const scannerStarting = ref(false);
const scannerVideoRef = ref<HTMLVideoElement | null>(null);
const availableCameras = ref<MediaDeviceInfo[]>([]);
const currentCameraIndex = ref(0);
const hasTorch = ref(false);
const torchOn = ref(false);
const isMirrored = ref(false);
let mediaStream: MediaStream | null = null;
let lastScannedCode = '';
let lastScannedAt = 0;
let scanTimer: any = null;
let isScanning = false;
let offscreenCanvas: HTMLCanvasElement | null = null;
let zxingDecoder: any = null;

const handleScannedCode = async (code: string) => {
    const clean = code.trim();
    if (!clean) return;
    const exact = props.barangList.find(
        (b) => b.barcode && b.barcode.toLowerCase() === clean.toLowerCase(),
    );
    if (exact) {
        addToCart(exact);
        closeScanner();
    } else {
        // Coba cari dari endpoint kasir scan
        try {
            const res = await fetch(`/kasir/scan/${encodeURIComponent(clean)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data.success && data.barang) {
                addToCart(data.barang);
                closeScanner();
                return;
            }
        } catch {
            // fallback
        }

        searchProduk.value = clean;
        toast.info('Barcode tidak dikenal', { description: `Kode ${clean} tidak cocok produk mana pun` });
        closeScanner();
    }
};

const onScanSuccess = (decodedText: string) => {
    const now = Date.now();
    if (decodedText === lastScannedCode && now - lastScannedAt < 2000) return;
    lastScannedCode = decodedText;
    lastScannedAt = now;
    playSound('scan');
    handleScannedCode(decodedText);
};

const stopCameraTracks = () => {
    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
    if (scannerVideoRef.value) {
        scannerVideoRef.value.srcObject = null;
    }
    torchOn.value = false;
    hasTorch.value = false;
};

const scanLoop = async () => {
    if (!isScanning) return;
    const video = scannerVideoRef.value;
    if (video && video.readyState >= 2 && video.videoWidth > 0) {
        try {
            // 1. Prioritaskan BarcodeDetector bawaan browser jika ada
            if ('BarcodeDetector' in window) {
                const detector = new (window as any).BarcodeDetector({
                    formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'qr_code', 'itf']
                });
                const detected = await detector.detect(video);
                if (detected && detected.length > 0 && detected[0].rawValue) {
                    onScanSuccess(detected[0].rawValue);
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
                    if (!zxingDecoder) {
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
                                ],
                                false,
                                { log: () => {}, logError: () => {}, logWarn: () => {} }
                            );
                        } catch {
                            // ignore import error
                        }
                    }
                    if (zxingDecoder) {
                        try {
                            const res = zxingDecoder.decode(offscreenCanvas);
                            if (res && res.text) {
                                onScanSuccess(res.text);
                                return;
                            }
                        } catch {
                            // frame tanpa barcode
                        }
                    }
                }
            }
        } catch {
            // abaikan error decoding per frame
        }
    }

    if (isScanning) {
        scanTimer = setTimeout(scanLoop, 120);
    }
};

const startScannerCamera = async (deviceId?: string) => {
    scannerError.value = '';
    scannerStarting.value = true;
    stopCameraTracks();

    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Peramban tidak mendukung akses kamera atau koneksi tidak aman (HTTPS / localhost).');
        }

        // Minta izin kamera browser dan arahkan ke kamera belakang jika tersedia
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
            // Fallback jika constraint ideal tidak didukung
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false,
            });
        }

        await nextTick();

        if (scannerVideoRef.value) {
            scannerVideoRef.value.srcObject = mediaStream;
            await scannerVideoRef.value.play();
        }

        // Cek torch / flash capability
        try {
            const track = mediaStream.getVideoTracks()[0];
            if (track) {
                const caps = (track.getCapabilities && track.getCapabilities()) || {};
                hasTorch.value = 'torch' in caps;
            }
        } catch {
            hasTorch.value = false;
        }

        // Ambil daftar kamera untuk fitur ganti kamera
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableCameras.value = devices.filter((d) => d.kind === 'videoinput');
        } catch {
            // ignore
        }

        isScanning = true;
        scanLoop();
    } catch (err: unknown) {
        const msg = err instanceof Error ? err.message : String(err);
        if (/permission|NotAllowed|denied/i.test(msg)) {
            scannerError.value = 'Izin kamera ditolak. Mohon berikan izin kamera di pengaturan browser Anda, lalu coba lagi.';
        } else if (/notfound|NotFound|no camera|devices/i.test(msg)) {
            scannerError.value = 'Kamera tidak ditemukan di perangkat Anda.';
        } else if (/secure|https/i.test(msg)) {
            scannerError.value = 'Kamera membutuhkan koneksi aman (HTTPS). Buka aplikasi lewat HTTPS atau localhost.';
        } else {
            scannerError.value = `Gagal membuka kamera: ${msg}`;
        }
        toast.error('Kamera Gagal', { description: scannerError.value });
    } finally {
        scannerStarting.value = false;
    }
};

const openScanner = () => {
    showScanner.value = true;
    scannerStarting.value = true;
    scannerError.value = '';
    setTimeout(() => {
        startScannerCamera();
    }, 200);
};

const switchCamera = async () => {
    if (availableCameras.value.length <= 1) return;
    currentCameraIndex.value = (currentCameraIndex.value + 1) % availableCameras.value.length;
    const deviceId = availableCameras.value[currentCameraIndex.value].deviceId;
    await startScannerCamera(deviceId);
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
    } catch {
        // torch toggle failed
    }
};

const toggleMirror = () => {
    isMirrored.value = !isMirrored.value;
};

const closeScanner = () => {
    isScanning = false;
    if (scanTimer) {
        clearTimeout(scanTimer);
        scanTimer = null;
    }
    stopCameraTracks();
    lastScannedCode = '';
    showScanner.value = false;
};

const updateQty = (index: number, delta: number) => {
    const item = cartItems.value[index];
    const newQty = item.qty + delta;
    if (newQty <= 0) {
        playSound('click');
        cartItems.value.splice(index, 1);
    } else if (newQty <= item.stok) {
        playSound('click');
        item.qty = newQty;
    } else {
        playSound('error');
        toast.error('Stok Terbatas', { description: `Stok tersedia hanya ${item.stok}` });
    }
};

const removeFromCart = (index: number) => {
    playSound('click');
    cartItems.value.splice(index, 1);
};

// Pelanggan
const selectedPelanggan = ref<number | null>(null);
const searchPelanggan = ref('');
const showAddPelanggan = ref(false);

const pelangganForm = useForm({ nama_pelanggan: '', telepon: '', alamat: '' });

const openAddPelanggan = () => {
    pelangganForm.reset();
    pelangganForm.clearErrors();
    showAddPelanggan.value = true;
};

const submitPelanggan = () => {
    pelangganForm.post('/pelanggan', {
        onSuccess: () => {
            showAddPelanggan.value = false;
            playSound('success');
            toast.success('Pelanggan berhasil ditambahkan');
        },
    });
};

const filteredPelanggan = computed(() => {
    if (!searchPelanggan.value) return props.pelangganList;
    const query = searchPelanggan.value.toLowerCase();
    return props.pelangganList.filter(p =>
        p.nama_pelanggan.toLowerCase().includes(query) ||
        p.telepon?.toLowerCase().includes(query)
    );
});

// Payment & Discount
const discountType = ref<'persen' | 'nominal'>('persen');
const discountValue = ref(0);
const paymentMethod = ref<'tunai' | 'kredit'>('tunai');
const paymentType = ref<'tunai' | 'kredit' | 'qris'>('tunai');
const cashReceived = ref<number>(0);
const isProcessing = ref(false);

const subtotal = computed(() =>
    cartItems.value.reduce((sum, item) => sum + item.harga_jual * item.qty, 0)
);

const discountAmount = computed(() => {
    if (discountType.value === 'persen') {
        return (subtotal.value * discountValue.value) / 100;
    }
    return Math.min(subtotal.value, discountValue.value);
});

const total = computed(() => Math.max(0, subtotal.value - discountAmount.value));

const change = computed(() => {
    if (paymentType.value === 'tunai') {
        return Math.max(0, (cashReceived.value || 0) - total.value);
    }
    return 0;
});

const setQuickCash = (amount: number) => {
    playSound('click');
    cashReceived.value = amount;
};

// Smart rounding calculation based on total
const smartDenominations = computed(() => {
    const t = total.value;
    if (t <= 0) return [10000, 20000, 50000, 100000];

    const result: number[] = [];
    result.push(t); // Uang pas

    // Rounding up to nearest 10,000
    const next10k = Math.ceil(t / 10000) * 10000;
    if (next10k > t && !result.includes(next10k)) result.push(next10k);

    // Rounding up to nearest 20,000
    const next20k = Math.ceil(t / 20000) * 20000;
    if (next20k > t && !result.includes(next20k)) result.push(next20k);

    // Rounding up to nearest 50,000
    const next50k = Math.ceil(t / 50000) * 50000;
    if (next50k > t && !result.includes(next50k)) result.push(next50k);

    // Rounding up to nearest 100,000
    const next100k = Math.ceil(t / 100000) * 100000;
    if (next100k > t && !result.includes(next100k)) result.push(next100k);

    return result.slice(0, 4);
});

// Virtual Touch Numpad Handlers
const appendNumpad = (val: string) => {
    playSound('click');
    const current = String(cashReceived.value || '');
    if (val === 'C') {
        cashReceived.value = 0;
    } else if (val === 'DEL') {
        const sliced = current.slice(0, -1);
        cashReceived.value = sliced ? parseInt(sliced, 10) : 0;
    } else {
        const nextVal = parseInt(current + val, 10);
        cashReceived.value = isNaN(nextVal) ? 0 : nextVal;
    }
};

// Parked / Hold Transactions
const parkedTransactions = ref<ParkedTransaction[]>([]);

const loadParked = () => {
    try {
        const key = `pos_parked_${props.user.id_sekolah}`;
        const saved = localStorage.getItem(key);
        if (saved) parkedTransactions.value = JSON.parse(saved);
    } catch {
        parkedTransactions.value = [];
    }
};

const saveParked = () => {
    try {
        const key = `pos_parked_${props.user.id_sekolah}`;
        localStorage.setItem(key, JSON.stringify(parkedTransactions.value));
    } catch {
        // storage error
    }
};

const parkCurrentTransaction = () => {
    if (cartItems.value.length === 0) {
        playSound('error');
        toast.error('Keranjang Kosong', { description: 'Tidak ada item untuk ditahan' });
        return;
    }

    const pelName = props.pelangganList.find(p => p.id_pelanggan === selectedPelanggan.value)?.nama_pelanggan || 'Pelanggan Umum';
    
    parkedTransactions.value.push({
        id: 'HOLD-' + Date.now().toString().slice(-4),
        waktu: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
        items: [...cartItems.value],
        pelangganId: selectedPelanggan.value,
        pelangganNama: pelName,
        subtotal: subtotal.value,
    });

    saveParked();
    clearCart();
    playSound('success');
    toast.info('Transaksi Ditahan', { description: `Antrean disimpan atas nama ${pelName}` });
};

const restoreParkedTransaction = (item: ParkedTransaction, index: number) => {
    if (cartItems.value.length > 0) {
        if (!confirm('Keranjang saat ini akan digantikan dengan transaksi antrean ini. Lanjutkan?')) {
            return;
        }
    }

    cartItems.value = [...item.items];
    selectedPelanggan.value = item.pelangganId;
    parkedTransactions.value.splice(index, 1);
    saveParked();
    activeTab.value = 'transaksi';
    playSound('scan');
    toast.success('Transaksi Dilanjutkan', { description: `Memuat keranjang ${item.pelangganNama}` });
};

const deleteParkedTransaction = (index: number) => {
    parkedTransactions.value.splice(index, 1);
    saveParked();
    playSound('click');
    toast.success('Antrean dihapus');
};

const clearCart = () => {
    cartItems.value = [];
    selectedPelanggan.value = null;
    discountType.value = 'persen';
    discountValue.value = 0;
    paymentMethod.value = 'tunai';
    paymentType.value = 'tunai';
    cashReceived.value = 0;
};

// Receipt State
const showReceiptModal = ref(false);
const lastReceiptData = ref<{
    nomorFaktur: string;
    tanggal: string;
    items: CartItem[];
    subtotal: number;
    diskon: number;
    total: number;
    bayar: number;
    kembalian: number;
    metode: string;
    kasir: string;
    pelanggan: string;
    pelangganTelepon: string;
} | null>(null);

const processPayment = async () => {
    if (cartItems.value.length === 0) {
        playSound('error');
        toast.error('Keranjang Kosong', { description: 'Pilih produk terlebih dahulu' });
        return;
    }

    if (paymentType.value === 'tunai' && (cashReceived.value || 0) < total.value) {
        playSound('error');
        toast.error('Uang Kurang', { description: 'Jumlah nominal tunai kurang dari total bayar' });
        return;
    }

    isProcessing.value = true;

    try {
        const payload = {
            items: cartItems.value,
            id_pelanggan: selectedPelanggan.value,
            subtotal: subtotal.value,
            discount_type: discountType.value,
            discount_value: discountValue.value,
            discount_amount: discountAmount.value,
            total: total.value,
            payment_method: paymentType.value === 'kredit' ? 'kredit' : 'tunai',
            payment_type: paymentType.value,
            cash_received: paymentType.value === 'tunai' ? cashReceived.value : total.value,
            change: change.value,
        };

        const response = await fetch('/kasir/bayar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const resData = await response.json();

        if (response.ok && resData.success) {
            playSound('success');
            const pel = props.pelangganList.find(p => p.id_pelanggan === selectedPelanggan.value);
            lastReceiptData.value = {
                nomorFaktur: resData.nomor_faktur || 'TRX-SUCCESS',
                tanggal: new Date().toLocaleString('id-ID'),
                items: [...cartItems.value],
                subtotal: subtotal.value,
                diskon: discountAmount.value,
                total: total.value,
                bayar: paymentType.value === 'tunai' ? (cashReceived.value || total.value) : total.value,
                kembalian: change.value,
                metode: paymentType.value.toUpperCase(),
                kasir: props.user.nama_lengkap,
                pelanggan: pel?.nama_pelanggan || 'Umum',
                pelangganTelepon: pel?.telepon || '',
            };

            toast.success('Transaksi Berhasil!', { description: `Faktur: ${lastReceiptData.value.nomorFaktur}` });
            showReceiptModal.value = true;
            clearCart();
        } else {
            playSound('error');
            toast.error('Transaksi Gagal', { description: resData.message || 'Terjadi kesalahan' });
        }
    } catch (err: any) {
        playSound('error');
        toast.error('Error Server', { description: err.message || 'Gagal tersambung' });
    } finally {
        isProcessing.value = false;
    }
};

const paperSize = ref<'58' | '80'>('58');

const printReceipt = () => {
    playSound('click');
    document.body.classList.toggle('print-80mm', paperSize.value === '80');
    window.print();
    // Bersihkan class setelah dialog print ditutup
    setTimeout(() => document.body.classList.remove('print-80mm'), 500);
};

const sendWhatsAppReceipt = () => {
    if (!lastReceiptData.value) return;
    playSound('click');
    const r = lastReceiptData.value;
    
    let text = `*KASIRA - ${sekolah.value?.nama_sekolah || 'STRUK PEMBAYARAN'}*\n`;
    text += `No. Faktur: ${r.nomorFaktur}\n`;
    text += `Tanggal: ${r.tanggal}\n`;
    text += `Kasir: ${r.kasir}\n`;
    text += `Pelanggan: ${r.pelanggan}\n`;
    text += `--------------------------------\n`;
    r.items.forEach(it => {
        text += `${it.nama}\n  ${it.qty}x ${formatCurrency(it.harga_jual)} = ${formatCurrency(it.qty * it.harga_jual)}\n`;
    });
    text += `--------------------------------\n`;
    text += `Subtotal: ${formatCurrency(r.subtotal)}\n`;
    if (r.diskon > 0) text += `Diskon: -${formatCurrency(r.diskon)}\n`;
    text += `*TOTAL: ${formatCurrency(r.total)}*\n`;
    text += `Metode: ${r.metode}\n`;
    text += `Bayar: ${formatCurrency(r.bayar)}\n`;
    text += `Kembalian: ${formatCurrency(r.kembalian)}\n`;
    text += `--------------------------------\n`;
    text += `Terima kasih atas kunjungan Anda!`;

    const phone = r.pelangganTelepon ? r.pelangganTelepon.replace(/^0/, '62').replace(/[^0-9]/g, '') : '';
    const url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
};

// Keyboard Hotkey Listener
const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'F2') {
        e.preventDefault();
        processPayment();
    } else if (e.key === 'F4') {
        e.preventDefault();
        searchInputRef.value?.focus();
    } else if (e.key === 'F8') {
        e.preventDefault();
        parkCurrentTransaction();
    }
};

onMounted(() => {
    loadParked();
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    closeScanner();
});

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <Head title="Transaksi Kasir - KASIRA" />

    <div class="space-y-3.5">
        <!-- Top Action Bar -->
        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between bg-white dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-500 text-white shadow-sm font-black text-sm">
                    KA
                </div>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                        KASIRA Kasir
                        <Badge variant="outline" class="text-[10px] bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 font-semibold border-emerald-200">
                            {{ sekolah?.nama_sekolah || 'Toko Aktif' }}
                        </Badge>
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        Pintas: <kbd class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-[10px] font-mono">F4</kbd> Cari | <kbd class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-[10px] font-mono">F8</kbd> Tahan | <kbd class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-[10px] font-mono">F2</kbd> Bayar
                    </p>
                </div>
            </div>

            <!-- Controls: Sound Toggle + Navigation Tabs -->
            <div class="flex items-center gap-2">
                <Button
                    size="sm"
                    variant="ghost"
                    class="h-8 w-8 p-0"
                    :class="soundEnabled ? 'text-emerald-600 hover:bg-emerald-50' : 'text-gray-400 hover:bg-gray-100'"
                    :title="soundEnabled ? 'Audio Suara Aktif' : 'Audio Suara Nonaktif'"
                    @click="soundEnabled = !soundEnabled"
                >
                    <Volume2 v-if="soundEnabled" class="size-4" />
                    <VolumeX v-else class="size-4" />
                </Button>

                <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                    <Button
                        size="sm"
                        :variant="activeTab === 'transaksi' ? 'default' : 'ghost'"
                        class="h-7 text-xs font-medium"
                        :class="activeTab === 'transaksi' ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm' : ''"
                        @click="activeTab = 'transaksi'"
                    >
                        <ShoppingCart class="mr-1.5 size-3.5" />
                        Kasir POS
                    </Button>
                    <Button
                        size="sm"
                        :variant="activeTab === 'antrean' ? 'default' : 'ghost'"
                        class="h-7 text-xs font-medium relative"
                        :class="activeTab === 'antrean' ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm' : ''"
                        @click="activeTab = 'antrean'"
                    >
                        <PauseCircle class="mr-1.5 size-3.5" />
                        Tahan
                        <span v-if="parkedTransactions.length > 0" class="ml-1 rounded-full bg-amber-500 px-1.5 py-0.2 text-[10px] text-white">
                            {{ parkedTransactions.length }}
                        </span>
                    </Button>
                    <Button
                        size="sm"
                        :variant="activeTab === 'pelanggan' ? 'default' : 'ghost'"
                        class="h-7 text-xs font-medium"
                        :class="activeTab === 'pelanggan' ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm' : ''"
                        @click="activeTab = 'pelanggan'"
                    >
                        <User class="mr-1.5 size-3.5" />
                        Member
                    </Button>
                </div>
            </div>
        </div>

        <!-- TAB: TRANSAKSI POS -->
        <div v-show="activeTab === 'transaksi'" class="grid gap-4 lg:grid-cols-12">
            <!-- Left 7 Cols: Search, Category Pills & Product Grid -->
            <div class="lg:col-span-7 space-y-3">
                <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <CardContent class="p-3.5 space-y-3">
                        <!-- Barcode / Search Input -->
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    ref="searchInputRef"
                                    v-model="searchProduk"
                                    type="text"
                                    placeholder="Scan Barcode / Cari nama produk (Tekan Enter)..."
                                    class="pl-9 h-10 text-sm font-medium border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                                    @keydown.enter.prevent="onSearchEnter"
                                />
                            </div>
                            <Button
                                type="button"
                                class="h-10 shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white"
                                title="Scan barcode pakai kamera"
                                @click="openScanner"
                            >
                                <ScanBarcode class="size-4 sm:mr-1.5" />
                                <span class="hidden sm:inline text-xs font-semibold">Scan</span>
                            </Button>
                        </div>

                        <!-- Category Filter Pills -->
                        <div v-if="kategoriList && kategoriList.length > 0" class="flex gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                            <Button
                                size="sm"
                                :variant="selectedCategory === null ? 'default' : 'outline'"
                                class="h-7 text-xs px-2.5 rounded-full shrink-0 font-medium"
                                :class="selectedCategory === null ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-50 dark:bg-gray-800'"
                                @click="selectedCategory = null"
                            >
                                Semua
                            </Button>
                            <Button
                                v-for="kat in kategoriList"
                                :key="kat.id_kategori"
                                size="sm"
                                :variant="selectedCategory === kat.id_kategori ? 'default' : 'outline'"
                                class="h-7 text-xs px-2.5 rounded-full shrink-0 font-medium"
                                :class="selectedCategory === kat.id_kategori ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-50 dark:bg-gray-800'"
                                @click="selectedCategory = kat.id_kategori"
                            >
                                <Tag class="mr-1 size-3" />
                                {{ kat.nama }}
                            </Button>
                        </div>

                        <!-- Product Cards Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-[380px] overflow-y-auto p-0.5">
                            <div
                                v-for="barang in filteredProduk"
                                :key="barang.id_barang"
                                class="group flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-2.5 hover:border-emerald-500 hover:shadow-md dark:border-gray-800 dark:bg-gray-800/60 cursor-pointer transition-all active:scale-[0.98]"
                                @click="addToCart(barang)"
                            >
                                <div>
                                    <div class="flex items-center justify-between gap-1 pb-1">
                                        <Badge variant="outline" class="text-[9px] px-1 py-0 truncate max-w-[80px]">
                                            {{ barang.kategori?.nama || 'Umum' }}
                                        </Badge>
                                        <span class="text-[10px] font-semibold" :class="barang.stok <= 3 ? 'text-rose-500' : 'text-muted-foreground'">
                                            Stok: {{ barang.stok }}
                                        </span>
                                    </div>
                                    <h3 class="font-semibold text-xs sm:text-sm text-gray-900 dark:text-gray-100 line-clamp-2 leading-snug group-hover:text-emerald-600 transition-colors">
                                        {{ barang.nama }}
                                    </h3>
                                    <div v-if="barang.barcode" class="text-[10px] text-gray-400 font-mono truncate">
                                        {{ barang.barcode }}
                                    </div>
                                </div>
                                <div class="mt-2.5 flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-700">
                                    <span class="text-xs sm:text-sm font-extrabold text-emerald-600 dark:text-emerald-400">
                                        {{ formatCurrency(barang.harga_jual) }}
                                    </span>
                                    <div class="flex size-6 items-center justify-center rounded-md bg-emerald-50 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                        <Plus class="size-3.5" />
                                    </div>
                                </div>
                            </div>

                            <div v-if="filteredProduk.length === 0" class="col-span-full py-12 text-center text-xs text-muted-foreground">
                                <Package class="mx-auto size-8 text-gray-300 mb-1" />
                                Tidak ada produk yang sesuai dengan pencarian.
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right 5 Cols: Shopping Cart & Fast Checkout -->
            <div class="lg:col-span-5 space-y-3">
                <!-- Cart Items Table Card -->
                <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <CardHeader class="p-3 pb-2 flex flex-row items-center justify-between border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <ShoppingCart class="size-4 text-emerald-600" />
                            <CardTitle class="text-sm font-bold">Keranjang Belanja</CardTitle>
                            <Badge variant="secondary" class="text-[10px] h-5">{{ cartItems.length }} Item</Badge>
                        </div>
                        <div class="flex items-center gap-1">
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 text-xs text-amber-600 hover:bg-amber-50"
                                :disabled="cartItems.length === 0"
                                title="Tahan Transaksi (F8)"
                                @click="parkCurrentTransaction"
                            >
                                <PauseCircle class="mr-1 size-3.5" /> Tahan
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 text-xs text-rose-600 hover:bg-rose-50"
                                :disabled="cartItems.length === 0"
                                title="Hapus Semua"
                                @click="clearCart"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                    </CardHeader>

                    <!-- Cart Item Rows -->
                    <div class="max-h-52 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800 p-1">
                        <div
                            v-for="(item, idx) in cartItems"
                            :key="item.id_barang"
                            class="flex items-center justify-between gap-2 p-2 hover:bg-gray-50/80 dark:hover:bg-gray-800/40 rounded-lg text-xs"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ item.nama }}</div>
                                <div class="text-[11px] text-muted-foreground">{{ formatCurrency(item.harga_jual) }} / pcs</div>
                            </div>

                            <!-- Qty adjuster -->
                            <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 shrink-0">
                                <button class="px-1.5 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600" @click="updateQty(idx, -1)">
                                    <Minus class="size-3" />
                                </button>
                                <span class="px-2 font-bold text-xs">{{ item.qty }}</span>
                                <button class="px-1.5 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600" @click="updateQty(idx, 1)">
                                    <Plus class="size-3" />
                                </button>
                            </div>

                            <div class="text-right font-bold text-gray-900 dark:text-gray-100 w-20 shrink-0">
                                {{ formatCurrency(item.harga_jual * item.qty) }}
                            </div>

                            <button class="p-1 text-gray-400 hover:text-rose-500 shrink-0" @click="removeFromCart(idx)">
                                <Trash2 class="size-3" />
                            </button>
                        </div>

                        <div v-if="cartItems.length === 0" class="py-8 text-center text-xs text-muted-foreground">
                            Keranjang kosong. Klik produk di katalog untuk menambahkan.
                        </div>
                    </div>

                    <!-- Payment Details Area -->
                    <CardContent class="p-3 pt-2 border-t border-gray-100 dark:border-gray-800 space-y-2.5">
                        <!-- Member Selector -->
                        <div class="flex items-center gap-1.5">
                            <Select v-model="selectedPelanggan">
                                <SelectTrigger class="h-7 text-xs flex-1">
                                    <SelectValue placeholder="Member: Pelanggan Umum" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Umum / Tanpa Member</SelectItem>
                                    <SelectItem v-for="pel in props.pelangganList" :key="pel.id_pelanggan" :value="pel.id_pelanggan">
                                        {{ pel.nama_pelanggan }} ({{ pel.telepon || '-' }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button size="sm" variant="outline" class="h-7 text-xs px-2" @click="openAddPelanggan">
                                <Plus class="size-3" />
                            </Button>
                        </div>

                        <!-- Subtotal & Total Display -->
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between text-muted-foreground">
                                <span>Subtotal:</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ formatCurrency(subtotal) }}</span>
                            </div>
                            <div v-if="discountAmount > 0" class="flex justify-between text-emerald-600 font-medium">
                                <span>Diskon:</span>
                                <span>- {{ formatCurrency(discountAmount) }}</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1 border-t border-dashed border-gray-200 dark:border-gray-700">
                                <span class="text-xs font-bold uppercase text-gray-700 dark:text-gray-300">Total Tagihan</span>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(total) }}</span>
                            </div>
                        </div>

                        <!-- Payment Methods: Tunai, QRIS, Kasbon -->
                        <div class="grid grid-cols-3 gap-1 pt-1">
                            <Button
                                size="sm"
                                :variant="paymentType === 'tunai' ? 'default' : 'outline'"
                                class="h-8 text-xs font-semibold"
                                :class="paymentType === 'tunai' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : ''"
                                @click="paymentType = 'tunai'"
                            >
                                <Banknote class="mr-1 size-3.5" /> Tunai
                            </Button>
                            <Button
                                size="sm"
                                :variant="paymentType === 'qris' ? 'default' : 'outline'"
                                class="h-8 text-xs font-semibold"
                                :class="paymentType === 'qris' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : ''"
                                @click="paymentType = 'qris'"
                            >
                                <CreditCard class="mr-1 size-3.5" /> QRIS
                            </Button>
                            <Button
                                size="sm"
                                :variant="paymentType === 'kredit' ? 'default' : 'outline'"
                                class="h-8 text-xs font-semibold"
                                :class="paymentType === 'kredit' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : ''"
                                @click="paymentType = 'kredit'"
                            >
                                Kasbon
                            </Button>
                        </div>

                        <!-- Tunai Cash Input & Smart Recommendations -->
                        <div v-if="paymentType === 'tunai'" class="space-y-1.5 pt-1">
                            <div class="flex items-center gap-1.5">
                                <Input
                                    v-model.number="cashReceived"
                                    type="number"
                                    min="0"
                                    class="h-9 text-base font-bold text-center border-emerald-300 focus:border-emerald-500"
                                    placeholder="Uang Tunai (Rp)"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-9 px-2 text-xs text-gray-600"
                                    :class="showNumpad ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : ''"
                                    title="Tampilkan Keyboard Sentuh Numpad"
                                    @click="showNumpad = !showNumpad"
                                >
                                    <Calculator class="size-4" />
                                </Button>
                            </div>

                            <!-- Smart Rounding Pills -->
                            <div class="flex flex-wrap gap-1">
                                <Button
                                    v-for="amt in smartDenominations"
                                    :key="amt"
                                    size="sm"
                                    variant="outline"
                                    class="h-6 text-[11px] px-2 py-0 bg-gray-50 dark:bg-gray-800 hover:bg-emerald-50 hover:text-emerald-700"
                                    @click="setQuickCash(amt)"
                                >
                                    {{ amt === total ? 'Uang Pas' : formatCurrency(amt) }}
                                </Button>
                            </div>

                            <!-- Virtual Numpad (Tablet / Touch Mode) -->
                            <div v-if="showNumpad" class="grid grid-cols-4 gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                <button v-for="num in ['1','2','3','DEL','4','5','6','C','7','8','9','00','0','000']" :key="num" class="h-8 rounded bg-white dark:bg-gray-700 text-xs font-bold hover:bg-emerald-50 hover:text-emerald-700 shadow-sm" @click="appendNumpad(num)">
                                    {{ num }}
                                </button>
                            </div>

                            <!-- Kembalian Display -->
                            <div class="flex justify-between items-center bg-emerald-50/70 dark:bg-emerald-950/40 p-2 rounded-lg border border-emerald-200 dark:border-emerald-900">
                                <span class="text-xs font-medium text-emerald-900 dark:text-emerald-300">Kembalian:</span>
                                <span class="text-sm font-black text-emerald-700 dark:text-emerald-300">{{ formatCurrency(change) }}</span>
                            </div>
                        </div>

                        <!-- Submit Checkout Button -->
                        <Button
                            class="w-full h-11 text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition-all active:scale-[0.99]"
                            :disabled="cartItems.length === 0 || isProcessing"
                            @click="processPayment"
                        >
                            <CheckCircle class="mr-1.5 size-4" />
                            {{ isProcessing ? 'Memproses Transaksi...' : `Bayar ${formatCurrency(total)} (F2)` }}
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- TAB: ANTREAN / TAHAN TRANSAKSI -->
        <div v-show="activeTab === 'antrean'" class="space-y-3">
            <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base font-semibold">Daftar Transaksi Ditahan (Hold Queue)</CardTitle>
                    <CardDescription class="text-xs">Antrean belanja yang dapat dilanjutkan kapan saja</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="parkedTransactions.length === 0" class="py-12 text-center text-muted-foreground text-xs">
                        <PauseCircle class="mx-auto size-10 text-gray-300 mb-2" />
                        Belum ada antrean transaksi yang ditahan.
                    </div>
                    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="(park, idx) in parkedTransactions"
                            :key="park.id"
                            class="border border-amber-200 bg-amber-50/50 p-3 rounded-xl dark:border-amber-900 dark:bg-amber-950/30 shadow-sm"
                        >
                            <div class="flex items-center justify-between pb-1.5">
                                <span class="text-xs font-bold text-amber-800 dark:text-amber-400">{{ park.id }} ({{ park.waktu }})</span>
                                <Badge variant="outline" class="text-[10px] bg-white dark:bg-gray-900">{{ park.items.length }} Item</Badge>
                            </div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">{{ park.pelangganNama }}</div>
                            <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1">Total: {{ formatCurrency(park.subtotal) }}</div>

                            <div class="mt-3 flex gap-2">
                                <Button
                                    size="sm"
                                    class="flex-1 h-8 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold"
                                    @click="restoreParkedTransaction(park, idx)"
                                >
                                    <PlayCircle class="mr-1 size-3.5" /> Lanjutkan
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-8 text-xs text-rose-600 border-rose-200 hover:bg-rose-50"
                                    @click="deleteParkedTransaction(idx)"
                                >
                                    Hapus
                                </Button>
                            </div>
                        </Card>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- TAB: MEMBER PELANGGAN -->
        <div v-show="activeTab === 'pelanggan'" class="space-y-3">
            <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <div>
                        <CardTitle class="text-base font-semibold">Data Pelanggan & Siswa</CardTitle>
                        <CardDescription class="text-xs">Daftar member toko di sekolah</CardDescription>
                    </div>
                    <Button size="sm" class="bg-emerald-600 text-white hover:bg-emerald-700 text-xs" @click="openAddPelanggan">
                        <Plus class="mr-1.5 size-3.5" /> Tambah Member
                    </Button>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="relative w-full sm:w-80">
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="searchPelanggan"
                            placeholder="Cari nama atau telepon..."
                            class="pl-9 h-8 text-xs"
                        />
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-800">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-2.5">Nama</th>
                                    <th class="px-4 py-2.5">Telepon</th>
                                    <th class="px-4 py-2.5">Alamat / Kelas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="pel in filteredPelanggan" :key="pel.id_pelanggan" class="hover:bg-gray-50/70">
                                    <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-gray-100">{{ pel.nama_pelanggan }}</td>
                                    <td class="px-4 py-2.5 text-xs text-gray-600">{{ pel.telepon || '-' }}</td>
                                    <td class="px-4 py-2.5 text-xs text-gray-500">{{ pel.alamat || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- DIALOG: STRUK THERMAL KASIRA -->
        <Dialog :open="showReceiptModal" @update:open="showReceiptModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-center text-lg font-bold text-gray-900 dark:text-white">
                        Transaksi Berhasil!
                    </DialogTitle>
                    <DialogDescription class="text-center text-xs">
                        Struk pembayaran KASIRA siap dicetak
                    </DialogDescription>
                </DialogHeader>

                <!-- Thermal Receipt Paper Mockup -->
                <div v-if="lastReceiptData" class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 font-mono text-xs text-gray-800 dark:bg-gray-950 dark:text-gray-200 shadow-inner">
                    <div class="text-center pb-2 border-b border-gray-300">
                        <div class="font-extrabold text-sm uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                            KASIRA
                        </div>
                        <div class="font-bold text-xs">{{ sekolah?.nama_sekolah || 'KANTIN / KOPERASI' }}</div>
                        <div class="text-[10px] text-muted-foreground">{{ sekolah?.alamat || '-' }}</div>
                    </div>

                    <div class="py-2 space-y-1 text-[11px]">
                        <div class="flex justify-between"><span>No:</span><span class="font-bold">{{ lastReceiptData.nomorFaktur }}</span></div>
                        <div class="flex justify-between"><span>Tgl:</span><span>{{ lastReceiptData.tanggal }}</span></div>
                        <div class="flex justify-between"><span>Kasir:</span><span>{{ lastReceiptData.kasir }}</span></div>
                        <div class="flex justify-between"><span>Pelanggan:</span><span>{{ lastReceiptData.pelanggan }}</span></div>
                    </div>

                    <div class="border-t border-dashed border-gray-300 py-2 space-y-1.5">
                        <div v-for="it in lastReceiptData.items" :key="it.id_barang" class="flex justify-between">
                            <div>{{ it.nama }} <span class="text-muted-foreground">x{{ it.qty }}</span></div>
                            <div class="font-semibold">{{ formatCurrency(it.harga_jual * it.qty) }}</div>
                        </div>
                    </div>

                    <div class="border-t border-gray-300 pt-2 space-y-1 font-bold">
                        <div class="flex justify-between"><span>Subtotal</span><span>{{ formatCurrency(lastReceiptData.subtotal) }}</span></div>
                        <div v-if="lastReceiptData.diskon > 0" class="flex justify-between text-emerald-600"><span>Diskon</span><span>- {{ formatCurrency(lastReceiptData.diskon) }}</span></div>
                        <div class="flex justify-between text-sm text-emerald-700 dark:text-emerald-400"><span>TOTAL</span><span>{{ formatCurrency(lastReceiptData.total) }}</span></div>
                        <div class="flex justify-between text-xs font-normal"><span>Bayar ({{ lastReceiptData.metode }})</span><span>{{ formatCurrency(lastReceiptData.bayar) }}</span></div>
                        <div class="flex justify-between text-xs font-normal"><span>Kembalian</span><span>{{ formatCurrency(lastReceiptData.kembalian) }}</span></div>
                    </div>

                    <div class="text-center pt-3 text-[10px] text-muted-foreground">
                        *** Terima Kasih Telah Berbelanja ***
                    </div>
                </div>

                <div class="flex items-center justify-center gap-1 pt-1">
                    <span class="text-[11px] text-muted-foreground">Kertas:</span>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-7 px-2.5 text-[11px]"
                        :class="paperSize === '58' ? 'border-emerald-500 text-emerald-700' : ''"
                        @click="paperSize = '58'"
                    >
                        58mm
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-7 px-2.5 text-[11px]"
                        :class="paperSize === '80' ? 'border-emerald-500 text-emerald-700' : ''"
                        @click="paperSize = '80'"
                    >
                        80mm
                    </Button>
                </div>

                <DialogFooter class="flex-col sm:flex-row gap-2 pt-2">
                    <Button variant="outline" class="w-full sm:w-auto text-xs" @click="sendWhatsAppReceipt">
                        <Send class="mr-1.5 size-3.5 text-emerald-600" />
                        WhatsApp
                    </Button>
                    <Button class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold" @click="printReceipt">
                        <Printer class="mr-1.5 size-3.5" />
                        Cetak Struk
                    </Button>
                    <DialogClose as-child>
                        <Button variant="ghost" class="text-xs">Tutup</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- AREA CETAK THERMAL (hanya muncul saat print) -->
        <div v-if="lastReceiptData" id="print-thermal-receipt" aria-hidden="true">
            <div style="text-align:center;font-weight:bold;">
                <div>KASIRA</div>
                <div>{{ sekolah?.nama_sekolah || 'KANTIN / KOPERASI' }}</div>
                <div style="font-weight:normal;">{{ sekolah?.alamat || '' }}</div>
            </div>
            <div>--------------------------------</div>
            <div>No: {{ lastReceiptData.nomorFaktur }}</div>
            <div>Tgl: {{ lastReceiptData.tanggal }}</div>
            <div>Kasir: {{ lastReceiptData.kasir }}</div>
            <div>Plg: {{ lastReceiptData.pelanggan }}</div>
            <div>--------------------------------</div>
            <div v-for="it in lastReceiptData.items" :key="it.id_barang">
                <div>{{ it.nama }}</div>
                <div>{{ it.qty }} x {{ formatCurrency(it.harga_jual) }} = {{ formatCurrency(it.harga_jual * it.qty) }}</div>
            </div>
            <div>--------------------------------</div>
            <div>Subtotal: {{ formatCurrency(lastReceiptData.subtotal) }}</div>
            <div v-if="lastReceiptData.diskon > 0">Diskon: -{{ formatCurrency(lastReceiptData.diskon) }}</div>
            <div style="font-weight:bold;">TOTAL: {{ formatCurrency(lastReceiptData.total) }}</div>
            <div>Bayar ({{ lastReceiptData.metode }}): {{ formatCurrency(lastReceiptData.bayar) }}</div>
            <div>Kembalian: {{ formatCurrency(lastReceiptData.kembalian) }}</div>
            <div>--------------------------------</div>
            <div style="text-align:center;">*** Terima Kasih ***</div>
        </div>

        <!-- DIALOG: TAMBAH PELANGGAN -->
        <Dialog :open="showAddPelanggan" @update:open="showAddPelanggan = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Tambah Member Pelanggan</DialogTitle>
                    <DialogDescription>Daftarkan siswa atau guru untuk transaksi kasir.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitPelanggan" class="grid gap-3 py-2">
                    <div class="grid gap-1.5">
                        <Label for="pel_nama">Nama Lengkap *</Label>
                        <Input id="pel_nama" v-model="pelangganForm.nama_pelanggan" placeholder="Nama siswa / pelanggan" required />
                        <InputError :message="pelangganForm.errors.nama_pelanggan" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="pel_telp">No. WhatsApp</Label>
                        <Input id="pel_telp" v-model="pelangganForm.telepon" placeholder="08xxxxxxxxxx" />
                        <InputError :message="pelangganForm.errors.telepon" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="pel_alamat">Kelas / Keterangan</Label>
                        <Input id="pel_alamat" v-model="pelangganForm.alamat" placeholder="Contoh: Kelas XII RPL 1" />
                        <InputError :message="pelangganForm.errors.alamat" />
                    </div>
                    <DialogFooter class="gap-2 pt-3">
                        <Button type="button" variant="outline" @click="showAddPelanggan = false">Batal</Button>
                        <Button type="submit" :disabled="pelangganForm.processing" class="bg-emerald-600 text-white hover:bg-emerald-700">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- DIALOG: SCAN BARCODE KAMERA -->
        <Dialog :open="showScanner" @update:open="(v) => { if (!v) closeScanner(); }">
            <DialogContent class="sm:max-w-md p-0 overflow-hidden shadow-2xl rounded-2xl">
                <DialogHeader class="p-4 pb-2 border-b border-gray-100 dark:border-gray-800">
                    <DialogTitle class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                        <ScanBarcode class="size-5 text-emerald-600" />
                        Scan Barcode Kamera
                    </DialogTitle>
                    <DialogDescription class="text-xs text-muted-foreground">
                        Arahkan kamera ke barcode produk. Hasil scan otomatis masuk keranjang.
                    </DialogDescription>
                </DialogHeader>

                <div class="relative bg-black min-h-[280px] max-h-[360px] aspect-square flex items-center justify-center overflow-hidden">
                    <!-- Elemen Video Kamera Langsung (Tidak mirror secara default, bisa di-flip) -->
                    <video
                        ref="scannerVideoRef"
                        autoplay
                        playsinline
                        muted
                        class="w-full h-full object-cover transition-transform duration-150"
                        :style="{ transform: isMirrored ? 'scaleX(-1)' : 'none' }"
                    ></video>

                    <!-- Guideline & Laser Overlay saat kamera aktif -->
                    <div v-if="!scannerStarting && !scannerError" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                        <div class="relative w-64 h-36 border-2 border-dashed border-emerald-400/80 rounded-xl flex items-center justify-center bg-emerald-500/5">
                            <!-- Sudut-sudut bidik -->
                            <div class="absolute -top-1 -left-1 w-4 h-4 border-t-2 border-l-2 border-emerald-500 rounded-tl"></div>
                            <div class="absolute -top-1 -right-1 w-4 h-4 border-t-2 border-r-2 border-emerald-500 rounded-tr"></div>
                            <div class="absolute -bottom-1 -left-1 w-4 h-4 border-b-2 border-l-2 border-emerald-500 rounded-bl"></div>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 border-b-2 border-r-2 border-emerald-500 rounded-br"></div>
                            <!-- Laser scan animasi -->
                            <div class="w-full h-0.5 bg-red-500 shadow-[0_0_8px_#ef4444] animate-pulse"></div>
                        </div>
                        <span class="mt-3 text-xs text-white/90 bg-black/60 px-3 py-1 rounded-full font-medium">
                            Posisikan barcode di dalam kotak
                        </span>
                    </div>

                    <!-- Loading / Membuka Kamera -->
                    <div v-if="scannerStarting" class="absolute inset-0 bg-black/85 flex flex-col items-center justify-center gap-2 text-white">
                        <span class="size-6 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent" />
                        <span class="text-xs font-medium">Membuka kamera...</span>
                    </div>

                    <!-- Pesan Error / Izin Ditolak -->
                    <div
                        v-else-if="scannerError"
                        class="absolute inset-0 bg-zinc-900/95 flex flex-col items-center justify-center p-6 text-center text-white"
                        role="alert"
                    >
                        <AlertCircle class="w-10 h-10 text-red-400 mb-2" />
                        <p class="text-xs font-medium mb-4 text-red-300 max-w-xs">{{ scannerError }}</p>
                        <Button type="button" variant="outline" size="sm" @click="startScannerCamera()" class="text-white border-zinc-700 hover:bg-zinc-800 text-xs">
                            Coba Lagi
                        </Button>
                    </div>
                </div>

                <!-- Kontrol Footer: Ganti Kamera & Mirror & Flash & Tutup -->
                <div class="p-3 bg-gray-50 dark:bg-zinc-900/80 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="availableCameras.length > 1"
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

                    <Button type="button" variant="secondary" size="sm" @click="closeScanner" class="text-xs h-8 ml-auto">
                        Tutup Kamera
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>