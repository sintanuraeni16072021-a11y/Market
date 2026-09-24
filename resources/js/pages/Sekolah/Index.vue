<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
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
import InputError from '@/components/InputError.vue';
import { Building2, Globe, MapPin, Pencil, Plus, Power, RotateCcw, Search, Trash2, Users, Package, ShoppingCart } from 'lucide-vue-next';

interface SekolahItem {
    id_sekolah: number;
    kode_sekolah: string;
    nama_sekolah: string;
    alamat_sekolah: string | null;
    website: string | null;
    is_active: boolean;
    created_at: string | null;
    user_count: number;
    product_count: number;
    total_penjualan: number;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    sekolahList: Paginator<SekolahItem>;
    filters: { search: string | null; status: string | null };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status != null && props.filters.status !== '' ? String(props.filters.status) : '');

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/sekolah',
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch(status, applyFilters);

const showForm = ref(false);
const editing = ref<SekolahItem | null>(null);
const deleteItem = ref<SekolahItem | null>(null);

const form = useForm({
    kode_sekolah: '',
    nama_sekolah: '',
    alamat_sekolah: '',
    website: '',
    is_active: true,
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showForm.value = true;
};

const openEdit = (item: SekolahItem) => {
    editing.value = item;
    form.kode_sekolah = item.kode_sekolah;
    form.nama_sekolah = item.nama_sekolah;
    form.alamat_sekolah = item.alamat_sekolah ?? '';
    form.website = item.website ?? '';
    form.is_active = item.is_active;
    form.clearErrors();
    showForm.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(`/sekolah/${editing.value.id_sekolah}`, {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post('/sekolah', {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteItem.value) return;
    router.delete(`/sekolah/${deleteItem.value.id_sekolah}`, {
        onFinish: () => {
            deleteItem.value = null;
        },
    });
};

const toggleStatus = (item: SekolahItem) => {
    router.patch(`/sekolah/${item.id_sekolah}/toggle-status`, {}, { preserveScroll: true });
};

const switchToSekolah = (item: SekolahItem) => {
    router.post('/sekolah/switch', { id_sekolah: item.id_sekolah });
};

const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
};

const goToPage = (pageNum: number) => {
    router.get(
        '/sekolah',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            page: pageNum,
        },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Manajemen Sekolah" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Master Sekolah</h1>
                <p class="text-sm text-muted-foreground">Kelola data sekolah / tenant cabang kasir</p>
            </div>
            <Button class="bg-emerald-600 text-white hover:bg-emerald-700" @click="openCreate">
                <Plus class="mr-2 size-4" />
                Tambah Sekolah
            </Button>
        </div>

        <!-- Table Card -->
        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader class="pb-3">
                <CardTitle class="text-lg font-semibold">Daftar Sekolah Terdaftar</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Filters -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:w-80">
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="search"
                            placeholder="Cari kode atau nama sekolah..."
                            class="pl-9"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Select v-model="status">
                            <SelectTrigger class="w-[140px]">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Semua Status</SelectItem>
                                <SelectItem value="1">Aktif</SelectItem>
                                <SelectItem value="0">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-md border border-gray-100 dark:border-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800/50 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Sekolah</th>
                                <th class="px-4 py-3">Alamat & Website</th>
                                <th class="px-4 py-3 text-center">Pengguna</th>
                                <th class="px-4 py-3 text-center">Produk</th>
                                <th class="px-4 py-3 text-right">Total Penjualan</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="item in props.sekolahList.data"
                                :key="item.id_sekolah"
                                class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/40"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 font-bold text-xs">
                                            {{ item.kode_sekolah.slice(0, 3) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ item.nama_sekolah }}</div>
                                            <div class="text-xs text-muted-foreground">Kode: {{ item.kode_sekolah }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1">
                                        <MapPin class="size-3 text-gray-400 shrink-0" />
                                        <span class="truncate max-w-[200px]">{{ item.alamat_sekolah || '-' }}</span>
                                    </div>
                                    <div v-if="item.website" class="mt-0.5 flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                        <Globe class="size-3 shrink-0" />
                                        <span>{{ item.website }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                        <Users class="size-3" />
                                        {{ item.user_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                                        <Package class="size-3" />
                                        {{ item.product_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">
                                    {{ formatRupiah(item.total_penjualan) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :class="item.is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-rose-100 text-rose-800 hover:bg-rose-100 dark:bg-rose-950 dark:text-rose-400'">
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="h-8 px-2 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700"
                                            title="Beralih ke Toko Ini"
                                            @click="switchToSekolah(item)"
                                        >
                                            <RotateCcw class="size-3.5 mr-1" />
                                            <span class="text-xs">Beralih</span>
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="h-8 w-8 p-0 text-blue-600 hover:bg-blue-50 hover:text-blue-700"
                                            title="Edit Sekolah"
                                            @click="openEdit(item)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="h-8 w-8 p-0"
                                            :class="item.is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50'"
                                            :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                            @click="toggleStatus(item)"
                                        >
                                            <Power class="size-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="h-8 w-8 p-0 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                            title="Hapus Sekolah"
                                            @click="deleteItem = item"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.sekolahList.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-muted-foreground">
                                    Tidak ada data sekolah ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="props.sekolahList.last_page > 1" class="flex items-center justify-between pt-2">
                    <p class="text-xs text-muted-foreground">
                        Menampilkan {{ props.sekolahList.data.length }} dari {{ props.sekolahList.total }} sekolah
                    </p>
                    <div class="flex gap-1">
                        <Button
                            v-for="page in props.sekolahList.last_page"
                            :key="page"
                            size="sm"
                            :variant="page === props.sekolahList.current_page ? 'default' : 'outline'"
                            class="h-8 w-8 p-0"
                            :class="page === props.sekolahList.current_page ? 'bg-emerald-600 text-white hover:bg-emerald-700' : ''"
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Form Modal (Create / Edit) -->
        <Dialog v-model:open="showForm">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editing ? 'Edit Sekolah' : 'Tambah Sekolah Baru' }}</DialogTitle>
                    <DialogDescription>
                        {{ editing ? 'Ubah informasi data sekolah di bawah ini.' : 'Isi form berikut untuk mendaftarkan sekolah baru.' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submit" class="grid gap-4 py-2">
                    <div class="grid gap-2">
                        <Label for="kode_sekolah">Kode / NPSN Sekolah *</Label>
                        <Input
                            id="kode_sekolah"
                            v-model="form.kode_sekolah"
                            placeholder="Contoh: SMK01, SMA02"
                            required
                        />
                        <InputError :message="form.errors.kode_sekolah" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="nama_sekolah">Nama Sekolah *</Label>
                        <Input
                            id="nama_sekolah"
                            v-model="form.nama_sekolah"
                            placeholder="Contoh: SMK Negeri 1 Jakarta"
                            required
                        />
                        <InputError :message="form.errors.nama_sekolah" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="alamat_sekolah">Alamat Lengkap</Label>
                        <Input
                            id="alamat_sekolah"
                            v-model="form.alamat_sekolah"
                            placeholder="Jl. Pendidikan No. 10..."
                        />
                        <InputError :message="form.errors.alamat_sekolah" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="website">Website / Link (Opsional)</Label>
                        <Input
                            id="website"
                            v-model="form.website"
                            placeholder="https://smkn1.sch.id"
                        />
                        <InputError :message="form.errors.website" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <Checkbox id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                        <Label for="is_active" class="cursor-pointer text-sm font-medium">Status Aktif</Label>
                    </div>

                    <DialogFooter class="gap-2 pt-4">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Batal</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="form.processing" class="bg-emerald-600 text-white hover:bg-emerald-700">
                            {{ editing ? 'Simpan Perubahan' : 'Tambah Sekolah' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirm Modal -->
        <Dialog :open="!!deleteItem" @update:open="!$event && (deleteItem = null)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Hapus Sekolah</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus atau menonaktifkan <strong>{{ deleteItem?.nama_sekolah }}</strong>?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 pt-2">
                    <Button type="button" variant="outline" @click="deleteItem = null">Batal</Button>
                    <Button type="button" variant="destructive" @click="confirmDelete">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
