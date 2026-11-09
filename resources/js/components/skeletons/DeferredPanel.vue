<script setup lang="ts">
import { Deferred } from '@inertiajs/vue3';
import SkeletonPanel from './SkeletonPanel.vue';

/**
 * Shows a skeleton until the named deferred props have loaded, then the slot (usually a Panel).
 * Layout classes (e.g. grid spans) are applied to the skeleton; give the real panel the same classes.
 */
defineOptions({ inheritAttrs: false });

withDefaults(
    defineProps<{
        data: string | string[];
        variant?: 'chart' | 'list' | 'table' | 'bars';
        rows?: number;
    }>(),
    {
        variant: 'list',
        rows: 5,
    },
);
</script>

<template>
    <Deferred :data="data">
        <template #fallback>
            <SkeletonPanel :variant="variant" :rows="rows" v-bind="$attrs" />
        </template>
        <slot />
    </Deferred>
</template>
