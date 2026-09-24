<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { BarChart3, ScanBarcode, Users } from 'lucide-vue-next';

const features = [
    {
        icon: ScanBarcode,
        title: 'Transaksi Kasir Cepat',
        description: 'Scan barcode, tahan antrean belanja, dan cetak struk thermal seketika.',
    },
    {
        icon: Users,
        title: 'Multi-Tenant Sekolah',
        description: 'Kelola kasir cabang sekolah secara terisolasi dan terpusat.',
    },
    {
        icon: BarChart3,
        title: 'Laporan Real-Time',
        description: 'Pantau omset harian, mutasi stok, dan laba kotor langsung dari dashboard.',
    },
];
</script>

<template>
    <div class="grid min-h-svh bg-background">
        <div class="grid lg:grid-cols-2">
            <!-- Panel branding (desktop) -->
            <div
                class="relative hidden flex-col justify-between overflow-hidden bg-slate-950 p-10 text-white lg:flex"
            >
                <div
                    class="pointer-events-none absolute -top-24 -right-24 size-96 rounded-full bg-emerald-500/20 blur-3xl"
                />
                <div
                    class="pointer-events-none absolute -bottom-32 -left-24 size-96 rounded-full bg-cyan-500/10 blur-3xl"
                />

                <div class="relative z-10 flex items-center gap-3">
                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-slate-900 ring-1 ring-emerald-500/30 shadow-lg"
                    >
                        <AppLogoIcon class="size-7" />
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-xl font-black tracking-tight text-white">KASIRA</p>
                            <span class="rounded bg-emerald-500/20 px-1 py-0.2 text-[10px] font-bold text-emerald-400">POS</span>
                        </div>
                        <p class="text-xs text-emerald-200/70">Smart Point of Sale Multi-Sekolah</p>
                    </div>
                </div>

                <div class="relative z-10 space-y-6">
                    <h2 class="max-w-md text-3xl leading-tight font-extrabold tracking-tight">
                        Kasir sekolah jadi lebih cerdas, cepat, dan transparan.
                    </h2>
                    <ul class="max-w-md space-y-5">
                        <li
                            v-for="feature in features"
                            :key="feature.title"
                            class="flex items-start gap-4"
                        >
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/10"
                            >
                                <component :is="feature.icon" class="size-5 text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ feature.title }}</p>
                                <p class="text-sm text-slate-300/80">{{ feature.description }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <p class="relative z-10 text-xs text-slate-400">
                    &copy; {{ new Date().getFullYear() }} KASIRA. Seluruh data transaksi tersimpan aman.
                </p>
            </div>

            <!-- Panel form -->
            <div class="flex items-center justify-center p-6 md:p-10">
                <div class="w-full max-w-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
