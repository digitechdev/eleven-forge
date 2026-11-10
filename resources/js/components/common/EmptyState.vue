<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{ icon?: Component; title: string; description?: string }>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center"
    >
        <div v-if="icon" class="relative mb-2">
            <div
                class="relative flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-primary/5 text-primary ring-1 ring-primary/15 motion-safe:animate-[pulse-ring_2.4s_ease-out_infinite]"
            >
                <component :is="icon" class="size-6" />
            </div>
        </div>
        <p class="font-semibold text-foreground">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-2"><slot /></div>
    </div>
</template>
