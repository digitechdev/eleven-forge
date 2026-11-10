<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

type Opt = { value: string | number; label: string };

const props = defineProps<{
    options?: Opt[];
    placeholder?: string;
    class?: HTMLAttributes['class'];
}>();

const model = defineModel<string | number | null | undefined>();
</script>

<template>
    <select
        v-model="model"
        :class="
            cn(
                'h-9 w-full min-w-0 cursor-pointer rounded-lg border border-input bg-background px-3 py-1 text-sm shadow-xs transition-[box-shadow,border-color] duration-200 outline-none hover:border-foreground/20 dark:bg-input/30',
                'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50',
                'aria-invalid:border-destructive',
                props.class,
            )
        "
    >
        <option v-if="placeholder !== undefined" :value="null">
            {{ placeholder }}
        </option>
        <option
            v-for="opt in options ?? []"
            :key="opt.value"
            :value="opt.value"
        >
            {{ opt.label }}
        </option>
        <slot />
    </select>
</template>
