<script setup lang="ts">
defineProps<{
    title: string;
    description?: string | null;
    /** Short section code shown above the title, e.g. "Registry". */
    eyebrow?: string | null;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between"
    >
        <div class="min-w-0 space-y-2">
            <p
                v-if="eyebrow"
                class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/8 px-2.5 py-0.5 text-xs font-medium text-primary"
            >
                <span
                    class="size-1.5 rounded-full bg-primary motion-safe:animate-pulse"
                    aria-hidden="true"
                />
                {{ eyebrow }}
            </p>
            <h1
                class="text-3xl font-bold tracking-tight text-foreground sm:text-[2.125rem] sm:leading-tight"
            >
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="max-w-2xl text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
            <slot name="meta" />
        </div>
        <div
            v-if="$slots.actions"
            class="flex shrink-0 flex-wrap items-center gap-2"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
