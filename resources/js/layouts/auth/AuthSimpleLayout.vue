<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <div class="flex flex-col items-center gap-2 font-medium">
                        <div
                            class="mb-1 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100"
                        >
                            <AppLogoIcon
                                class="size-10 fill-current text-emerald-600"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </div>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-muted-foreground text-center text-sm">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>