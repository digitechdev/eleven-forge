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
        class="overflow-hidden rounded-sm border bg-card shadow-[0_1px_0_0_var(--border)]"
    >
        <header
            v-if="title || $slots.actions"
            class="flex flex-col gap-3 border-b px-5 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="min-w-0">
                <h2
                    v-if="title"
                    class="flex items-center gap-2 label-mono font-sans font-medium text-foreground"
                >
                    <component
                        :is="icon"
                        v-if="icon"
                        class="size-3.5 text-primary"
                    />
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
