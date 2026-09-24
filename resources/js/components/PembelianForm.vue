<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
import { DialogClose, DialogFooter } from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';
import { Search, Trash2 } from 'lucide-vue-next';

export interface PembelianItem {
    id_barang: number;
    satuan: string;
    jumlah: number;
    harga_beli: number;
}

export interface PembelianFormInitial {
    id_pembelian?: number;
    id_supplier: number | string | null;
    nomor_faktur: string;
    tanggal_faktur: string;
    jenis_transaksi: string;
    cara_bayar: string | null;
    note: string | null;
    items: PembelianItem[];
}

interface Barang {
    id_barang: number;
    barcode: string | null;
    nama: string;
    harga_beli: number;
    satuan: string | null;
    stok?: number;
}

const props = defineProps<{
    initial?: PembelianFormInitial;
    supplierList: Array<{ id_supplier: number; nama: string }>;
    barangList: Barang[];
    nomorFaktur?: string;
    tanggalFaktur?: string;
}>();

const emit = defineEmits<{ success: [] }>();

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    id_supplier: (props.initial?.id_supplier ?? '') as number | string,
    nomor_faktur: props.initial?.nomor_faktur ?? props.nomorFaktur ?? '',
    tanggal_faktur: props.initial?.tanggal_faktur ?? props.tanggalFaktur ?? today,
    jenis_transaksi: props.initial?.jenis_transaksi ?? 'tunai',
    cara_bayar: props.initial?.cara_bayar ?? '',
    note: props.initial?.note ?? '',
    items: (props.initial?.items ?? []).map((i) => ({ ...i })) as PembelianItem[],
});

const searchBarang = ref('');

const filteredBarang = computed(() => {
    const q = searchBarang.value.trim().toLowerCase();
    if (!q) return props.barangList.slice(0, 20);
    return props.barangList
        .filter(
            (b) =>
                b.nama.toLowerCase().includes(q) ||
                (b.barcode ?? '').toLowerCase().includes(q),
        )
        .slice(0, 20);
});

const barangName = (id: number) =>
    props.barangList.find((b) => b.id_barang === id)?.nama ?? `Barang #${id}`;

const addItem = (barang: Barang) => {
    const existing = form.items.find((i) => i.id_barang === barang.id_barang);
    if (existing) {
        existing.jumlah += 1;
    } else {
        form.items.push({
            id_barang: barang.id_barang,
            satuan: barang.satuan ?? 'pcs',
            jumlah: 1,
            harga_beli: Number(barang.harga_beli),
        });
    }
    searchBarang.value = '';
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const totalBayar = computed(() =>
    form.items.reduce((sum, i) => sum + Number(i.harga_beli) * Number(i.jumlah), 0),
);

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value) || 0);

const submit = () => {
    const onSuccess = () => emit('success');
    if (props.initial?.id_pembelian) {
        form.put(`/pembelian/${props.initial.id_pembelian}`, { onSuccess });
    } else {
        form.post('/pembelian', { onSuccess });
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="grid gap-6 md:grid-cols-5">
        <div class="space-y-4 md:col-span-3">
            <div class="grid gap-2">
                <Label>Cari Barang</Label>
                <div class="relative">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        v-model="searchBarang"
                        type="text"
                        placeholder="Cari barcode atau nama barang..."
                        aria-label="Cari barang"
                        class="w-full border-gray-300 bg-gray-50 pr-3 pl-10 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                    />
                </div>
                <div class="max-h-44 space-y-1 overflow-y-auto">
                    <button
                        v-for="b in filteredBarang"
                        :key="b.id_barang"
                        type="button"
                        class="w-full rounded-lg border border-gray-200 p-2 text-left transition-colors hover:border-emerald-300 hover:bg-gray-50"
                        @click="addItem(b)"
                    >
                        <span class="block text-sm font-medium text-gray-900">{{ b.nama }}</span>
                        <span class="block text-xs text-gray-500">
                            {{ b.barcode || '-' }} · {{ formatCurrency(Number(b.harga_beli)) }}
                        </span>
                    </button>
                    <p v-if="filteredBarang.length === 0" class="py-3 text-center text-sm text-gray-500">
                        Barang tidak ditemukan.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Barang</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Jml</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Harga</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Subtotal</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(item, index) in form.items" :key="item.id_barang">
                            <td class="px-3 py-2">
                                <div class="font-medium text-gray-900">{{ barangName(item.id_barang) }}</div>
                                <Input v-model="item.satuan" class="mt-1 h-7 w-20 border-gray-300 bg-gray-50 text-xs" />
                            </td>
                            <td class="px-3 py-2">
                                <Input
                                    v-model="item.jumlah"
                                    type="number"
                                    min="1"
                                    class="h-8 w-18 border-gray-300 bg-gray-50 text-center"
                                />
                            </td>
                            <td class="px-3 py-2">
                                <Input
                                    v-model="item.harga_beli"
                                    type="number"
                                    min="0"
                                    class="h-8 w-28 border-gray-300 bg-gray-50 text-right"
                                />
                            </td>
                            <td class="px-3 py-2 text-right font-medium whitespace-nowrap text-gray-900">
                                {{ formatCurrency(Number(item.harga_beli) * Number(item.jumlah)) }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    class="text-red-600 hover:text-red-700"
                                    @click="removeItem(index)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="form.items.length === 0">
                            <td colspan="5" class="px-3 py-6 text-center text-gray-500">Belum ada item.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <InputError :message="form.errors.items" />
        </div>

        <div class="space-y-4 md:col-span-2">
            <div class="grid gap-2">
                <Label for="pbf-supplier">Supplier *</Label>
                <Select v-model="form.id_supplier">
                    <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                        <SelectValue placeholder="Pilih supplier" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in supplierList" :key="s.id_supplier" :value="s.id_supplier">
                            {{ s.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.id_supplier" />
            </div>
            <div class="grid gap-2">
                <Label for="pbf-faktur">Nomor Faktur *</Label>
                <Input
                    id="pbf-faktur"
                    v-model="form.nomor_faktur"
                    required
                    class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                />
                <InputError :message="form.errors.nomor_faktur" />
            </div>
            <div class="grid gap-2">
                <Label for="pbf-tanggal">Tanggal Faktur *</Label>
                <Input
                    id="pbf-tanggal"
                    v-model="form.tanggal_faktur"
                    type="date"
                    required
                    class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                />
                <InputError :message="form.errors.tanggal_faktur" />
            </div>
            <div class="grid gap-2">
                <Label for="pbf-jenis">Jenis Transaksi</Label>
                <Select v-model="form.jenis_transaksi">
                    <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="tunai">Tunai</SelectItem>
                        <SelectItem value="kredit">Kredit</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="grid gap-2">
                <Label for="pbf-cara">Cara Bayar</Label>
                <Input
                    id="pbf-cara"
                    v-model="form.cara_bayar"
                    placeholder="Contoh: Transfer BCA"
                    class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                />
            </div>
            <div class="grid gap-2">
                <Label for="pbf-note">Catatan</Label>
                <Input
                    id="pbf-note"
                    v-model="form.note"
                    placeholder="Opsional"
                    class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                />
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-3 text-lg">
                <span class="font-semibold text-gray-900">Total</span>
                <span class="font-bold text-emerald-600">{{ formatCurrency(totalBayar) }}</span>
            </div>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button type="button" variant="outline">Batal</Button>
                </DialogClose>
                <Button
                    type="submit"
                    :disabled="form.processing || form.items.length === 0"
                    class="bg-emerald-600 text-white hover:bg-emerald-700"
                >
                    Simpan
                </Button>
            </DialogFooter>
        </div>
    </form>
</template>
