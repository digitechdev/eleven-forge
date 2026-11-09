<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Paginated } from '@/types';

defineProps<{ paginator: Paginated<unknown> }>();

function label(raw: string): string {
    return raw.replace('&laquo; Previous', '‹').replace('Next &raquo;', '›');
}
</script>

<template>
    <div
        v-if="paginator.last_page > 1 || paginator.total > 0"
        class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-xs text-muted-foreground sm:flex-row"
    >
        <span>
            Showing
            <strong class="text-foreground"
                >{{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }}</strong
            >
            of <strong class="text-foreground">{{ paginator.total }}</strong>
        </span>
        <nav
            v-if="paginator.last_page > 1"
            class="flex flex-wrap items-center gap-1"
            aria-label="Pagination"
        >
            <template v-for="(link, i) in paginator.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    :class="[
                        'min-w-8 rounded-md border px-2.5 py-1 text-center font-medium transition-colors',
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'bg-background text-foreground hover:bg-accent',
                    ]"
                    >{{ label(link.label) }}</Link
                >
                <span
                    v-else
                    class="min-w-8 px-2.5 py-1 text-center opacity-50"
                    >{{ label(link.label) }}</span
                >
            </template>
        </nav>
    </div>
</template>
