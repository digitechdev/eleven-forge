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
    <div
        class="group relative flex hover-lift flex-col overflow-hidden rounded-xl border border-border/70 bg-card p-5 shadow-soft"
    >
        <span
            :class="[
                'pointer-events-none absolute -top-10 -right-10 size-32 rounded-full opacity-[0.12] blur-2xl transition-opacity duration-500 group-hover:opacity-25',
                toneBar[tone],
            ]"
            aria-hidden="true"
        />
        <div class="mb-3 flex items-start justify-between gap-3">
            <span class="text-sm font-medium text-muted-foreground">{{
                label
            }}</span>
            <span
                v-if="icon"
                :class="[
                    'flex size-9 shrink-0 items-center justify-center rounded-lg transition-transform duration-300 ease-[var(--ease-spring)] group-hover:scale-110 group-hover:-rotate-6',
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
