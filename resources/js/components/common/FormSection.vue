<script setup lang="ts">
/** A titled group of fields inside a form; sections after the first get a divider. */
defineProps<{ title?: string; description?: string; columns?: 1 | 2 | 3 }>();
</script>

<template>
    <fieldset class="space-y-4 border-t pt-5 first:border-t-0 first:pt-0">
        <legend v-if="title" class="sr-only">{{ title }}</legend>
        <div v-if="title" class="-mt-1">
            <h3 class="text-sm font-bold">{{ title }}</h3>
            <p v-if="description" class="text-xs text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div
            :class="[
                'grid gap-4',
                columns === 3
                    ? 'sm:grid-cols-3'
                    : columns === 1
                      ? ''
                      : 'sm:grid-cols-2',
            ]"
        >
            <slot />
        </div>
    </fieldset>
</template>
