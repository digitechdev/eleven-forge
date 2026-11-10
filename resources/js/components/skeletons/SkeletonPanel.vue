<script setup lang="ts">
import Bone from './Bone.vue';

/**
 * Placeholder for a Panel while its data loads.
 * `chart` draws bars, `list` draws rows with an avatar, `table` draws a grid.
 */
withDefaults(
    defineProps<{
        variant?: 'chart' | 'list' | 'table' | 'bars';
        rows?: number;
        title?: boolean;
    }>(),
    {
        variant: 'list',
        rows: 5,
        title: true,
    },
);

const bars = [35, 55, 40, 70, 50, 85, 60, 45, 75, 90, 65, 80];
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-border/70 bg-card shadow-soft"
        aria-busy="true"
        aria-label="Loading"
    >
        <header
            v-if="title"
            class="flex items-center gap-2 border-b bg-muted/30 px-5 py-4"
        >
            <Bone class="size-7 rounded-sm" />
            <Bone class="h-4 w-40" />
        </header>

        <div
            v-if="variant === 'chart'"
            class="flex h-[240px] items-end gap-3 p-5"
        >
            <Bone
                v-for="(h, i) in bars"
                :key="i"
                class="flex-1 rounded-t-md rounded-b-none"
                :style="{ height: `${h}%` }"
            />
        </div>

        <ul v-else-if="variant === 'list'" class="divide-y">
            <li
                v-for="i in rows"
                :key="i"
                class="flex items-center gap-3 px-5 py-3.5"
            >
                <Bone class="size-7 shrink-0 rounded-full" />
                <div class="flex-1 space-y-2">
                    <Bone
                        class="h-3"
                        :style="{ width: `${70 - ((i * 13) % 30)}%` }"
                    />
                    <Bone class="h-2.5 w-1/3" />
                </div>
                <Bone class="h-4 w-16" />
            </li>
        </ul>

        <div v-else-if="variant === 'bars'" class="space-y-4 p-5">
            <div v-for="i in rows" :key="i" class="space-y-2">
                <div class="flex justify-between">
                    <Bone class="h-3 w-28" />
                    <Bone class="h-3 w-14" />
                </div>
                <Bone
                    class="h-1.5 rounded-full"
                    :style="{ width: `${100 - i * 15}%` }"
                />
            </div>
        </div>

        <div v-else>
            <div class="flex gap-4 border-b bg-muted/30 px-5 py-3">
                <Bone v-for="i in 5" :key="i" class="h-2.5 flex-1" />
            </div>
            <div
                v-for="i in rows"
                :key="i"
                class="flex items-center gap-4 border-b px-5 py-3.5 last:border-0"
            >
                <div class="flex-1 space-y-1.5">
                    <Bone class="h-3 w-3/4" />
                    <Bone class="h-2.5 w-1/2" />
                </div>
                <Bone v-for="j in 3" :key="j" class="h-3 flex-1" />
                <Bone class="h-5 w-20 rounded-full" />
            </div>
        </div>
    </section>
</template>
