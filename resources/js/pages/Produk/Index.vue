<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ProdukForm from '@/components/ProdukForm.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
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
import {
    Eye,
    Pencil,
    Plus,
    Power,
    Search,
    Trash2,
} from 'lucide-vue-next';

interface Barang {
    id_barang: number;
    barcode: string | null;
    nama: string;
    harga_jual: number;
    harga_beli: number;
    stok: number;
    stok_minimum: number;
    satuan: string | null;
    is_active: boolean;
    id_kategori: number | null;
    kategori: { id_kategori: number; nama: string } | null;
    id_kelompok_kategori: number | null;
    kelompok_kategori: { id_kelompok: number; nama_kelompok: string } | null;
    id_supplier: number | null;
    supplier: { id_supplier: number; nama: string } | null;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    barangList: Paginator<Barang>;
    kategoriList: Array<{ id_kategori: number; nama: string }>;
    kelompokKategoriList: Array<{ id_kelompok: number; nama_kelompok: string }>;
    supplierList: Array<{ id_supplier: number; nama: string }>;
    filters: { search: string | null; kategori: string | number | null; status: string | null };
}>();

const showForm = ref(false);
const editingItem = ref<Barang | null>(null);

const openCreate = () => {
    editingItem.value = null;
    showForm.value = true;
};

const openEdit = (item: Barang) => {
    editingItem.value = item;
    showForm.value = true;
};

const search = ref(props.filters.search ?? '');
const kategori = ref(props.filters.kategori != null ? String(props.filters.kategori) : '');
const status = ref(props.filters.status != null && props.filters.status !== '' ? String(props.filters.status) : '');

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/produk',
        {
            search: search.value || undefined,
            kategori: kategori.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch([kategori, status], applyFilters);

const detailItem = ref<Barang | null>(null);
const deleteItem = ref<Barang | null>(null);

const confirmDelete = () => {
    if (!deleteItem.value) return;
    router.delete(`/produk/${deleteItem.value.id_barang}`, {
        onFinish: () => {
            deleteItem.value = null;
        },
    });
};

const toggleStatus = (item: Barang) => {
    router.patch(
        `/produk/${item.id_barang}/toggle-status`,
        {},
        { preserveScroll: true },
    );
};

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const pageNumbers = computed(() => {
    const pages: number[] = [];
    const current = props.barangList.current_page;
    const last = props.barangList.last_page;
    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i);
    }
    return pages;
});

const goToPage = (page: number) => {
    router.get(
        '/produk',
        {
            search: search.value || undefined,
            kategori: kategori.value || undefined,
            status: status.value || undefined,
            page,
        },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Katalog Produk - KASIRA" />

    <div class="space-y-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Katalog & Stok Produk</h1>
                <p class="text-sm text-muted-foreground">Kelola daftar produk, barcode, harga jual, dan persediaan stok</p>
            </div>
            <Button class="bg-emerald-600 text-white hover:bg-emerald-700" @click="openCreate">
                <Plus class="mr-2 size-4" />
                Tambah Produk
            </Button>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Daftar Produk Terdaftar</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex flex-col gap-3 md:flex-row">
                    <div class="relative flex-1">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Cari produk, barcode..."
                            aria-label="Cari produk"
                            class="w-full border-gray-300 bg-gray-50 pr-3 pl-10 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <Select v-model="kategori">
                        <SelectTrigger
                            aria-label="Filter kategori"
                            class="w-full border-gray-300 bg-gray-50 text-gray-800 md:w-56 data-[placeholder]:text-gray-500"
                        >
                            <SelectValue placeholder="Semua Kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Semua Kategori</SelectItem>
                            <SelectItem
                                v-for="k in kategoriList"
                                :key="k.id_kategori"
                                :value="String(k.id_kategori)"
                            >
                                {{ k.nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="status">
                        <SelectTrigger
                            aria-label="Filter status"
                            class="w-full border-gray-300 bg-gray-50 text-gray-800 md:w-44 data-[placeholder]:text-gray-500"
                        >
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Semua Status</SelectItem>
                            <SelectItem value="1">Aktif</SelectItem>
                            <SelectItem value="0">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Barcode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Nama Produk</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Harga Jual</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Stok</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="(item, index) in barangList.data"
                                :key="item.id_barang"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-4 py-3 text-gray-900">
                                    {{ (barangList.current_page - 1) * barangList.per_page + index + 1 }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ item.barcode || '-' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ item.nama }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ item.kategori?.nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">
                                    {{ formatCurrency(item.harga_jual) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="item.stok <= item.stok_minimum ? 'destructive' : 'secondary'" :title="`Stok minimum: ${item.stok_minimum}`">
                                        {{ item.stok }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="item.is_active ? 'default' : 'outline'">
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <Button size="sm" variant="ghost" title="Detail" @click="detailItem = item">
                                            <Eye class="size-4" />
                                        </Button>
                                        <Button size="sm" variant="ghost" title="Edit" @click="openEdit(item)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button size="sm" variant="ghost" title="Aktif/Nonaktif" @click="toggleStatus(item)">
                                            <Power class="size-4" />
                                        </Button>
                                        <Button
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
                            <tr v-if="barangList.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    Tidak ada data produk.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="barangList.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 md:flex-row"
                >
                    <p class="text-sm text-gray-500">
                        Menampilkan {{ barangList.data.length }} dari {{ barangList.total }} produk
                    </p>
                    <div class="flex items-center gap-1">
                        <Button
                            v-for="page in pageNumbers"
                            :key="page"
                            size="sm"
                            :variant="page === barangList.current_page ? 'default' : 'outline'"
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Modal tambah/edit -->
        <Dialog :open="showForm" @update:open="(v) => { if (!v) showForm = false; }">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ editingItem ? 'Edit Produk' : 'Tambah Produk' }}</DialogTitle>
                    <DialogDescription>Lengkapi data produk di bawah ini.</DialogDescription>
                </DialogHeader>
                <ProdukForm
                    :key="editingItem ? editingItem.id_barang : 'new'"
                    :initial="
                        editingItem
                            ? {
                                  id_barang: editingItem.id_barang,
                                  barcode: editingItem.barcode,
                                  nama: editingItem.nama,
                                  id_kategori: editingItem.id_kategori,
                                  id_kelompok_kategori: editingItem.id_kelompok_kategori,
                                  id_supplier: editingItem.id_supplier,
                                  satuan: editingItem.satuan,
                                  harga_beli: editingItem.harga_beli,
                                  harga_jual: editingItem.harga_jual,
                                  stok: editingItem.stok,
                                  stok_minimum: editingItem.stok_minimum,
                                  is_active: editingItem.is_active,
                              }
                            : undefined
                    "
                    :kategori-list="kategoriList"
                    :kelompok-kategori-list="kelompokKategoriList"
                    :supplier-list="supplierList"
                    @success="showForm = false"
                />
            </DialogContent>
        </Dialog>

        <!-- Modal detail -->
        <Dialog :open="!!detailItem" @update:open="(v) => { if (!v) detailItem = null; }">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Detail Produk</DialogTitle>
                    <DialogDescription>Informasi lengkap produk.</DialogDescription>
                </DialogHeader>
                <dl v-if="detailItem" class="grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
                    <dt class="text-gray-500">Barcode</dt>
                    <dd class="col-span-2 font-medium text-gray-900">{{ detailItem.barcode || '-' }}</dd>
                    <dt class="text-gray-500">Nama</dt>
                    <dd class="col-span-2 font-medium text-gray-900">{{ detailItem.nama }}</dd>
                    <dt class="text-gray-500">Kategori</dt>
                    <dd class="col-span-2 text-gray-700">{{ detailItem.kategori?.nama ?? '-' }}</dd>
                    <dt class="text-gray-500">Kelompok</dt>
                    <dd class="col-span-2 text-gray-700">{{ detailItem.kelompok_kategori?.nama_kelompok ?? '-' }}</dd>
                    <dt class="text-gray-500">Supplier</dt>
                    <dd class="col-span-2 text-gray-700">{{ detailItem.supplier?.nama ?? '-' }}</dd>
                    <dt class="text-gray-500">Satuan</dt>
                    <dd class="col-span-2 text-gray-700">{{ detailItem.satuan || '-' }}</dd>
                    <dt class="text-gray-500">Harga Beli</dt>
                    <dd class="col-span-2 text-gray-700">{{ formatCurrency(detailItem.harga_beli) }}</dd>
                    <dt class="text-gray-500">Harga Jual</dt>
                    <dd class="col-span-2 font-semibold text-emerald-600">{{ formatCurrency(detailItem.harga_jual) }}</dd>
                    <dt class="text-gray-500">Stok</dt>
                    <dd class="col-span-2 text-gray-700">{{ detailItem.stok }}</dd>
                </dl>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Tutup</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal konfirmasi hapus -->
        <Dialog :open="!!deleteItem" @update:open="(v) => { if (!v) deleteItem = null; }">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Hapus Produk?</DialogTitle>
                    <DialogDescription>
                        Produk "{{ deleteItem?.nama }}" akan dihapus. Lanjutkan?
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
