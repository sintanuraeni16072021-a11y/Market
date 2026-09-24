<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
import PasswordInput from '@/components/PasswordInput.vue';
import { KeyRound, Pencil, Plus, Power, Search, Trash2 } from 'lucide-vue-next';

interface UserItem {
    id_user: number;
    username: string;
    nama_lengkap: string;
    is_active: boolean;
    id_role: number;
    role: { id_role: number; nama_role: string } | null;
    id_sekolah: number;
    sekolah: { id_sekolah: number; nama_sekolah: string } | null;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    userList: Paginator<UserItem>;
    roleList: Array<{ id_role: number; nama_role: string }>;
    sekolahList: Array<{ id_sekolah: number; nama_sekolah: string }>;
    filters: { search: string | null; role: string | number | null; status: string | null };
    currentUserRole: string;
}>();

const page = usePage();
const currentUserId = (page.props.auth as { user: { id_user: number } }).user.id_user;
const currentSekolahId = (page.props.sekolah as { id_sekolah: number } | null)?.id_sekolah;

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role != null && props.filters.role !== '' ? String(props.filters.role) : '');
const status = ref(props.filters.status != null && props.filters.status !== '' ? String(props.filters.status) : '');

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/user',
        {
            search: search.value || undefined,
            role: role.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch([role, status], applyFilters);

const showForm = ref(false);
const editing = ref<UserItem | null>(null);
const deleteItem = ref<UserItem | null>(null);
const resetItem = ref<UserItem | null>(null);

const form = useForm({
    username: '',
    password: '',
    password_confirmation: '',
    nama_lengkap: '',
    id_role: '' as number | string,
    id_sekolah: '' as number | string,
    is_active: true,
});

const resetForm = useForm({ password: '', password_confirmation: '' });

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    if (currentUserRole !== 'super admin' && currentSekolahId) {
        form.id_sekolah = currentSekolahId;
    }
    showForm.value = true;
};

const openEdit = (item: UserItem) => {
    editing.value = item;
    form.username = item.username;
    form.password = '';
    form.password_confirmation = '';
    form.nama_lengkap = item.nama_lengkap;
    form.id_role = item.id_role;
    form.id_sekolah = item.id_sekolah;
    form.is_active = item.is_active;
    form.clearErrors();
    showForm.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(`/user/${editing.value.id_user}`, {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post('/user', {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteItem.value) return;
    router.delete(`/user/${deleteItem.value.id_user}`, {
        onFinish: () => {
            deleteItem.value = null;
        },
    });
};

const toggleStatus = (item: UserItem) => {
    router.patch(`/user/${item.id_user}/toggle-status`, {}, { preserveScroll: true });
};

const openReset = (item: UserItem) => {
    resetItem.value = item;
    resetForm.reset();
    resetForm.clearErrors();
};

const confirmReset = () => {
    if (!resetItem.value) return;
    resetForm.patch(`/user/${resetItem.value.id_user}/reset-password`, {
        onSuccess: () => {
            resetItem.value = null;
        },
    });
};

const goToPage = (pageNum: number) => {
    router.get(
        '/user',
        {
            search: search.value || undefined,
            role: role.value || undefined,
            status: status.value || undefined,
            page: pageNum,
        },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Kelola Pengguna - KASIRA" />

    <div class="space-y-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kelola Pengguna & Kasir</h1>
                <p class="text-sm text-muted-foreground">Kelola akun admin sekolah, kasir operasional, dan hak akses</p>
            </div>
            <Button class="bg-emerald-600 text-white hover:bg-emerald-700" @click="openCreate">
                <Plus class="mr-2 size-4" />
                Tambah User
            </Button>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Daftar Akun Pengguna</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex flex-col gap-3 md:flex-row">
                    <div class="relative flex-1">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama atau username..."
                            aria-label="Cari user"
                            class="w-full border-gray-300 bg-gray-50 pr-3 pl-10 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <Select v-model="role">
                        <SelectTrigger aria-label="Filter role" class="w-full border-gray-300 bg-gray-50 text-gray-800 md:w-48 data-[placeholder]:text-gray-500">
                            <SelectValue placeholder="Semua Role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Semua Role</SelectItem>
                            <SelectItem v-for="r in roleList" :key="r.id_role" :value="String(r.id_role)">
                                {{ r.nama_role }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="status">
                        <SelectTrigger aria-label="Filter status" class="w-full border-gray-300 bg-gray-50 text-gray-800 md:w-44 data-[placeholder]:text-gray-500">
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
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Username</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Role</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(item, index) in userList.data" :key="item.id_user" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900">
                                    {{ (userList.current_page - 1) * userList.per_page + index + 1 }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ item.nama_lengkap }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ item.username }}</td>
                                <td class="px-4 py-3 text-gray-600 capitalize">{{ item.role?.nama_role ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="item.is_active ? 'default' : 'outline'">
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <Button size="sm" variant="ghost" title="Edit" @click="openEdit(item)">
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button size="sm" variant="ghost" title="Reset password" @click="openReset(item)">
                                            <KeyRound class="size-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            title="Aktif/Nonaktif"
                                            :disabled="item.id_user === currentUserId"
                                            @click="toggleStatus(item)"
                                        >
                                            <Power class="size-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            title="Hapus"
                                            class="text-red-600 hover:text-red-700"
                                            :disabled="item.id_user === currentUserId"
                                            @click="deleteItem = item"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="userList.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada data user.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="userList.last_page > 1" class="flex items-center justify-end gap-1">
                    <Button
                        v-for="pageNum in userList.last_page"
                        :key="pageNum"
                        size="sm"
                        :variant="pageNum === userList.current_page ? 'default' : 'outline'"
                        @click="goToPage(pageNum)"
                    >
                        {{ pageNum }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Dialog :open="showForm" @update:open="(v) => { if (!v) showForm = false; }">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editing ? 'Edit User' : 'Tambah User' }}</DialogTitle>
                    <DialogDescription>Lengkapi data pengguna.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submit" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="nama_lengkap">Nama Lengkap *</Label>
                        <Input
                            id="nama_lengkap"
                            v-model="form.nama_lengkap"
                            required
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.nama_lengkap" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="username">Username *</Label>
                        <Input
                            id="username"
                            v-model="form.username"
                            required
                            autocomplete="off"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.username" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password">Password {{ editing ? '(kosongkan jika tidak diubah)' : '*' }}</Label>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            :required="!editing"
                            autocomplete="new-password"
                            placeholder="Minimal 6 karakter"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Konfirmasi Password {{ editing ? '' : '*' }}</Label>
                        <PasswordInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :required="!editing"
                            autocomplete="new-password"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="id_role">Role *</Label>
                            <Select v-model="form.id_role">
                                <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                                    <SelectValue placeholder="Pilih role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="r in roleList" :key="r.id_role" :value="r.id_role">
                                        <span class="capitalize">{{ r.nama_role }}</span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.id_role" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="id_sekolah">Sekolah *</Label>
                            <Select v-model="form.id_sekolah" :disabled="currentUserRole !== 'super admin'">
                                <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                                    <SelectValue placeholder="Pilih sekolah" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in sekolahList" :key="s.id_sekolah" :value="s.id_sekolah">
                                        {{ s.nama_sekolah }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.id_sekolah" />
                        </div>
                    </div>
                    <div class="flex items-center">
                        <Label for="is_active" class="flex cursor-pointer items-center gap-2">
                            <Checkbox id="is_active" v-model="form.is_active" />
                            <span class="text-sm text-gray-700">Akun aktif</span>
                        </Label>
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
                    <DialogTitle>Nonaktifkan User?</DialogTitle>
                    <DialogDescription>
                        User "{{ deleteItem?.nama_lengkap }}" akan dinonaktifkan. Lanjutkan?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmDelete">Nonaktifkan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="!!resetItem" @update:open="(v) => { if (!v) resetItem = null; }">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Reset Password</DialogTitle>
                    <DialogDescription>
                        Buat password baru untuk "{{ resetItem?.nama_lengkap }}".
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="confirmReset" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="reset_password">Password Baru *</Label>
                        <PasswordInput
                            id="reset_password"
                            v-model="resetForm.password"
                            required
                            autocomplete="new-password"
                            placeholder="Minimal 6 karakter"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="resetForm.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="reset_password_confirmation">Konfirmasi Password *</Label>
                        <PasswordInput
                            id="reset_password_confirmation"
                            v-model="resetForm.password_confirmation"
                            required
                            autocomplete="new-password"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Batal</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="resetForm.processing" class="bg-emerald-600 text-white hover:bg-emerald-700">
                            Reset Password
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
