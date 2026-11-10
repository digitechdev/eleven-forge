<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';

/** Labelled counts with bars proportional to the largest count. */
const props = defineProps<{
    items: { value: string; label: string; count: number }[];
}>();

const max = computed(() => Math.max(1, ...props.items.map((i) => i.count)));
</script>

<template>
    <ul class="space-y-3">
        <li v-for="item in items" :key="item.value" class="space-y-1">
            <div class="flex justify-between gap-3 text-sm">
                <span class="truncate font-medium">{{ item.label }}</span>
                <span class="shrink-0 text-muted-foreground tabular-nums">{{
                    formatNumber(item.count, 0)
                }}</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary/80 transition-[width] duration-700"
                    :style="{ width: `${(item.count / max) * 100}%` }"
                />
            </div>
        </li>
    </ul>
</template>
