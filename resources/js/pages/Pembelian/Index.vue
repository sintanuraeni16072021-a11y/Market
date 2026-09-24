<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import PembelianForm, { type PembelianFormInitial } from '@/components/PembelianForm.vue';
import PembelianDetail, { type PembelianDetailData } from '@/components/PembelianDetail.vue';
import { CheckCircle2, Eye, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface Pembelian {
    id_pembelian: number;
    nomor_faktur: string;
    tanggal_faktur: string;
    total_bayar: number;
    status_pembelian: string;
    jenis_transaksi: string;
    cara_bayar: string | null;
    id_supplier: number;
    supplier: { id_supplier: number; nama: string } | null;
    id_user: number;
    user: { id_user: number; nama_lengkap: string } | null;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    pembelianList: Paginator<Pembelian>;
    supplierList: Array<{ id_supplier: number; nama: string }>;
    barangList: Array<{
        id_barang: number;
        barcode: string | null;
        nama: string;
        harga_beli: number;
        satuan: string | null;
        stok: number;
    }>;
    nomorFaktur: string;
    filters: {
        search: string | null;
        tanggal_mulai: string | null;
        tanggal_akhir: string | null;
        supplier: string | number | null;
        status: string | null;
    };
}>();

const search = ref(props.filters.search ?? '');
const tanggalMulai = ref(props.filters.tanggal_mulai ?? '');
const tanggalAkhir = ref(props.filters.tanggal_akhir ?? '');
const supplier = ref(props.filters.supplier != null && props.filters.supplier !== '' ? String(props.filters.supplier) : '');
const status = ref(props.filters.status ?? '');

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const queryParams = (extra: Record<string, unknown> = {}) => ({
    search: search.value || undefined,
    tanggal_mulai: tanggalMulai.value || undefined,
    tanggal_akhir: tanggalAkhir.value || undefined,
    supplier: supplier.value || undefined,
    status: status.value || undefined,
    ...extra,
});

const applyFilters = () => {
    router.get('/pembelian', queryParams(), { preserveState: true, replace: true });
};

watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch([tanggalMulai, tanggalAkhir, supplier, status], applyFilters);

const showForm = ref(false);
const editingInitial = ref<PembelianFormInitial | undefined>(undefined);
const formKey = ref(0);

const openCreate = () => {
    editingInitial.value = undefined;
    formKey.value += 1;
    showForm.value = true;
};

const openEdit = async (item: Pembelian) => {
    try {
        const res = await fetch(`/pembelian/${item.id_pembelian}/json`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error();
        const data: PembelianDetailData = await res.json();
        editingInitial.value = {
            id_pembelian: data.id_pembelian,
            id_supplier: data.id_supplier,
            nomor_faktur: data.nomor_faktur,
            tanggal_faktur: data.tanggal_faktur.slice(0, 10),
            jenis_transaksi: data.jenis_transaksi,
            cara_bayar: data.cara_bayar,
            note: data.note,
            items: data.detail_pembelian.map((d) => ({
                id_barang: d.id_barang,
                satuan: d.satuan,
                jumlah: d.jumlah,
                harga_beli: d.harga_beli,
            })),
        };
        formKey.value += 1;
        showForm.value = true;
    } catch {
        toast.error('Gagal memuat data pembelian');
    }
};

const detailItem = ref<PembelianDetailData | null>(null);
const loadingDetail = ref(false);

const openDetail = async (item: Pembelian) => {
    loadingDetail.value = true;
    detailItem.value = null;
    try {
        const res = await fetch(`/pembelian/${item.id_pembelian}/json`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error();
        detailItem.value = await res.json();
    } catch {
        toast.error('Gagal memuat detail pembelian');
    } finally {
        loadingDetail.value = false;
    }
};

const deleteItem = ref<Pembelian | null>(null);

const confirmDelete = () => {
    if (!deleteItem.value) return;
    router.delete(`/pembelian/${deleteItem.value.id_pembelian}`, {
        onFinish: () => {
            deleteItem.value = null;
        },
    });
};

const selesaikan = (item: Pembelian) => {
    router.patch(`/pembelian/${item.id_pembelian}/selesaikan`, {}, { preserveScroll: true });
};

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

const goToPage = (page: number) => {
    router.get('/pembelian', queryParams({ page }), { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Pembelian & Restock - KASIRA" />

    <div class="space-y-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Pembelian & Restock Barang</h1>
                <p class="text-sm text-muted-foreground">Catat transaksi pengadaan barang masuk dari supplier</p>
            </div>
            <Button class="bg-emerald-600 text-white hover:bg-emerald-700" @click="openCreate">
                <Plus class="mr-2 size-4" />
                Tambah Pembelian
            </Button>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Riwayat Faktur Pembelian</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
                    <div class="relative lg:col-span-2">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Cari nomor faktur..."
                            aria-label="Cari nomor pembelian"
                            class="w-full border-gray-300 bg-gray-50 pr-3 pl-10 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <div class="grid gap-1">
                        <Label for="tanggal_mulai" class="text-xs text-gray-500">Tanggal mulai</Label>
                        <Input
                            id="tanggal_mulai"
                            v-model="tanggalMulai"
                            type="date"
                            class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <div class="grid gap-1">
                        <Label for="tanggal_akhir" class="text-xs text-gray-500">Tanggal akhir</Label>
                        <Input
                            id="tanggal_akhir"
                            v-model="tanggalAkhir"
                            type="date"
                            class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <div class="grid items-end gap-1">
                        <Select v-model="supplier">
                            <SelectTrigger aria-label="Filter supplier" class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                                <SelectValue placeholder="Semua Supplier" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Semua Supplier</SelectItem>
                                <SelectItem v-for="s in supplierList" :key="s.id_supplier" :value="String(s.id_supplier)">
                                    {{ s.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <Select v-model="status">
                        <SelectTrigger aria-label="Filter status" class="w-full border-gray-300 bg-gray-50 text-gray-800 md:w-48 data-[placeholder]:text-gray-500">
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Semua Status</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="selesai">Selesai</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">No. Faktur</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Supplier</th>
                                <th class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(item, index) in pembelianList.data" :key="item.id_pembelian" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900">
                                    {{ (pembelianList.current_page - 1) * pembelianList.per_page + index + 1 }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ item.nomor_faktur }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatDate(item.tanggal_faktur) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ item.supplier?.nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(item.total_bayar) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="item.status_pembelian === 'selesai' ? 'default' : 'secondary'" class="capitalize">
                                        {{ item.status_pembelian }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <Button size="sm" variant="ghost" title="Detail" @click="openDetail(item)">
                                            <Eye class="size-4" />
                                        </Button>
                                        <Button
                                            v-if="item.status_pembelian === 'draft'"
                                            size="sm"
                                            variant="ghost"
                                            title="Edit"
                                            @click="openEdit(item)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            v-if="item.status_pembelian !== 'selesai'"
                                            size="sm"
                                            variant="ghost"
                                            title="Selesaikan"
                                            class="text-emerald-600 hover:text-emerald-700"
                                            @click="selesaikan(item)"
                                        >
                                            <CheckCircle2 class="size-4" />
                                        </Button>
                                        <Button
                                            v-if="item.status_pembelian === 'draft'"
                                            size="sm"
                                            variant="ghost"
                                            title="Hapus"
                                            class="text-red-600 hover:text-red-700"
                                            @click="deleteItem = item"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pembelianList.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada data pembelian.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pembelianList.last_page > 1" class="flex items-center justify-end gap-1">
                    <Button
                        v-for="page in pembelianList.last_page"
                        :key="page"
                        size="sm"
                        :variant="page === pembelianList.current_page ? 'default' : 'outline'"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Popup tambah/edit -->
        <Dialog :open="showForm" @update:open="(v) => { if (!v) showForm = false; }">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>{{ editingInitial ? 'Edit Pembelian' : 'Tambah Pembelian' }}</DialogTitle>
                    <DialogDescription>
                        {{ editingInitial ? 'Ubah faktur pembelian (Draft).' : 'Buat faktur pembelian baru (status Draft).' }}
                    </DialogDescription>
                </DialogHeader>
                <PembelianForm
                    :key="editingInitial ? `edit-${editingInitial.id_pembelian}` : `new-${formKey}`"
                    :initial="editingInitial"
                    :supplier-list="supplierList"
                    :barang-list="barangList"
                    :nomor-faktur="nomorFaktur"
                    @success="showForm = false"
                />
            </DialogContent>
        </Dialog>

        <!-- Popup detail -->
        <Dialog :open="loadingDetail || !!detailItem" @update:open="(v) => { if (!v) detailItem = null; }">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Detail Pembelian {{ detailItem ? `· ${detailItem.nomor_faktur}` : '' }}</DialogTitle>
                    <DialogDescription>Rincian faktur dan item pembelian.</DialogDescription>
                </DialogHeader>
                <div v-if="loadingDetail" class="flex items-center justify-center gap-2 py-10 text-gray-500">
                    <Spinner class="size-5" />
                    Memuat detail...
                </div>
                <PembelianDetail v-else-if="detailItem" :pembelian="detailItem" />
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Tutup</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Popup konfirmasi hapus -->
        <Dialog :open="!!deleteItem" @update:open="(v) => { if (!v) deleteItem = null; }">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Hapus Pembelian?</DialogTitle>
                    <DialogDescription>
                        Faktur "{{ deleteItem?.nomor_faktur }}" akan dihapus. Lanjutkan?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmDelete">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
