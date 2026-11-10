<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Tags, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilters } from '@/composables/useFilters';
import innovationCategories from '@/routes/innovation-categories';
import innovations from '@/routes/innovations';
import type { Paginated } from '@/types';
import { index as innovationCategoriesIndexCrumb } from '@/routes/innovation-categories';
import { index as setupCrumb } from '@/routes/inventory/locations';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([
    { title: 'Setup', href: setupCrumb() },
    { title: 'Innovation categories', href: innovationCategoriesIndexCrumb() },
]);

type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    innovations_count: number;
};

const props = defineProps<{
    categories: Paginated<Category>;
    filters: { search?: string; status?: string };
    can: { manage: boolean };
}>();

const page = usePage();
const canViewInnovations = computed(
    () => page.props.auth.access?.can?.viewInnovations ?? false,
);

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        status: props.filters.status ?? null,
    },
    () => innovationCategories.index().url,
);

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

type CategoryForm = {
    name: string;
    slug: string;
    description: string;
    is_active: boolean;
    sort_order: number;
};

const blankForm = (): CategoryForm => ({
    name: '',
    slug: '',
    description: '',
    is_active: true,
    sort_order: 0,
});

const dialogOpen = ref(false);
const editing = ref<Category | null>(null);
const form = useForm<CategoryForm>(blankForm());

const deleting = ref<Category | null>(null);
const deleteOpen = ref(false);
const deleteProcessing = ref(false);

function openCreate(): void {
    editing.value = null;
    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(category: Category): void {
    editing.value = category;
    form.defaults({
        name: category.name,
        slug: category.slug,
        description: category.description ?? '',
        is_active: category.is_active,
        sort_order: category.sort_order,
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
        form.put(innovationCategories.update(editing.value.id).url, options);
    } else {
        form.post(innovationCategories.store().url, options);
    }
}

function confirmDelete(category: Category): void {
    deleting.value = category;
    deleteOpen.value = true;
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(innovationCategories.destroy(deleting.value.id).url, {
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
    <Head title="Innovation categories" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Registry"
            title="Innovation categories"
            description="Classify innovations by business area or purpose."
        >
            <template v-if="can.manage" #actions>
                <Button @click="openCreate"><Plus /> New category</Button>
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
                        placeholder="Search name, slug or description"
                        aria-label="Search categories"
                    />
                </div>
                <SelectInput
                    v-model="filters.status"
                    class="w-40"
                    :options="statusOptions"
                    placeholder="All statuses"
                    aria-label="Filter by status"
                />
            </div>

            <div v-if="props.categories.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="w-16 px-5 py-2.5 font-medium">Order</th>
                            <th class="px-5 py-2.5 font-medium">Name</th>
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
                        <tr
                            v-for="category in props.categories.data"
                            :key="category.id"
                            class="hover:bg-accent/40"
                        >
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ category.sort_order }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium">
                                    {{ category.name }}
                                </div>
                                <div
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ category.slug }}
                                </div>
                                <p
                                    v-if="category.description"
                                    class="mt-1 line-clamp-2 max-w-md text-xs text-muted-foreground"
                                >
                                    {{ category.description }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    :variant="
                                        category.is_active
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                    >{{
                                        category.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}</Badge
                                >
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                <Link
                                    v-if="canViewInnovations"
                                    :href="
                                        innovations.index({
                                            query: { category: category.id },
                                        })
                                    "
                                    class="text-primary underline-offset-4 hover:underline"
                                    :aria-label="`View innovations in ${category.name}`"
                                    >{{ category.innovations_count }}</Link
                                >
                                <span v-else class="text-muted-foreground">{{
                                    category.innovations_count
                                }}</span>
                            </td>
                            <td v-if="can.manage" class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Edit ${category.name}`"
                                        @click="openEdit(category)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Delete ${category.name}`"
                                        @click="confirmDelete(category)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="Tags"
                title="No categories found"
                description="Try a different search, or add a new category."
            />
            <Pagination :paginator="props.categories" />
        </Panel>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? `Edit ${editing.name}` : 'New category'"
            :icon="Tags"
            size="md"
        >
            <form @submit.prevent="submit">
                <FormSection>
                    <Field
                        label="Name"
                        for="category-name"
                        :error="form.errors.name"
                        required
                    >
                        <Input
                            id="category-name"
                            v-model="form.name"
                            placeholder="e.g. Smart Farming"
                        />
                    </Field>
                    <Field
                        label="Slug"
                        for="category-slug"
                        :error="form.errors.slug"
                        hint="Leave blank to generate it from the name."
                    >
                        <Input
                            id="category-slug"
                            v-model="form.slug"
                            class="font-mono"
                            placeholder="e.g. smart-farming"
                        />
                    </Field>
                    <Field
                        label="Description"
                        for="category-description"
                        :error="form.errors.description"
                    >
                        <TextareaInput
                            id="category-description"
                            v-model="form.description"
                        />
                    </Field>
                    <Field
                        label="Display order"
                        for="category-sort-order"
                        :error="form.errors.sort_order"
                        hint="Lower numbers are listed first."
                    >
                        <Input
                            id="category-sort-order"
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="w-32"
                        />
                    </Field>
                    <Label class="flex items-center gap-2.5 font-normal">
                        <Checkbox v-model="form.is_active" />
                        Active — available when classifying innovations
                    </Label>
                </FormSection>
                <FormFooter
                    :submit-label="
                        editing ? 'Save category' : 'Create category'
                    "
                    :processing="form.processing"
                    @cancel="dialogOpen = false"
                />
            </form>
        </FormDialog>

        <ConfirmDialog
            v-model:open="deleteOpen"
            :title="`Delete ${deleting?.name ?? 'category'}?`"
            description="Categories that already classify innovations cannot be deleted; deactivate them instead."
            confirm-label="Delete category"
            :processing="deleteProcessing"
            @confirm="destroy"
        />
    </div>
</template>
