<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    CornerDownRight,
    FolderTree,
    Pencil,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Panel from '@/components/common/Panel.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilters } from '@/composables/useFilters';
import inventory from '@/routes/inventory';
import itemCategories from '@/routes/inventory/categories';
import { index as itemCategoriesIndexCrumb } from '@/routes/inventory/categories';
import { index as setupCrumb } from '@/routes/inventory/locations';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([
    { title: 'Setup', href: setupCrumb() },
    { title: 'Item categories', href: itemCategoriesIndexCrumb() },
]);

type Category = {
    id: number;
    parent_id: number | null;
    name: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    depth: number;
    path: string;
    items_count: number;
    children_count: number;
};

type ParentOption = {
    id: number;
    parent_id: number | null;
    name: string;
    depth: number;
};

const props = defineProps<{
    categories: Category[];
    parentOptions: ParentOption[];
    filters: { search?: string; status?: string };
    can: { manage: boolean };
}>();

const page = usePage();
const canViewItems = computed(
    () => page.props.auth.access?.can?.viewInventoryItems ?? false,
);

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        status: props.filters.status ?? null,
    },
    () => itemCategories.index().url,
);

const isFiltered = computed(() => Boolean(filters.search || filters.status));

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

type CategoryForm = {
    parent_id: number | null;
    name: string;
    description: string;
    is_active: boolean;
    sort_order: number;
};

const blankForm = (parentId: number | null = null): CategoryForm => ({
    parent_id: parentId,
    name: '',
    description: '',
    is_active: true,
    sort_order: 0,
});

const dialogOpen = ref(false);
const editing = ref<Category | null>(null);
const form = useForm<CategoryForm>(blankForm());

/** IDs that cannot become the parent of the category being edited: itself and its descendants. */
const excludedParentIds = computed<Set<number>>(() => {
    const excluded = new Set<number>();

    if (!editing.value) {
        return excluded;
    }

    excluded.add(editing.value.id);
    let added = true;

    while (added) {
        added = false;

        for (const option of props.parentOptions) {
            if (
                option.parent_id !== null &&
                excluded.has(option.parent_id) &&
                !excluded.has(option.id)
            ) {
                excluded.add(option.id);
                added = true;
            }
        }
    }

    return excluded;
});

const parentSelectOptions = computed(() =>
    props.parentOptions
        .filter((option) => !excludedParentIds.value.has(option.id))
        .map((option) => ({
            value: option.id,
            label: `${'   '.repeat(option.depth)}${option.depth ? '↳ ' : ''}${option.name}`,
        })),
);

const deleting = ref<Category | null>(null);
const deleteOpen = ref(false);
const deleteProcessing = ref(false);

function openCreate(parentId: number | null = null): void {
    editing.value = null;
    form.defaults(blankForm(parentId));
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(category: Category): void {
    editing.value = category;
    form.defaults({
        parent_id: category.parent_id,
        name: category.name,
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
        form.put(itemCategories.update(editing.value.id).url, options);
    } else {
        form.post(itemCategories.store().url, options);
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

    router.delete(itemCategories.destroy(deleting.value.id).url, {
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
    <Head title="Item categories" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Inventory"
            title="Item categories"
            description="Organize physical inventory into parent and child categories."
        >
            <template v-if="can.manage" #actions>
                <Button @click="openCreate()"><Plus /> New category</Button>
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

            <div v-if="props.categories.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Category</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Items
                            </th>
                            <th v-if="can.manage" class="px-5 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="category in props.categories"
                            :key="category.id"
                            class="hover:bg-accent/40"
                        >
                            <td class="px-5 py-3">
                                <div
                                    class="flex items-start gap-2"
                                    :style="{
                                        paddingLeft: `${category.depth * 1.5}rem`,
                                    }"
                                >
                                    <CornerDownRight
                                        v-if="category.depth > 0"
                                        class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    />
                                    <div class="min-w-0">
                                        <div class="font-medium">
                                            {{ category.name }}
                                        </div>
                                        <div
                                            v-if="
                                                isFiltered && category.depth > 0
                                            "
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ category.path }}
                                        </div>
                                        <p
                                            v-if="category.description"
                                            class="mt-1 line-clamp-2 max-w-md text-xs text-muted-foreground"
                                        >
                                            {{ category.description }}
                                        </p>
                                    </div>
                                </div>
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
                                    v-if="canViewItems"
                                    :href="
                                        inventory.items.index({
                                            query: { category: category.id },
                                        })
                                    "
                                    class="text-primary underline-offset-4 hover:underline"
                                    :aria-label="`View items in ${category.name}`"
                                    >{{ category.items_count }}</Link
                                >
                                <span v-else class="text-muted-foreground">{{
                                    category.items_count
                                }}</span>
                            </td>
                            <td v-if="can.manage" class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Add subcategory to ${category.name}`"
                                        @click="openCreate(category.id)"
                                    >
                                        <Plus />
                                    </Button>
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
                :icon="FolderTree"
                title="No categories found"
                description="Try a different search, or add a new category."
            />
        </Panel>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? `Edit ${editing.name}` : 'New category'"
            :icon="FolderTree"
            size="md"
        >
            <form @submit.prevent="submit">
                <FormSection>
                    <Field
                        label="Parent category"
                        for="category-parent"
                        :error="form.errors.parent_id"
                    >
                        <SelectInput
                            id="category-parent"
                            v-model="form.parent_id"
                            :options="parentSelectOptions"
                            placeholder="None — top-level category"
                        />
                    </Field>
                    <Field
                        label="Name"
                        for="category-name"
                        :error="form.errors.name"
                        hint="Must be unique among categories with the same parent."
                        required
                    >
                        <Input
                            id="category-name"
                            v-model="form.name"
                            placeholder="e.g. Sensors"
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
                        hint="Lower numbers are listed first among siblings."
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
                        Active — available for new inventory items
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
            description="Categories that contain items or subcategories cannot be deleted; deactivate them instead so existing items keep their category."
            confirm-label="Delete category"
            :processing="deleteProcessing"
            @confirm="destroy"
        />
    </div>
</template>
