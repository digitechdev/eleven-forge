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
        class="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between"
    >
        <div class="min-w-0 space-y-2">
            <p
                v-if="eyebrow"
                class="flex items-center gap-2 label-mono text-primary"
            >
                <span
                    class="size-1.5 rotate-45 bg-primary"
                    aria-hidden="true"
                />
                {{ eyebrow }}
            </p>
            <h1
                class="text-3xl font-bold text-foreground sm:text-[2rem] sm:leading-tight"
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
