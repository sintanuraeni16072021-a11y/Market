<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

export interface PembelianDetailData {
    id_pembelian: number;
    nomor_faktur: string;
    tanggal_faktur: string;
    total_bayar: number;
    status_pembelian: string;
    jenis_transaksi: string;
    cara_bayar: string | null;
    note: string | null;
    supplier: { id_supplier: number; nama: string } | null;
    user: { id_user: number; nama_lengkap: string } | null;
    detail_pembelian: Array<{
        id_detail_pembelian: number;
        id_barang: number;
        barang: { id_barang: number; nama: string } | null;
        satuan: string;
        jumlah: number;
        harga_beli: number;
        subtotal: number;
    }>;
}

defineProps<{ pembelian: PembelianDetailData }>();

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value) || 0);

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <Badge :variant="pembelian.status_pembelian === 'selesai' ? 'default' : 'secondary'" class="capitalize">
                {{ pembelian.status_pembelian }}
            </Badge>
            <span class="text-sm text-gray-500">
                {{ pembelian.jenis_transaksi }}{{ pembelian.cara_bayar ? ` · ${pembelian.cara_bayar}` : '' }}
            </span>
        </div>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm md:grid-cols-4">
            <div>
                <dt class="text-gray-500">Supplier</dt>
                <dd class="font-medium text-gray-900">{{ pembelian.supplier?.nama ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Tanggal</dt>
                <dd class="font-medium text-gray-900">{{ formatDate(pembelian.tanggal_faktur) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Dibuat oleh</dt>
                <dd class="font-medium text-gray-900">{{ pembelian.user?.nama_lengkap ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Total</dt>
                <dd class="font-bold text-emerald-600">{{ formatCurrency(pembelian.total_bayar) }}</dd>
            </div>
            <div v-if="pembelian.note" class="col-span-2 md:col-span-4">
                <dt class="text-gray-500">Catatan</dt>
                <dd class="text-gray-700">{{ pembelian.note }}</dd>
            </div>
        </dl>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">No</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Barang</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Satuan</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Jumlah</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Harga Beli</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="(item, index) in pembelian.detail_pembelian" :key="item.id_detail_pembelian">
                        <td class="px-3 py-2 text-gray-600">{{ index + 1 }}</td>
                        <td class="px-3 py-2 font-medium text-gray-900">
                            {{ item.barang?.nama ?? `Barang #${item.id_barang}` }}
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ item.satuan }}</td>
                        <td class="px-3 py-2 text-center text-gray-900">{{ item.jumlah }}</td>
                        <td class="px-3 py-2 text-right text-gray-600">{{ formatCurrency(item.harga_beli) }}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-900">{{ formatCurrency(item.subtotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
