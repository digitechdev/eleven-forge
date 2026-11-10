<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Lightbulb, Search } from '@lucide/vue';
import EmptyState from '@/components/common/EmptyState.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Pagination from '@/components/common/Pagination.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import ShowcaseCard from '@/components/showcase/ShowcaseCard.vue';
import type { ShowcaseInnovation } from '@/components/showcase/ShowcaseCard.vue';
import { Input } from '@/components/ui/input';
import { useFilters } from '@/composables/useFilters';
import showcase from '@/routes/showcase';
import type { Paginated } from '@/types';

const props = defineProps<{
    innovations: Paginated<ShowcaseInnovation>;
    categories: { value: string; label: string }[];
    filters: { search?: string; category?: string };
}>();

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        category: props.filters.category ?? null,
    },
    () => showcase.index().url,
);
</script>

<template>
    <Head title="Showcase" />

    <div class="space-y-8">
        <PageHeader
            eyebrow="Public showcase"
            title="What we have built"
            description="A selection of the software, hardware, and hybrid innovations developed by our team."
        />

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-56 flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    class="pl-9"
                    placeholder="Search innovations"
                    aria-label="Search innovations"
                />
            </div>
            <SelectInput
                v-if="categories.length"
                v-model="filters.category"
                class="w-52"
                :options="categories"
                placeholder="All categories"
                aria-label="Filter by category"
            />
        </div>

        <div
            v-if="props.innovations.data.length"
            class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
        >
            <ShowcaseCard
                v-for="innovation in props.innovations.data"
                :key="innovation.slug"
                :innovation="innovation"
            />
        </div>
        <EmptyState
            v-else
            :icon="Lightbulb"
            title="No innovations to show yet"
            description="Try a different search or category."
            class="rounded-sm border"
        />

        <div
            v-if="props.innovations.last_page > 1"
            class="overflow-hidden rounded-sm border"
        >
            <Pagination :paginator="props.innovations" />
        </div>
    </div>
</template>
