<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import type { Paginated } from '@/types';

/** Must match App\Support\PerPage::OPTIONS on the server. */
const PER_PAGE_OPTIONS = [10, 25, 50, 100, 200] as const;

const props = withDefaults(
    defineProps<{
        paginator: Paginated<unknown>;
        /** Hide the "Show N entries" choice where the page size is fixed (e.g. the public showcase). */
        perPageSelector?: boolean;
    }>(),
    { perPageSelector: true },
);

/** Laravel's links are [previous, ...pages (with "..." gaps), next]. */
const previous = computed(() => props.paginator.links[0] ?? null);
const next = computed(
    () => props.paginator.links[props.paginator.links.length - 1] ?? null,
);
const pages = computed(() => props.paginator.links.slice(1, -1));

const perPage = computed(() => Number(props.paginator.per_page));

/** Reload the same page with a new page size, keeping the filters and starting from page 1. */
function changePerPage(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;
    const query = Object.fromEntries(
        new URLSearchParams(window.location.search),
    );

    delete query.page;
    query.per_page = value;

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <div
        v-if="paginator.total > 0"
        class="flex flex-col gap-3 border-t border-border/60 px-5 py-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <label v-if="perPageSelector" class="flex items-center gap-2">
                <span>Show</span>
                <select
                    :value="perPage"
                    class="h-8 cursor-pointer rounded-lg border border-input bg-background px-2 text-sm text-foreground tabular-nums shadow-xs transition-[box-shadow,border-color] duration-200 outline-none hover:border-foreground/20 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    aria-label="Entries per page"
                    @change="changePerPage"
                >
                    <option
                        v-for="option in PER_PAGE_OPTIONS"
                        :key="option"
                        :value="option"
                    >
                        {{ option }}
                    </option>
                </select>
                <span>entries</span>
            </label>
            <span
                v-if="perPageSelector"
                class="hidden h-4 w-px bg-border sm:block"
                aria-hidden="true"
            />
            <span>
                Showing
                <strong class="font-semibold text-foreground tabular-nums"
                    >{{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }}</strong
                >
                of
                <strong class="font-semibold text-foreground tabular-nums">{{
                    paginator.total
                }}</strong>
                {{ paginator.total === 1 ? 'entry' : 'entries' }}
            </span>
        </div>

        <nav
            v-if="paginator.last_page > 1"
            class="flex items-center gap-1"
            aria-label="Pagination"
        >
            <component
                :is="previous?.url ? Link : 'span'"
                :href="previous?.url ?? undefined"
                preserve-scroll
                :aria-disabled="!previous?.url"
                aria-label="Previous page"
                :class="[
                    'flex size-8 items-center justify-center rounded-lg border transition-colors',
                    previous?.url
                        ? 'bg-background text-foreground hover:border-primary/30 hover:bg-accent'
                        : 'pointer-events-none opacity-40',
                ]"
            >
                <ChevronLeft class="size-4" />
            </component>

            <template v-for="(link, index) in pages" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    :aria-current="link.active ? 'page' : undefined"
                    :class="[
                        'flex h-8 min-w-8 items-center justify-center rounded-lg px-2 font-medium tabular-nums transition-colors',
                        link.active
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-foreground hover:bg-accent',
                    ]"
                    >{{ link.label }}</Link
                >
                <span
                    v-else
                    class="flex h-8 min-w-6 items-center justify-center"
                    aria-hidden="true"
                    >…</span
                >
            </template>

            <component
                :is="next?.url ? Link : 'span'"
                :href="next?.url ?? undefined"
                preserve-scroll
                :aria-disabled="!next?.url"
                aria-label="Next page"
                :class="[
                    'flex size-8 items-center justify-center rounded-lg border transition-colors',
                    next?.url
                        ? 'bg-background text-foreground hover:border-primary/30 hover:bg-accent'
                        : 'pointer-events-none opacity-40',
                ]"
            >
                <ChevronRight class="size-4" />
            </component>
        </nav>
    </div>
</template>
