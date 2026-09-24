<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
import { FileSpreadsheet, Printer } from 'lucide-vue-next';

const props = defineProps<{
    jenisLaporan: string;
    tanggalMulai: string;
    tanggalAkhir: string;
    data: { list: Array<Record<string, unknown>>; summary: Record<string, number> };
}>();

const jenis = ref(props.jenisLaporan);
const tanggalMulai = ref(props.tanggalMulai);
const tanggalAkhir = ref(props.tanggalAkhir);

const applyFilters = () => {
    router.get(
        '/laporan',
        {
            jenis: jenis.value,
            tanggal_mulai: tanggalMulai.value || undefined,
            tanggal_akhir: tanggalAkhir.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch([jenis, tanggalMulai, tanggalAkhir], applyFilters);

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value ?? 0));

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

const summaryLabels: Record<string, Record<string, string>> = {
    penjualan: {
        total_transaksi: 'Total Transaksi',
        total_omzet: 'Total Omzet',
        total_diskon: 'Total Diskon',
        tunai: 'Tunai',
        kredit: 'Kredit',
    },
    pembelian: {
        total_transaksi: 'Total Faktur',
        total_belanja: 'Total Belanja',
        draft: 'Draft',
        selesai: 'Selesai',
    },
    stok: {
        total_produk: 'Total Produk',
        total_nilai_stok: 'Nilai Stok',
        stok_habis: 'Stok Habis',
        stok_rendah: 'Stok Rendah',
    },
    pelanggan: {
        total_pelanggan: 'Total Pelanggan',
        pelanggan_aktif: 'Pelanggan Aktif',
    },
    produk_terlaris: {
        total_produk_terjual: 'Item Terjual',
        total_omzet: 'Total Omzet',
    },
    laba_rugi: {
        total_omzet: 'Total Omzet',
        total_hpp: 'Total HPP',
        total_laba: 'Laba Kotor',
    },
};

const moneyKeys = ['total_omzet', 'total_belanja', 'total_diskon', 'tunai', 'kredit', 'total_nilai_stok', 'total_hpp', 'total_laba'];

const exportUrl = computed(() => {
    const params = new URLSearchParams({
        jenis: jenis.value,
        tanggal_mulai: tanggalMulai.value,
        tanggal_akhir: tanggalAkhir.value,
    });
    return `/laporan/export?${params.toString()}`;
});
</script>

<template>
    <Head title="Laporan & Analitik - KASIRA" />

    <div class="space-y-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Laporan & Analitik</h1>
                <p class="text-sm text-muted-foreground">Rekap mutasi kas, penjualan, pembelian, stok barang, dan produk terlaris</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <a :href="exportUrl">
                        <FileSpreadsheet class="mr-2 size-4" />
                        Export CSV
                    </a>
                </Button>
                <Button variant="outline" @click="() => window.print()">
                    <Printer class="mr-2 size-4" />
                    Cetak Laporan
                </Button>
            </div>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardContent class="pt-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-end">
                    <div class="grid gap-1">
                        <Label class="text-xs text-gray-500">Jenis Laporan</Label>
                        <Select v-model="jenis">
                            <SelectTrigger class="w-full border-gray-300 bg-gray-50 text-gray-800 md:w-52">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="penjualan">Penjualan</SelectItem>
                                <SelectItem value="pembelian">Pembelian</SelectItem>
                                <SelectItem value="stok">Stok</SelectItem>
                                <SelectItem value="pelanggan">Pelanggan</SelectItem>
                                <SelectItem value="produk_terlaris">Produk Terlaris</SelectItem>
                                <SelectItem value="laba_rugi">Laba Rugi</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div v-if="jenis !== 'stok'" class="grid gap-1">
                        <Label for="tanggal_mulai" class="text-xs text-gray-500">Tanggal mulai</Label>
                        <Input
                            id="tanggal_mulai"
                            v-model="tanggalMulai"
                            type="date"
                            class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                    <div v-if="jenis !== 'stok'" class="grid gap-1">
                        <Label for="tanggal_akhir" class="text-xs text-gray-500">Tanggal akhir</Label>
                        <Input
                            id="tanggal_akhir"
                            v-model="tanggalAkhir"
                            type="date"
                            class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card
                v-for="(value, key) in data.summary"
                :key="key"
                class="border-gray-200 bg-white shadow-sm"
            >
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">
                        {{ summaryLabels[jenisLaporan]?.[key] ?? key }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ moneyKeys.includes(key) ? formatCurrency(value) : formatNumber(value) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Detail</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <!-- Penjualan -->
                    <table v-if="jenisLaporan === 'penjualan'" class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Faktur</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kasir</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="row in data.list" :key="row.id_penjualan as number" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ row.nomor_faktur }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatDate(row.tanggal_penjualan as string) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ row.kasir }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ row.pelanggan }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(row.total_bayar as number) }}</td>
                                <td class="px-4 py-3 text-gray-600 capitalize">{{ row.status_pembayaran }}</td>
                            </tr>
                            <tr v-if="data.list.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pembelian -->
                    <table v-else-if="jenisLaporan === 'pembelian'" class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Faktur</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="row in data.list" :key="row.id_pembelian as number" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ row.nomor_faktur }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatDate(row.tanggal_faktur as string) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ row.supplier }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(row.total_bayar as number) }}</td>
                                <td class="px-4 py-3 text-gray-600 capitalize">{{ row.status_pembelian }}</td>
                            </tr>
                            <tr v-if="data.list.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Stok -->
                    <table v-else-if="jenisLaporan === 'stok'" class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barcode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stok</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nilai Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="row in data.list" :key="row.id_barang as number" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-600">{{ row.barcode || '-' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ row.nama }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ row.kategori }}</td>
                                <td class="px-4 py-3 text-center text-gray-900">{{ row.stok }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(row.nilai_stok as number) }}</td>
                            </tr>
                            <tr v-if="data.list.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pelanggan -->
                    <table v-else-if="jenisLaporan === 'pelanggan'" class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telepon</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Transaksi</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Belanja</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="row in data.list" :key="row.id_pelanggan as number" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ row.nama_pelanggan }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ row.telepon || '-' }}</td>
                                <td class="px-4 py-3 text-center text-gray-900">{{ row.total_transaksi }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(row.total_belanja as number) }}</td>
                            </tr>
                            <tr v-if="data.list.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Laba rugi -->
                    <table v-else-if="jenisLaporan === 'laba_rugi'" class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Omzet</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">HPP</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Laba Kotor</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="row in data.list" :key="row.tanggal as string" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ formatDate(row.tanggal as string) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ formatCurrency(row.omzet as number) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ formatCurrency(row.hpp as number) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-emerald-700">{{ formatCurrency(row.laba_kotor as number) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ row.margin }}%</td>
                            </tr>
                            <tr v-if="data.list.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Produk terlaris -->
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Harga</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Terjual</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Omzet</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="row in data.list" :key="row.id_barang as number" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ row.nama }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ formatCurrency(row.harga_jual as number) }}</td>
                                <td class="px-4 py-3 text-center text-gray-900">{{ row.total_terjual }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(row.total_penjualan as number) }}</td>
                            </tr>
                            <tr v-if="data.list.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
