<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <defs>
            <linearGradient id="kasiraGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#10B981" />
                <stop offset="100%" stop-color="#06B6D4" />
            </linearGradient>
            <linearGradient id="kasiraGrad2" x1="0%" y1="100%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#059669" />
                <stop offset="100%" stop-color="#3B82F6" />
            </linearGradient>
        </defs>

        <!-- Main Stylized 'V' Left Stem -->
        <path
            d="M10 8L20 34C21.2 37.2 25.8 37.2 27 34L33 18"
            stroke="url(#kasiraGrad1)"
            stroke-width="5.5"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <!-- Dynamic Swoosh / Checkout Spark -->
        <path
            d="M13 28C10 32 12 38 18 38C26 38 34 26 40 14"
            stroke="url(#kasiraGrad2)"
            stroke-width="4.5"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <!-- Top Right Glow Star / Sparkle -->
        <path
            d="M40 8L41.5 12.5L46 14L41.5 15.5L40 20L38.5 15.5L34 14L38.5 12.5L40 8Z"
            fill="#38BDF8"
        />
    </svg>
</template>
