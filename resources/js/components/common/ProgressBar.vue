<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { toneBar } from './tones';
import type { Tone } from './tones';

const props = withDefaults(
    defineProps<{ value: number | null; tone?: Tone; size?: 'sm' | 'md' }>(),
    {
        tone: 'ember',
        size: 'md',
    },
);

// Start empty and fill on mount so bars grow into place.
const mounted = ref(false);
onMounted(() => requestAnimationFrame(() => (mounted.value = true)));

const pct = computed(() => Math.max(0, Math.min(100, props.value ?? 0)));
</script>

<template>
    <div
        :class="[
            'w-full overflow-hidden rounded-full bg-muted',
            size === 'sm' ? 'h-1.5' : 'h-2',
        ]"
        role="progressbar"
        :aria-valuenow="pct"
        aria-valuemin="0"
        aria-valuemax="100"
    >
        <div
            :class="[
                'relative h-full overflow-hidden rounded-full transition-[width] duration-1000 ease-[cubic-bezier(0.22,1,0.36,1)]',
                toneBar[tone],
                pct > 0 && pct < 100 ? 'shimmer' : '',
            ]"
            :style="{ width: `${mounted ? pct : 0}%` }"
        />
    </div>
</template>
