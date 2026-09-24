<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import {
    DollarSign,
    ShoppingCart,
    Package,
    Users,
    Building2,
    TrendingUp,
    ArrowRight,
    ArrowUpRight,
    CheckCircle2,
    RotateCcw
} from 'lucide-vue-next';

interface SekolahStat {
    id_sekolah: number;
    nama_sekolah: string;
    kode_sekolah: string;
    omset: number;
    total_transaksi: number;
}

interface TopProduct {
    id_barang: number;
    nama_barang: string;
    total_qty: number;
    total_nominal: number;
}

interface RecentTrx {
    id_penjualan: number;
    nomor_faktur: string;
    tanggal_penjualan: string;
    total_bayar: number;
    nama_lengkap: string;
    nama_sekolah?: string;
}

const props = defineProps<{
    stats: {
        penjualan_hari_ini: number;
        transaksi_hari_ini: number;
        produk_terjual: number;
        pelanggan_baru: number;
        total_omset: number;
        total_produk: number;
    };
    chartData: Array<{ tanggal: string; total: number }>;
    transaksiTerbaru: Array<RecentTrx>;
    topProduk?: Array<TopProduct>;
    sekolahStats?: Array<SekolahStat>;
    viewMode?: string;
    isSuperAdmin?: boolean;
}>();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateString: string) => {
    try {
        return format(new Date(dateString), 'dd MMM yyyy HH:mm', { locale: id });
    } catch {
        return dateString;
    }
};

const switchViewMode = (mode: string) => {
    router.get('/dashboard', { mode }, { preserveState: true });
};

const maxTotal = computed(() => {
    const max = Math.max(...props.chartData.map(d => d.total), 1);
    return max;
});
</script>

<template>
    <Head title="Dashboard POS" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Dashboard Penjualan
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ isSuperAdmin && viewMode === 'all' ? 'Ikhtisar performa penjualan seluruh cabang sekolah' : 'Ringkasan aktivitas transaksi dan pendapatan toko' }}
                </p>
            </div>

            <!-- View Mode Switcher for Super Admin -->
            <div v-if="isSuperAdmin" class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <Button
                    size="sm"
                    :variant="viewMode === 'all' ? 'default' : 'ghost'"
                    class="h-8 text-xs font-medium"
                    :class="viewMode === 'all' ? 'bg-emerald-600 text-white' : ''"
                    @click="switchViewMode('all')"
                >
                    <Building2 class="mr-1.5 size-3.5" />
                    Semua Sekolah
                </Button>
                <Button
                    size="sm"
                    :variant="viewMode === 'school' ? 'default' : 'ghost'"
                    class="h-8 text-xs font-medium"
                    :class="viewMode === 'school' ? 'bg-emerald-600 text-white' : ''"
                    @click="switchViewMode('school')"
                >
                    Sekolah Aktif
                </Button>
            </div>
        </div>

        <!-- Top Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Penjualan Hari Ini -->
            <Card class="border-gray-200 bg-white shadow-sm transition-all hover:shadow dark:border-gray-800 dark:bg-gray-900">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Penjualan Hari Ini</CardTitle>
                    <div class="rounded-full bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <DollarSign class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(props.stats.penjualan_hari_ini) }}</div>
                    <p class="mt-1 text-xs text-muted-foreground">Total omset tercatat hari ini</p>
                </CardContent>
            </Card>

            <!-- Transaksi Hari Ini -->
            <Card class="border-gray-200 bg-white shadow-sm transition-all hover:shadow dark:border-gray-800 dark:bg-gray-900">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Transaksi Hari Ini</CardTitle>
                    <div class="rounded-full bg-blue-50 p-2 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                        <ShoppingCart class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ props.stats.transaksi_hari_ini }} Struk</div>
                    <p class="mt-1 text-xs text-muted-foreground">Nota penjualan berhasil</p>
                </CardContent>
            </Card>

            <!-- Produk Terjual -->
            <Card class="border-gray-200 bg-white shadow-sm transition-all hover:shadow dark:border-gray-800 dark:bg-gray-900">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Produk Terjual (Item)</CardTitle>
                    <div class="rounded-full bg-purple-50 p-2 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                        <Package class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ props.stats.produk_terjual }} Qty</div>
                    <p class="mt-1 text-xs text-muted-foreground">Jumlah barang keluar</p>
                </CardContent>
            </Card>

            <!-- Total Omset Keseluruhan -->
            <Card class="border-gray-200 bg-white shadow-sm transition-all hover:shadow dark:border-gray-800 dark:bg-gray-900">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Akumulasi Omset</CardTitle>
                    <div class="rounded-full bg-amber-50 p-2 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                        <TrendingUp class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(props.stats.total_omset) }}</div>
                    <p class="mt-1 text-xs text-muted-foreground">Akumulasi total pendapatan</p>
                </CardContent>
            </Card>
        </div>

        <!-- Super Admin: Comparison per School -->
        <div v-if="isSuperAdmin && props.sekolahStats && props.sekolahStats.length > 0" class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white">Performa Cabang Sekolah</h2>
                <Link href="/sekolah" class="flex items-center text-xs font-medium text-emerald-600 hover:underline">
                    Kelola Sekolah <ArrowUpRight class="ml-1 size-3.5" />
                </Link>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="sek in props.sekolahStats"
                    :key="sek.id_sekolah"
                    class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <CardHeader class="pb-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ sek.nama_sekolah }}</CardTitle>
                                <CardDescription class="text-xs">Kode: {{ sek.kode_sekolah }}</CardDescription>
                            </div>
                            <Badge variant="outline" class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                {{ sek.total_transaksi }} Transaksi
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="mt-2 text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency(sek.omset) }}
                        </div>
                        <p class="text-xs text-muted-foreground">Total omset toko</p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Charts & Top Products Row -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- 7 Days Revenue Bar Chart -->
            <Card class="lg:col-span-2 border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <CardHeader>
                    <CardTitle class="text-base font-semibold">Tren Penjualan 7 Hari Terakhir</CardTitle>
                    <CardDescription>Grafik pergerakan omset harian</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex h-56 items-end gap-3 pt-6">
                        <div
                            v-for="day in props.chartData"
                            :key="day.tanggal"
                            class="flex flex-1 flex-col items-center gap-2 h-full justify-end group"
                        >
                            <div class="text-[10px] font-medium text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                {{ formatCurrency(day.total) }}
                            </div>
                            <div
                                class="w-full rounded-t-md bg-emerald-500 transition-all duration-300 group-hover:bg-emerald-600 dark:bg-emerald-600"
                                :style="{ height: `${Math.max((day.total / maxTotal) * 100, 4)}%` }"
                            ></div>
                            <div class="text-xs font-medium text-gray-600 dark:text-gray-400 truncate w-full text-center">
                                {{ day.tanggal }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Selling Products -->
            <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <CardHeader>
                    <CardTitle class="text-base font-semibold">Produk Terlaris</CardTitle>
                    <CardDescription>Barang dengan penjualan terbanyak</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        v-for="(prod, idx) in props.topProduk"
                        :key="prod.id_barang"
                        class="flex items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex size-7 items-center justify-center rounded-full bg-emerald-50 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                {{ idx + 1 }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ prod.nama_barang }}</div>
                                <div class="text-xs text-muted-foreground">{{ prod.total_qty }} terjual</div>
                            </div>
                        </div>
                        <div class="text-xs font-semibold text-gray-900 dark:text-gray-100 shrink-0">
                            {{ formatCurrency(prod.total_nominal) }}
                        </div>
                    </div>
                    <div v-if="!props.topProduk || props.topProduk.length === 0" class="py-6 text-center text-xs text-muted-foreground">
                        Belum ada data produk terjual.
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Transactions Table -->
        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader class="flex flex-row items-center justify-between pb-3">
                <div>
                    <CardTitle class="text-base font-semibold">Transaksi Terakhir</CardTitle>
                    <CardDescription>Daftar 8 transaksi penjualan paling baru</CardDescription>
                </div>
                <Link href="/laporan?jenis=penjualan">
                    <Button variant="ghost" size="sm" class="text-xs text-emerald-600 hover:text-emerald-700">
                        Lihat Semua <ArrowRight class="ml-1 size-3.5" />
                    </Button>
                </Link>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto rounded-md border border-gray-100 dark:border-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800/50 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">No. Faktur</th>
                                <th v-if="isSuperAdmin" class="px-4 py-3">Sekolah</th>
                                <th class="px-4 py-3">Kasir</th>
                                <th class="px-4 py-3">Waktu Transaksi</th>
                                <th class="px-4 py-3 text-right">Total Bayar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="trx in props.transaksiTerbaru"
                                :key="trx.id_penjualan"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40"
                            >
                                <td class="px-4 py-3 font-medium text-emerald-600 dark:text-emerald-400">
                                    {{ trx.nomor_faktur }}
                                </td>
                                <td v-if="isSuperAdmin" class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                    {{ trx.nama_sekolah ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                    {{ trx.nama_lengkap }}
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ formatDate(trx.tanggal_penjualan) }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                    {{ formatCurrency(trx.total_bayar) }}
                                </td>
                            </tr>
                            <tr v-if="props.transaksiTerbaru.length === 0">
                                <td :colspan="isSuperAdmin ? 5 : 4" class="py-6 text-center text-xs text-muted-foreground">
                                    Belum ada transaksi tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>