<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArchiveRestore, Lightbulb, Plus, Search, Star } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Pagination from '@/components/common/Pagination.vue';
import Panel from '@/components/common/Panel.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import { statusTone } from '@/components/innovations/types';
import type { Option } from '@/components/innovations/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFilters } from '@/composables/useFilters';
import { formatDate } from '@/lib/format';
import innovations from '@/routes/innovations';
import type { Paginated } from '@/types';
import { index as innovationsIndexCrumb } from '@/routes/innovations';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([{ title: 'Innovations', href: innovationsIndexCrumb() }]);

type InnovationRow = {
    id: number;
    innovation_code: string;
    name: string;
    tagline: string | null;
    category: string | null;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    visibility: string;
    visibility_label: string;
    is_featured: boolean;
    updated_at: string | null;
    deleted_at: string | null;
};

type Filters = {
    search?: string;
    category?: string;
    type?: string;
    status?: string;
    visibility?: string;
    trashed?: string;
};

const props = defineProps<{
    innovations: Paginated<InnovationRow>;
    filters: Filters;
    options: {
        categories: Option<number>[];
        types: Option[];
        statuses: Option[];
        visibilities: Option[];
    };
    can: { create: boolean; restore: boolean };
}>();

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        category: props.filters.category
            ? Number(props.filters.category)
            : null,
        type: props.filters.type ?? null,
        status: props.filters.status ?? null,
        visibility: props.filters.visibility ?? null,
        trashed: props.filters.trashed ?? null,
    },
    () => innovations.index().url,
);

const trashedOptions = [{ value: 'only', label: 'Archived only' }];

const restoring = ref<InnovationRow | null>(null);
const restoreOpen = ref(false);
const restoreProcessing = ref(false);

function confirmRestore(innovation: InnovationRow): void {
    restoring.value = innovation;
    restoreOpen.value = true;
}

function restore(): void {
    if (!restoring.value) {
        return;
    }

    router.patch(
        innovations.restore(restoring.value.id).url,
        {},
        {
            preserveScroll: true,
            onStart: () => (restoreProcessing.value = true),
            onFinish: () => {
                restoreProcessing.value = false;
                restoreOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Innovations" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Registry"
            title="Innovations"
            description="The central record for each software, hardware, or hybrid innovation."
        >
            <template v-if="can.create" #actions>
                <Button as-child>
                    <Link :href="innovations.create().url"
                        ><Plus /> New innovation</Link
                    >
                </Button>
            </template>
        </PageHeader>

        <Panel flush>
            <div class="flex flex-wrap items-center gap-3 border-b px-5 py-3">
                <div class="relative min-w-56 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="filters.search"
                        class="pl-9"
                        placeholder="Search name, code, or tagline"
                        aria-label="Search innovations"
                    />
                </div>
                <SelectInput
                    v-model="filters.category"
                    class="w-44"
                    :options="options.categories"
                    placeholder="All categories"
                    aria-label="Filter by category"
                />
                <SelectInput
                    v-model="filters.type"
                    class="w-36"
                    :options="options.types"
                    placeholder="All types"
                    aria-label="Filter by type"
                />
                <SelectInput
                    v-model="filters.status"
                    class="w-40"
                    :options="options.statuses"
                    placeholder="All statuses"
                    aria-label="Filter by status"
                />
                <SelectInput
                    v-model="filters.visibility"
                    class="w-40"
                    :options="options.visibilities"
                    placeholder="Any visibility"
                    aria-label="Filter by visibility"
                />
                <SelectInput
                    v-if="can.restore"
                    v-model="filters.trashed"
                    class="w-40"
                    :options="trashedOptions"
                    placeholder="Current records"
                    aria-label="Show archived innovations"
                />
            </div>

            <div v-if="props.innovations.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Code</th>
                            <th class="px-5 py-2.5 font-medium">Innovation</th>
                            <th class="px-5 py-2.5 font-medium">Category</th>
                            <th class="px-5 py-2.5 font-medium">Type</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 font-medium">Visibility</th>
                            <th class="px-5 py-2.5 font-medium">Updated</th>
                            <th
                                v-if="filters.trashed === 'only'"
                                class="px-5 py-2.5"
                            >
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="innovation in props.innovations.data"
                            :key="innovation.id"
                            class="hover:bg-accent/40"
                        >
                            <td
                                class="px-5 py-3 font-mono text-xs whitespace-nowrap text-muted-foreground"
                            >
                                {{ innovation.innovation_code }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    <Link
                                        v-if="!innovation.deleted_at"
                                        :href="
                                            innovations.show(innovation.id).url
                                        "
                                        class="font-medium hover:text-primary hover:underline"
                                        >{{ innovation.name }}</Link
                                    >
                                    <span v-else class="font-medium">{{
                                        innovation.name
                                    }}</span>
                                    <Star
                                        v-if="innovation.is_featured"
                                        class="size-3.5 fill-chart-3 text-chart-3"
                                        aria-label="Featured"
                                    />
                                </div>
                                <p
                                    v-if="innovation.tagline"
                                    class="line-clamp-1 text-xs text-muted-foreground"
                                >
                                    {{ innovation.tagline }}
                                </p>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ innovation.category ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <Badge variant="outline">{{
                                    innovation.type_label
                                }}</Badge>
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    variant="outline"
                                    :class="statusTone[innovation.status]"
                                    >{{ innovation.status_label }}</Badge
                                >
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    :variant="
                                        innovation.visibility === 'PUBLIC'
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                    >{{ innovation.visibility_label }}</Badge
                                >
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDate(innovation.updated_at) }}
                            </td>
                            <td
                                v-if="filters.trashed === 'only'"
                                class="px-5 py-3"
                            >
                                <div class="flex justify-end">
                                    <Button
                                        v-if="innovation.deleted_at"
                                        size="sm"
                                        variant="outline"
                                        @click="confirmRestore(innovation)"
                                    >
                                        <ArchiveRestore /> Restore
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="Lightbulb"
                title="No innovations found"
                :description="
                    can.create
                        ? 'Try a different filter, or record a new innovation.'
                        : 'Try a different search or filter.'
                "
            />
            <Pagination :paginator="props.innovations" />
        </Panel>

        <ConfirmDialog
            v-model:open="restoreOpen"
            :title="`Restore ${restoring?.name ?? 'innovation'}?`"
            description="The innovation returns to the registry with its bill of materials, technologies, and media."
            confirm-label="Restore"
            :destructive="false"
            :processing="restoreProcessing"
            @confirm="restore"
        />
    </div>
</template>
