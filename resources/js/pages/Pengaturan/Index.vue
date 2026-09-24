<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { DatabaseBackup } from 'lucide-vue-next';

interface Sekolah {
    id_sekolah: number;
    kode_sekolah: string;
    nama_sekolah: string;
    alamat_sekolah: string | null;
    website: string | null;
    is_active: boolean | number;
}

const props = defineProps<{ sekolah: Sekolah | null }>();

const form = useForm({
    nama_sekolah: props.sekolah?.nama_sekolah ?? '',
    alamat_sekolah: props.sekolah?.alamat_sekolah ?? '',
    website: props.sekolah?.website ?? '',
});

const submit = () => {
    form.put('/pengaturan');
};
</script>

<template>
    <Head title="Pengaturan Profil - KASIRA" />

    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Pengaturan Profil Sekolah</h1>
            <p class="text-sm text-muted-foreground">Kelola informasi sekolah dan identitas toko kasir</p>
        </div>

        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Informasi Sekolah Aktif</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="!sekolah" class="py-6 text-center text-sm text-gray-500">
                    Data sekolah tidak ditemukan.
                </div>
                <form v-else @submit.prevent="submit" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label>Kode Sekolah</Label>
                        <Input :model-value="sekolah.kode_sekolah" disabled class="bg-gray-100 text-gray-500" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="nama_sekolah">Nama Sekolah *</Label>
                        <Input
                            id="nama_sekolah"
                            v-model="form.nama_sekolah"
                            required
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.nama_sekolah" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="alamat_sekolah">Alamat</Label>
                        <Input
                            id="alamat_sekolah"
                            v-model="form.alamat_sekolah"
                            placeholder="Alamat sekolah"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.alamat_sekolah" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="website">Website</Label>
                        <Input
                            id="website"
                            v-model="form.website"
                            placeholder="sekolah.sch.id"
                            class="border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        />
                        <InputError :message="form.errors.website" />
                    </div>
                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-emerald-600 text-white hover:bg-emerald-700"
                        >
                            Simpan Pengaturan
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card class="border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <CardHeader>
                <CardTitle class="text-lg font-semibold">Backup Database</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <p class="text-sm text-muted-foreground">
                    Unduh salinan database (.sql) untuk arsip atau pemulihan darurat.
                </p>
                <Button variant="outline" as-child>
                    <a href="/backup">
                        <DatabaseBackup class="mr-2 size-4" />
                        Unduh Backup
                    </a>
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
