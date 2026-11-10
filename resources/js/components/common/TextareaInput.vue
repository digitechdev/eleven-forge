<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
const model = defineModel<string | null | undefined>();
</script>

<template>
    <textarea
        v-model="model"
        :class="
            cn(
                'min-h-20 w-full rounded-lg border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[box-shadow,border-color] duration-200 outline-none placeholder:text-muted-foreground hover:border-foreground/20 dark:bg-input/30',
                'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50',
                props.class,
            )
        "
    />
</template>
