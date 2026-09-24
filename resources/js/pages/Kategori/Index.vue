<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';

interface Kategori {
    id_kategori: number;
    nama: string;
    id_kelompok: number | null;
    kelompok: { id_kelompok: number; nama_kelompok: string } | null;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    kategoriList: Paginator<Kategori>;
    kelompokKategoriList: Array<{ id_kelompok: number; nama_kelompok: string }>;
    filters: { search: string | null };
}>();

const search = ref(props.filters.search ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/kategori', { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 400);
});

const showForm = ref(false);
const editing = ref<Kategori | null>(null);
const deleteItem = ref<Kategori | null>(null);

const form = useForm({ nama: '', id_kelompok: '' as number | string });

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const openEdit = (item: Kategori) => {
    editing.value = item;
    form.nama = item.nama;
    form.id_kelompok = item.id_kelompok ?? '';
    form.clearErrors();
    showForm.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(`/kategori/${editing.value.id_kategori}`, {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post('/kategori', {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteItem.value) return;
    router.delete(`/kategori/${deleteItem.value.id_kategori}`, {
        onFinish: () => {
            deleteItem.value = null;
        },
    });
};

const goToPage = (page: number) => {
    router.get(
        '/kategori',
        { search: search.value || undefined, page },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Kategori Produk - KASIRA" />

    <div class="space-y-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kategori Produk</h1>
                <p class="text-sm text-muted-foreground">Kelola pengelompokan kategori dan jenis barang</p>
            </div>
            <Button class="bg-emerald-600 text-white hover:bg-emerald-700" @click="openCreate">
                <Plus class="mr-2 size-4" />
                Tambah Kategori
            </Button>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Daftar Kategori Produk</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="relative max-w-md">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Cari kategori..."
                        aria-label="Cari kategori"
                        class="w-full border-gray-300 bg-gray-50 pr-3 pl-10 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                    />
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Kelompok Kategori</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(item, index) in kategoriList.data" :key="item.id_kategori" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900">
                                    {{ (kategoriList.current_page - 1) * kategoriList.per_page + index + 1 }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ item.nama }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ item.kelompok?.nama_kelompok ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <Button size="sm" variant="ghost" title="Edit" @click="openEdit(item)">
                                            <Pencil class="size-4" />
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
                            <tr v-if="kategoriList.data.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Tidak ada data kategori.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="kategoriList.last_page > 1" class="flex items-center justify-end gap-1">
                    <Button
                        v-for="page in kategoriList.last_page"
                        :key="page"
                        size="sm"
                        :variant="page === kategoriList.current_page ? 'default' : 'outline'"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Dialog :open="showForm" @update:open="(v) => { if (!v) showForm = false; }">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editing ? 'Edit Kategori' : 'Tambah Kategori' }}</DialogTitle>
                    <DialogDescription>Lengkapi data kategori di bawah ini.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submit" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="nama">Nama Kategori *</Label>
                        <Input
                            id="nama"
                            v-model="form.nama"
                            required
                            placeholder="Contoh: Makanan Ringan"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.nama" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="id_kelompok">Kelompok Kategori *</Label>
                        <Select v-model="form.id_kelompok">
                            <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                                <SelectValue placeholder="Pilih kelompok" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="k in kelompokKategoriList" :key="k.id_kelompok" :value="k.id_kelompok">
                                    {{ k.nama_kelompok }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.id_kelompok" />
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Batal</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="form.processing" class="bg-emerald-600 text-white hover:bg-emerald-700">
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="!!deleteItem" @update:open="(v) => { if (!v) deleteItem = null; }">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Hapus Kategori?</DialogTitle>
                    <DialogDescription>
                        Kategori "{{ deleteItem?.nama }}" akan dihapus. Lanjutkan?
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
