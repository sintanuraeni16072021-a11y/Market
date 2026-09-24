<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <main
    data-slot="sidebar-inset"
    :class="cn(
      'bg-background relative flex w-full flex-1 flex-col',
      'md:peer-data-[variant=inset]:m-3 md:peer-data-[variant=inset]:ml-3 md:peer-data-[variant=inset]:rounded-2xl md:peer-data-[variant=inset]:shadow-sm md:peer-data-[variant=inset]:border md:peer-data-[variant=inset]:border-sidebar-border/60 overflow-hidden',
      props.class,
    )"
  >
    <slot />
  </main>
</template>
