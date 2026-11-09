<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { ArrowUpRight, ListChecks, Network } from '@lucide/vue';
import Panel from '@/components/common/Panel.vue';

export type RoadmapArea = {
    label: string;
    description: string;
    /** Left out when the current user cannot open the area. */
    href?: NonNullable<InertiaLinkProps['href']> | null;
};

defineProps<{
    tasks: string[];
    related: RoadmapArea[];
}>();
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <Panel title="Next tasks" :icon="ListChecks" flush>
            <ol class="divide-y">
                <li
                    v-for="(task, index) in tasks"
                    :key="task"
                    class="flex gap-4 px-5 py-3 text-sm"
                >
                    <span
                        class="font-mono text-xs leading-5 text-primary tabular-nums"
                        >{{ String(index + 1).padStart(2, '0') }}</span
                    >
                    <span class="text-foreground/80">{{ task }}</span>
                </li>
            </ol>
        </Panel>

        <Panel title="Connected areas" :icon="Network" flush>
            <ul class="divide-y">
                <li v-for="area in related" :key="area.label">
                    <component
                        :is="area.href ? Link : 'div'"
                        :href="area.href ?? undefined"
                        :class="[
                            'group flex items-start justify-between gap-4 px-5 py-3',
                            area.href
                                ? 'transition-colors hover:bg-accent'
                                : '',
                        ]"
                    >
                        <span class="min-w-0">
                            <span
                                :class="[
                                    'block text-sm font-semibold',
                                    area.href
                                        ? 'text-foreground group-hover:text-primary'
                                        : 'text-muted-foreground',
                                ]"
                                >{{ area.label }}</span
                            >
                            <span class="block text-sm text-muted-foreground">{{
                                area.description
                            }}</span>
                        </span>
                        <ArrowUpRight
                            v-if="area.href"
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-primary"
                        />
                    </component>
                </li>
            </ul>
        </Panel>
    </div>
</template>
