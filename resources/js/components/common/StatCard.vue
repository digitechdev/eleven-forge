<script setup lang="ts">
import type { Component } from 'vue';
import { useCountUp } from '@/composables/useCountUp';
import { toneBar, toneIcon } from './tones';
import type { Tone } from './tones';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        unit?: string;
        icon?: Component;
        tone?: Tone;
    }>(),
    { tone: 'ember' },
);

const display = useCountUp(() => props.value);
</script>

<template>
    <div class="relative flex flex-col rounded-sm border bg-card p-5">
        <span
            :class="['absolute inset-y-0 left-0 w-0.5', toneBar[tone]]"
            aria-hidden="true"
        />
        <div class="mb-3 flex items-start justify-between gap-3">
            <span class="label-mono text-muted-foreground">{{ label }}</span>
            <span
                v-if="icon"
                :class="[
                    'flex size-8 shrink-0 items-center justify-center rounded-sm',
                    toneIcon[tone],
                ]"
            >
                <component :is="icon" class="size-4" />
            </span>
        </div>
        <div class="flex items-baseline gap-1.5">
            <span
                class="font-display text-3xl font-bold tracking-tight text-foreground tabular-nums"
                >{{ display }}</span
            >
            <span v-if="unit" class="font-mono text-xs text-muted-foreground">{{
                unit
            }}</span>
        </div>
        <div v-if="$slots.default" class="mt-3 text-xs text-muted-foreground">
            <slot />
        </div>
    </div>
</template>
