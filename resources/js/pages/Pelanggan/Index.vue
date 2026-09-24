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

interface Pelanggan {
    id_pelanggan: number;
    nama_pelanggan: string;
    telepon: string | null;
    alamat: string | null;
    id_kelompok_pelanggan: number | null;
    kelompok: { id_kelompok_pelanggan: number; nama_kelompok: string } | null;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    pelangganList: Paginator<Pelanggan>;
    kelompokPelangganList: Array<{ id_kelompok_pelanggan: number; nama_kelompok: string }>;
    filters: { search: string | null };
}>();

const search = ref(props.filters.search ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/pelanggan', { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 400);
});

const showForm = ref(false);
const editing = ref<Pelanggan | null>(null);
const deleteItem = ref<Pelanggan | null>(null);

const form = useForm({
    nama_pelanggan: '',
    telepon: '',
    alamat: '',
    id_kelompok_pelanggan: '' as number | string,
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const openEdit = (item: Pelanggan) => {
    editing.value = item;
    form.nama_pelanggan = item.nama_pelanggan;
    form.telepon = item.telepon ?? '';
    form.alamat = item.alamat ?? '';
    form.id_kelompok_pelanggan = item.id_kelompok_pelanggan ?? '';
    form.clearErrors();
    showForm.value = true;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        id_kelompok_pelanggan: data.id_kelompok_pelanggan === '' ? null : data.id_kelompok_pelanggan,
    }));
    if (editing.value) {
        form.put(`/pelanggan/${editing.value.id_pelanggan}`, {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post('/pelanggan', {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteItem.value) return;
    router.delete(`/pelanggan/${deleteItem.value.id_pelanggan}`, {
        onFinish: () => {
            deleteItem.value = null;
        },
    });
};

const goToPage = (page: number) => {
    router.get(
        '/pelanggan',
        { search: search.value || undefined, page },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Data Pelanggan & Siswa - KASIRA" />

    <div class="space-y-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Data Pelanggan & Siswa</h1>
                <p class="text-sm text-muted-foreground">Kelola data member siswa, guru, dan staf sekolah</p>
            </div>
            <Button class="bg-emerald-600 text-white hover:bg-emerald-700" @click="openCreate">
                <Plus class="mr-2 size-4" />
                Tambah Pelanggan
            </Button>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Daftar Member Terdaftar</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="relative max-w-md">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama atau telepon..."
                        aria-label="Cari pelanggan"
                        class="w-full border-gray-300 bg-gray-50 pr-3 pl-10 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                    />
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Telepon</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Kelompok</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Alamat</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(item, index) in pelangganList.data" :key="item.id_pelanggan" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900">
                                    {{ (pelangganList.current_page - 1) * pelangganList.per_page + index + 1 }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ item.nama_pelanggan }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ item.telepon || '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ item.kelompok?.nama_kelompok ?? '-' }}</td>
                                <td class="max-w-56 truncate px-4 py-3 text-gray-500">{{ item.alamat || '-' }}</td>
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
                            <tr v-if="pelangganList.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada data pelanggan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pelangganList.last_page > 1" class="flex items-center justify-end gap-1">
                    <Button
                        v-for="page in pelangganList.last_page"
                        :key="page"
                        size="sm"
                        :variant="page === pelangganList.current_page ? 'default' : 'outline'"
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
                    <DialogTitle>{{ editing ? 'Edit Pelanggan' : 'Tambah Pelanggan' }}</DialogTitle>
                    <DialogDescription>Lengkapi data pelanggan.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submit" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="nama_pelanggan">Nama Pelanggan *</Label>
                        <Input
                            id="nama_pelanggan"
                            v-model="form.nama_pelanggan"
                            required
                            placeholder="Nama lengkap"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.nama_pelanggan" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="telepon">No. Telepon</Label>
                        <Input
                            id="telepon"
                            v-model="form.telepon"
                            placeholder="08xxxxxxxxxx"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.telepon" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="id_kelompok_pelanggan">Kelompok Pelanggan</Label>
                        <Select v-model="form.id_kelompok_pelanggan">
                            <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                                <SelectValue placeholder="Pilih kelompok (opsional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="k in kelompokPelangganList" :key="k.id_kelompok_pelanggan" :value="k.id_kelompok_pelanggan">
                                    {{ k.nama_kelompok }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.id_kelompok_pelanggan" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="alamat">Alamat</Label>
                        <Input
                            id="alamat"
                            v-model="form.alamat"
                            placeholder="Alamat pelanggan"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.alamat" />
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
                    <DialogTitle>Hapus Pelanggan?</DialogTitle>
                    <DialogDescription>
                        Pelanggan "{{ deleteItem?.nama_pelanggan }}" akan dihapus. Lanjutkan?
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
