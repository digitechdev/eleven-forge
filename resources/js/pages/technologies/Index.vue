<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ChevronRight,
    Cpu,
    ExternalLink,
    Pencil,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Pagination from '@/components/common/Pagination.vue';
import Panel from '@/components/common/Panel.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFilters } from '@/composables/useFilters';
import technologies from '@/routes/technologies';
import type { Paginated } from '@/types';
import { index as technologiesIndexCrumb } from '@/routes/technologies';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([{ title: 'Technologies', href: technologiesIndexCrumb() }]);

type TechnologyInnovation = {
    id: number;
    code: string;
    name: string;
    version: string | null;
    purpose: string | null;
};

type Technology = {
    id: number;
    name: string;
    category: string;
    category_label: string;
    description: string | null;
    website: string | null;
    status: string;
    innovations_count: number;
    innovations: TechnologyInnovation[];
};

type Option = { value: string; label: string };

const props = defineProps<{
    technologies: Paginated<Technology>;
    filters: { search?: string; category?: string; status?: string };
    categoryOptions: Option[];
    statusOptions: Option[];
    can: { manage: boolean };
}>();

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        category: props.filters.category ?? null,
        status: props.filters.status ?? null,
    },
    () => technologies.index().url,
);

const websitePlaceholder = 'https://example.com';

const expanded = ref<Set<number>>(new Set());

function toggleExpanded(id: number): void {
    const next = new Set(expanded.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    expanded.value = next;
}

type TechnologyForm = {
    name: string;
    category: string | null;
    description: string;
    website: string;
    status: string;
};

const blankForm = (): TechnologyForm => ({
    name: '',
    category: null,
    description: '',
    website: '',
    status: 'ACTIVE',
});

const dialogOpen = ref(false);
const editing = ref<Technology | null>(null);
const form = useForm<TechnologyForm>(blankForm());

const deleting = ref<Technology | null>(null);
const deleteOpen = ref(false);
const deleteProcessing = ref(false);

function openCreate(): void {
    editing.value = null;
    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(technology: Technology): void {
    editing.value = technology;
    form.defaults({
        name: technology.name,
        category: technology.category,
        description: technology.description ?? '',
        website: technology.website ?? '',
        status: technology.status,
    });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };

    if (editing.value) {
        form.put(technologies.update(editing.value.id).url, options);
    } else {
        form.post(technologies.store().url, options);
    }
}

function confirmDelete(technology: Technology): void {
    deleting.value = technology;
    deleteOpen.value = true;
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(technologies.destroy(deleting.value.id).url, {
        preserveScroll: true,
        onStart: () => (deleteProcessing.value = true),
        onFinish: () => {
            deleteProcessing.value = false;
            deleteOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Technologies" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Registry"
            title="Technologies"
            description="Maintain the non-physical tools and platforms used by innovations."
        >
            <template v-if="can.manage" #actions>
                <Button @click="openCreate"><Plus /> New technology</Button>
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
                        placeholder="Search name or description"
                        aria-label="Search technologies"
                    />
                </div>
                <SelectInput
                    v-model="filters.category"
                    class="w-40"
                    :options="categoryOptions"
                    placeholder="All categories"
                    aria-label="Filter by category"
                />
                <SelectInput
                    v-model="filters.status"
                    class="w-40"
                    :options="statusOptions"
                    placeholder="All statuses"
                    aria-label="Filter by status"
                />
            </div>

            <div v-if="props.technologies.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="w-10 py-2.5 pl-3">
                                <span class="sr-only">Expand</span>
                            </th>
                            <th class="px-5 py-2.5 font-medium">Name</th>
                            <th class="px-5 py-2.5 font-medium">Category</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Innovations
                            </th>
                            <th v-if="can.manage" class="px-5 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template
                            v-for="technology in props.technologies.data"
                            :key="technology.id"
                        >
                            <tr class="hover:bg-accent/40">
                                <td class="py-3 pl-3">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="size-7"
                                        :disabled="
                                            !technology.innovations_count
                                        "
                                        :aria-expanded="
                                            expanded.has(technology.id)
                                        "
                                        :aria-label="`Show innovations using ${technology.name}`"
                                        @click="toggleExpanded(technology.id)"
                                    >
                                        <ChevronRight
                                            :class="[
                                                'transition-transform',
                                                expanded.has(technology.id) &&
                                                    'rotate-90',
                                            ]"
                                        />
                                    </Button>
                                </td>
                                <td class="px-5 py-3">
                                    <div
                                        class="flex items-center gap-1.5 font-medium"
                                    >
                                        {{ technology.name }}
                                        <a
                                            v-if="technology.website"
                                            :href="technology.website"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-muted-foreground hover:text-primary"
                                            :aria-label="`${technology.name} website`"
                                        >
                                            <ExternalLink class="size-3.5" />
                                        </a>
                                    </div>
                                    <p
                                        v-if="technology.description"
                                        class="mt-1 line-clamp-2 max-w-md text-xs text-muted-foreground"
                                    >
                                        {{ technology.description }}
                                    </p>
                                </td>
                                <td class="px-5 py-3">
                                    <Badge variant="outline">{{
                                        technology.category_label
                                    }}</Badge>
                                </td>
                                <td class="px-5 py-3">
                                    <Badge
                                        :variant="
                                            technology.status === 'ACTIVE'
                                                ? 'secondary'
                                                : 'outline'
                                        "
                                        >{{
                                            technology.status === 'ACTIVE'
                                                ? 'Active'
                                                : 'Deprecated'
                                        }}</Badge
                                    >
                                </td>
                                <td
                                    class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                                >
                                    {{ technology.innovations_count }}
                                </td>
                                <td v-if="can.manage" class="px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            :aria-label="`Edit ${technology.name}`"
                                            @click="openEdit(technology)"
                                        >
                                            <Pencil />
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            :aria-label="`Delete ${technology.name}`"
                                            @click="confirmDelete(technology)"
                                        >
                                            <Trash2 />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-if="expanded.has(technology.id)"
                                class="bg-muted/20"
                            >
                                <td
                                    :colspan="can.manage ? 6 : 5"
                                    class="px-5 py-3"
                                >
                                    <p
                                        class="mb-2 label-mono text-muted-foreground"
                                    >
                                        Used by
                                    </p>
                                    <ul
                                        class="divide-y rounded-xl border border-border/70 bg-card shadow-soft"
                                    >
                                        <li
                                            v-for="innovation in technology.innovations"
                                            :key="innovation.id"
                                            class="flex flex-col gap-1 px-4 py-2.5 sm:flex-row sm:items-baseline sm:gap-4"
                                        >
                                            <span
                                                class="font-mono text-xs text-muted-foreground"
                                                >{{ innovation.code }}</span
                                            >
                                            <span class="font-medium">{{
                                                innovation.name
                                            }}</span>
                                            <Badge
                                                v-if="innovation.version"
                                                variant="outline"
                                                class="font-mono"
                                                >v{{
                                                    innovation.version
                                                }}</Badge
                                            >
                                            <span
                                                v-if="innovation.purpose"
                                                class="text-xs text-muted-foreground sm:ml-auto sm:max-w-sm sm:text-right"
                                                >{{ innovation.purpose }}</span
                                            >
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="Cpu"
                title="No technologies found"
                description="Try a different search or filter, or add a new technology."
            />
            <Pagination :paginator="props.technologies" />
        </Panel>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? `Edit ${editing.name}` : 'New technology'"
            :icon="Cpu"
            size="md"
        >
            <form @submit.prevent="submit">
                <FormSection>
                    <Field
                        label="Name"
                        for="technology-name"
                        :error="form.errors.name"
                        required
                    >
                        <Input
                            id="technology-name"
                            v-model="form.name"
                            placeholder="e.g. Laravel"
                        />
                    </Field>
                    <Field
                        label="Category"
                        for="technology-category"
                        :error="form.errors.category"
                        required
                    >
                        <SelectInput
                            id="technology-category"
                            v-model="form.category"
                            :options="categoryOptions"
                            placeholder="Select a category"
                        />
                    </Field>
                    <Field
                        label="Description"
                        for="technology-description"
                        :error="form.errors.description"
                    >
                        <TextareaInput
                            id="technology-description"
                            v-model="form.description"
                        />
                    </Field>
                    <Field
                        label="Website"
                        for="technology-website"
                        :error="form.errors.website"
                    >
                        <Input
                            id="technology-website"
                            v-model="form.website"
                            type="url"
                            :placeholder="websitePlaceholder"
                        />
                    </Field>
                    <Field
                        label="Status"
                        for="technology-status"
                        :error="form.errors.status"
                        hint="Deprecated technologies stay linked to existing innovations."
                        required
                    >
                        <SelectInput
                            id="technology-status"
                            v-model="form.status"
                            :options="statusOptions"
                        />
                    </Field>
                </FormSection>
                <FormFooter
                    :submit-label="
                        editing ? 'Save technology' : 'Create technology'
                    "
                    :processing="form.processing"
                    @cancel="dialogOpen = false"
                />
            </form>
        </FormDialog>

        <ConfirmDialog
            v-model:open="deleteOpen"
            :title="`Delete ${deleting?.name ?? 'technology'}?`"
            description="Technologies already used by innovations cannot be deleted; deprecate them instead."
            confirm-label="Delete technology"
            :processing="deleteProcessing"
            @confirm="destroy"
        />
    </div>
</template>
