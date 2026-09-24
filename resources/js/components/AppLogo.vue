<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div class="flex size-9 items-center justify-center rounded-xl bg-purple-950/80 shadow-md ring-1 ring-purple-400/30">
            <AppLogoIcon class="size-6" />
        </div>
        <div class="grid flex-1 text-left">
            <div class="flex items-center gap-1.5">
                <span class="font-black tracking-tight text-white text-base leading-none">
                    KASIRA
                </span>
                <span class="rounded bg-purple-500/30 px-1 py-0.2 text-[10px] font-bold tracking-wide text-purple-200 ring-1 ring-purple-400/20">
                    POS
                </span>
            </div>
            <span class="text-[10px] text-purple-200/70 font-medium leading-tight mt-0.5">
                Sistem Kasir Sekolah
            </span>
        </div>
    </div>
</template>
