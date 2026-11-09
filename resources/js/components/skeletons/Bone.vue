<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/** A single skeleton block with a light shimmer sweep. Size it with classes. */
const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <div
        data-slot="skeleton"
        aria-hidden="true"
        :class="
            cn(
                'shimmer relative overflow-hidden rounded-md bg-muted',
                props.class,
            )
        "
    />
</template>
