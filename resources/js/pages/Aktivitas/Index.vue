<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
import { Search } from 'lucide-vue-next';

interface LogItem {
    id: number;
    aksi: string;
    modul: string;
    deskripsi: string | null;
    ip_address: string | null;
    created_at: string;
    user: string;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    logList: Paginator<LogItem>;
    modulList: string[];
    filters: { search: string | null; modul: string | null };
}>();

const search = ref(props.filters.search ?? '');
const modul = ref(props.filters.modul ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/aktivitas',
        { search: search.value || undefined, modul: modul.value || undefined },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch(modul, applyFilters);

const aksiVariant = (aksi: string) => {
    if (['hapus', 'batal', 'hapus permanen'].includes(aksi.toLowerCase())) return 'destructive';
    if (['tambah', 'buat', 'bayar', 'pelunasan', 'selesaikan'].includes(aksi.toLowerCase())) return 'default';
    return 'secondary';
};

const formatDateTime = (value: string) =>
    new Date(value).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const goToPage = (page: number) => {
    router.get(
        '/aktivitas',
        { search: search.value || undefined, modul: modul.value || undefined, page },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Log Aktivitas" />

    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Log Aktivitas</h1>
            <p class="text-sm text-muted-foreground">Jejak siapa melakukan apa di aplikasi</p>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm dark:bg-gray-900">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Riwayat Aktivitas</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex flex-col gap-3 md:flex-row">
                    <div class="relative flex-1">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Cari user, aksi, atau deskripsi..."
                            aria-label="Cari log"
                            class="w-full border-gray-300 bg-gray-50 pr-3 pl-10 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <Select v-model="modul">
                        <SelectTrigger aria-label="Filter modul" class="w-full border-gray-300 bg-gray-50 text-gray-800 md:w-52 data-[placeholder]:text-gray-500">
                            <SelectValue placeholder="Semua Modul" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Semua Modul</SelectItem>
                            <SelectItem v-for="m in modulList" :key="m" :value="m" class="capitalize">
                                {{ m }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:bg-gray-800/50">
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Aksi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Modul</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:bg-gray-900">
                            <tr v-for="item in logList.data" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ formatDateTime(item.created_at) }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ item.user }}</td>
                                <td class="px-4 py-3">
                                    <Badge :variant="aksiVariant(item.aksi)" class="capitalize">{{ item.aksi }}</Badge>
                                </td>
                                <td class="px-4 py-3 text-gray-600 capitalize">{{ item.modul }}</td>
                                <td class="max-w-72 truncate px-4 py-3 text-gray-500">{{ item.deskripsi || '-' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ item.ip_address || '-' }}</td>
                            </tr>
                            <tr v-if="logList.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada aktivitas tercatat.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logList.last_page > 1" class="flex items-center justify-end gap-1">
                    <Button
                        v-for="page in logList.last_page"
                        :key="page"
                        size="sm"
                        :variant="page === logList.current_page ? 'default' : 'outline'"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
