<script setup lang="ts">
import { computed } from 'vue';
import { formatKg } from '@/lib/format';

/** A ranked list of labelled totals with proportional bars. */
const props = defineProps<{ items: { name: string; total_kg: number }[] }>();

const max = computed(() => Math.max(1, ...props.items.map((i) => i.total_kg)));
</script>

<template>
    <ul class="space-y-3">
        <li v-for="item in items" :key="item.name" class="space-y-1">
            <div class="flex justify-between gap-3 text-sm">
                <span class="truncate font-medium">{{ item.name }}</span>
                <span class="shrink-0 text-muted-foreground tabular-nums">{{
                    formatKg(item.total_kg, 0)
                }}</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary/80"
                    :style="{ width: `${(item.total_kg / max) * 100}%` }"
                />
            </div>
        </li>
    </ul>
</template>
