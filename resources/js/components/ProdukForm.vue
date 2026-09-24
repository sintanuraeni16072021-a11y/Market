<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import BarcodeScannerModal from '@/components/BarcodeScannerModal.vue';
import { Camera } from 'lucide-vue-next';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DialogClose,
    DialogFooter,
} from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';

export interface ProdukFormData {
    id_barang?: number;
    barcode: string | null;
    nama: string;
    id_kategori: number | string | null;
    id_kelompok_kategori: number | string | null;
    id_supplier: number | string | null;
    satuan: string | null;
    harga_beli: number;
    harga_jual: number;
    stok: number;
    stok_minimum?: number;
    is_active: boolean;
}

const props = defineProps<{
    initial?: Partial<ProdukFormData>;
    kategoriList: Array<{ id_kategori: number; nama: string }>;
    kelompokKategoriList: Array<{ id_kelompok: number; nama_kelompok: string }>;
    supplierList: Array<{ id_supplier: number; nama: string }>;
}>();

const emit = defineEmits<{ success: [] }>();

const form = useForm({
    barcode: props.initial?.barcode ?? '',
    nama: props.initial?.nama ?? '',
    id_kategori: (props.initial?.id_kategori ?? '') as number | string,
    id_kelompok_kategori: (props.initial?.id_kelompok_kategori ?? '') as number | string,
    id_supplier: (props.initial?.id_supplier ?? '') as number | string,
    satuan: props.initial?.satuan ?? 'pcs',
    harga_beli: props.initial?.harga_beli ?? 0,
    harga_jual: props.initial?.harga_jual ?? 0,
    stok: props.initial?.stok ?? 0,
    stok_minimum: props.initial?.stok_minimum ?? 0,
    is_active: props.initial?.is_active ?? true,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        id_supplier: data.id_supplier === '' ? null : data.id_supplier,
    }));
    const onSuccess = () => emit('success');
    if (props.initial?.id_barang) {
        form.put(`/produk/${props.initial.id_barang}`, { onSuccess });
    } else {
        form.post('/produk', { onSuccess });
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
            <Label for="pf-barcode">Barcode</Label>
            <Input
                id="pf-barcode"
                v-model="form.barcode"
                placeholder="Opsional"
                class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
            />
            <InputError :message="form.errors.barcode" />
        </div>
        <div class="grid gap-2">
            <Label for="pf-nama">Nama Produk *</Label>
            <Input
                id="pf-nama"
                v-model="form.nama"
                required
                placeholder="Contoh: Indomie Goreng"
                class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
            />
            <InputError :message="form.errors.nama" />
        </div>
        <div class="grid gap-2">
            <Label for="pf-kategori">Kategori *</Label>
            <Select v-model="form.id_kategori">
                <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                    <SelectValue placeholder="Pilih kategori" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="k in kategoriList" :key="k.id_kategori" :value="k.id_kategori">
                        {{ k.nama }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.id_kategori" />
        </div>
        <div class="grid gap-2">
            <Label for="pf-kelompok">Kelompok Kategori *</Label>
            <Select v-model="form.id_kelompok_kategori">
                <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                    <SelectValue placeholder="Pilih kelompok" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="k in kelompokKategoriList" :key="k.id_kelompok" :value="k.id_kelompok">
                        {{ k.nama_kelompok }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.id_kelompok_kategori" />
        </div>
        <div class="grid gap-2">
            <Label for="pf-supplier">Supplier</Label>
            <Select v-model="form.id_supplier">
                <SelectTrigger class="border-gray-300 bg-gray-50 text-gray-800 data-[placeholder]:text-gray-500">
                    <SelectValue placeholder="Pilih supplier (opsional)" />
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
            <Label for="pf-satuan">Satuan *</Label>
            <Input
                id="pf-satuan"
                v-model="form.satuan"
                required
                placeholder="pcs"
                class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
            />
            <InputError :message="form.errors.satuan" />
        </div>
        <div class="grid gap-2">
            <Label for="pf-harga-beli">Harga Beli *</Label>
            <Input
                id="pf-harga-beli"
                v-model="form.harga_beli"
                type="number"
                min="0"
                required
                class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
            />
            <InputError :message="form.errors.harga_beli" />
        </div>
        <div class="grid gap-2">
            <Label for="pf-harga-jual">Harga Jual *</Label>
            <Input
                id="pf-harga-jual"
                v-model="form.harga_jual"
                type="number"
                min="0"
                required
                class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
            />
            <InputError :message="form.errors.harga_jual" />
        </div>
        <div class="grid gap-2">
            <Label for="pf-stok">Stok *</Label>
            <Input
                id="pf-stok"
                v-model="form.stok"
                type="number"
                min="0"
                required
                class="border-gray-300 bg-gray-50 text-gray-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
            />
            <InputError :message="form.errors.stok" />
        </div>
        <div class="grid gap-2">
            <Label for="pf-stok-minimum">Stok Minimum</Label>
            <Input
                id="pf-stok-minimum"
                v-model="form.stok_minimum"
                type="number"
                min="0"
                placeholder="Batas peringatan stok"
                class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
            />
            <InputError :message="form.errors.stok_minimum" />
        </div>
        <div class="flex items-end pb-1">
            <Label for="pf-active" class="flex cursor-pointer items-center gap-2">
                <Checkbox id="pf-active" v-model="form.is_active" />
                <span class="text-sm text-gray-700">Produk aktif</span>
            </Label>
        </div>
        <DialogFooter class="gap-2 md:col-span-2">
            <DialogClose as-child>
                <Button type="button" variant="outline">Batal</Button>
            </DialogClose>
            <Button type="submit" :disabled="form.processing" class="bg-emerald-600 text-white hover:bg-emerald-700">
                Simpan
            </Button>
        </DialogFooter>
    </form>
</template>
