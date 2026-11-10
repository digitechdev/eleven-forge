<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title?: string;
    description?: string;
    icon?: Component;
    flush?: boolean;
}>();
</script>

<template>
    <section
        data-slot="panel"
        class="overflow-hidden rounded-xl border border-border/70 bg-card shadow-soft"
    >
        <header
            v-if="title || $slots.actions"
            class="flex flex-col gap-3 border-b border-border/60 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="min-w-0">
                <h2
                    v-if="title"
                    class="flex items-center gap-2.5 font-sans text-sm font-semibold tracking-normal text-foreground"
                >
                    <span
                        v-if="icon"
                        class="flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <component :is="icon" class="size-3.5" />
                    </span>
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mt-1 text-xs text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div
                v-if="$slots.actions"
                class="flex flex-wrap items-center gap-2"
            >
                <slot name="actions" />
            </div>
        </header>
        <div :class="flush ? '' : 'p-5'">
            <slot />
        </div>
    </section>
</template>
