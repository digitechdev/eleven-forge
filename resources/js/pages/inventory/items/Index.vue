<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Boxes,
    CircleAlert,
    Package,
    Pencil,
    Plus,
    Search,
    Trash2,
    Wallet,
} from '@lucide/vue';
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
import StatCard from '@/components/common/StatCard.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilters } from '@/composables/useFilters';
import { formatNumber } from '@/lib/format';
import items from '@/routes/inventory/items';
import type { Paginated } from '@/types';
import { index as itemsIndexCrumb } from '@/routes/inventory/items';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([
    { title: 'Inventory', href: itemsIndexCrumb() },
    { title: 'Items', href: itemsIndexCrumb() },
]);

type Item = {
    id: number;
    item_code: string;
    name: string;
    item_type: string;
    item_type_label: string;
    category_id: number;
    category_name: string;
    default_unit_id: number;
    unit_symbol: string;
    brand: string | null;
    model: string | null;
    manufacturer: string | null;
    part_number: string | null;
    description: string | null;
    specification: string | null;
    quantity_on_hand: number;
    minimum_stock: number;
    unit_cost: number | null;
    status: string;
    status_label: string;
    is_low_stock: boolean;
};

type Option = { value: string; label: string };
type RecordOption = { value: number; label: string; is_active: boolean };

const props = defineProps<{
    items: Paginated<Item>;
    stats: { total: number; low_stock: number; stock_value: number };
    filters: {
        search?: string;
        category?: string;
        type?: string;
        status?: string;
        stock?: string;
    };
    categories: RecordOption[];
    units: RecordOption[];
    types: Option[];
    statuses: Option[];
    can: { manage: boolean };
}>();

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        category: props.filters.category
            ? Number(props.filters.category)
            : null,
        type: props.filters.type ?? null,
        status: props.filters.status ?? null,
        stock: props.filters.stock ?? null,
    },
    () => items.index().url,
);

const lowStockOnly = computed({
    get: () => filters.stock === 'low',
    set: (value: boolean | 'indeterminate') =>
        (filters.stock = value === true ? 'low' : null),
});

const statusVariant: Record<string, 'secondary' | 'outline' | 'destructive'> = {
    ACTIVE: 'secondary',
    INACTIVE: 'outline',
    DISCONTINUED: 'outline',
};

type ItemForm = {
    item_code: string;
    name: string;
    item_type: string | null;
    category_id: number | null;
    default_unit_id: number | null;
    brand: string;
    model: string;
    manufacturer: string;
    part_number: string;
    description: string;
    specification: string;
    quantity_on_hand: number | string;
    minimum_stock: number | string;
    unit_cost: number | string | null;
    status: string;
};

function blankForm(): ItemForm {
    return {
        item_code: '',
        name: '',
        item_type: 'COMPONENT',
        category_id: null,
        default_unit_id: null,
        brand: '',
        model: '',
        manufacturer: '',
        part_number: '',
        description: '',
        specification: '',
        quantity_on_hand: 0,
        minimum_stock: 0,
        unit_cost: null,
        status: 'ACTIVE',
    };
}

const dialogOpen = ref(false);
const editing = ref<Item | null>(null);
const form = useForm<ItemForm>(blankForm());

/** Active records, plus the inactive one the edited item still uses. */
function selectable(options: RecordOption[], current: number | null) {
    return options
        .filter((option) => option.is_active || option.value === current)
        .map((option) => ({
            value: option.value,
            label: option.is_active
                ? option.label
                : `${option.label} (inactive)`,
        }));
}

const categoryChoices = computed(() =>
    selectable(props.categories, editing.value?.category_id ?? null),
);
const unitChoices = computed(() =>
    selectable(props.units, editing.value?.default_unit_id ?? null),
);
const categoryFilterOptions = computed(() =>
    props.categories.map((c) => ({ value: c.value, label: c.label })),
);

const deleting = ref<Item | null>(null);
const deleteOpen = ref(false);
const deleteProcessing = ref(false);

function openCreate(): void {
    editing.value = null;
    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(item: Item): void {
    editing.value = item;
    form.defaults({
        item_code: item.item_code,
        name: item.name,
        item_type: item.item_type,
        category_id: item.category_id,
        default_unit_id: item.default_unit_id,
        brand: item.brand ?? '',
        model: item.model ?? '',
        manufacturer: item.manufacturer ?? '',
        part_number: item.part_number ?? '',
        description: item.description ?? '',
        specification: item.specification ?? '',
        quantity_on_hand: item.quantity_on_hand,
        minimum_stock: item.minimum_stock,
        unit_cost: item.unit_cost,
        status: item.status,
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
        form.put(items.update(editing.value.id).url, options);
    } else {
        form.post(items.store().url, options);
    }
}

function confirmDelete(item: Item): void {
    deleting.value = item;
    deleteOpen.value = true;
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(items.destroy(deleting.value.id).url, {
        preserveScroll: true,
        onStart: () => (deleteProcessing.value = true),
        onFinish: () => {
            deleteProcessing.value = false;
            deleteOpen.value = false;
        },
    });
}

function brandModel(item: Item): string {
    return [item.brand, item.model].filter(Boolean).join(' · ') || '—';
}
</script>

<template>
    <Head title="Inventory items" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Inventory"
            title="Inventory items"
            description="The master catalog for physical materials, components, and equipment."
        >
            <template v-if="can.manage" #actions>
                <Button @click="openCreate"><Plus /> New item</Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-3">
            <StatCard
                label="Total items"
                :value="formatNumber(stats.total, 0)"
                :icon="Boxes"
            />
            <StatCard
                label="Low stock"
                :value="formatNumber(stats.low_stock, 0)"
                :icon="CircleAlert"
                :tone="stats.low_stock > 0 ? 'danger' : 'patina'"
            />
            <StatCard
                label="Stock value"
                :value="formatNumber(stats.stock_value)"
                unit="PHP"
                :icon="Wallet"
                tone="brass"
            />
        </div>

        <Panel flush>
            <div class="flex flex-wrap items-center gap-3 border-b px-5 py-3">
                <div class="relative min-w-56 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="filters.search"
                        class="pl-9"
                        placeholder="Search code, name, brand, model, part no."
                        aria-label="Search items"
                    />
                </div>
                <SelectInput
                    v-model="filters.category"
                    class="w-48"
                    :options="categoryFilterOptions"
                    placeholder="All categories"
                    aria-label="Filter by category"
                />
                <SelectInput
                    v-model="filters.type"
                    class="w-40"
                    :options="types"
                    placeholder="All types"
                    aria-label="Filter by type"
                />
                <SelectInput
                    v-model="filters.status"
                    class="w-36"
                    :options="statuses"
                    placeholder="All statuses"
                    aria-label="Filter by status"
                />
                <Label class="flex items-center gap-2 font-normal">
                    <Checkbox v-model="lowStockOnly" />
                    Low stock only
                </Label>
            </div>

            <div v-if="props.items.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Code</th>
                            <th class="px-5 py-2.5 font-medium">Name</th>
                            <th class="px-5 py-2.5 font-medium">Category</th>
                            <th class="px-5 py-2.5 font-medium">Type</th>
                            <th class="px-5 py-2.5 font-medium">
                                Brand / model
                            </th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                On hand
                            </th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th v-if="can.manage" class="px-5 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="item in props.items.data"
                            :key="item.id"
                            class="hover:bg-accent/40"
                        >
                            <td class="px-5 py-3 font-mono text-xs">
                                {{ item.item_code }}
                            </td>
                            <td class="px-5 py-3 font-medium">
                                <Link
                                    :href="items.show(item.id)"
                                    class="hover:text-primary hover:underline"
                                >
                                    {{ item.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ item.category_name }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ item.item_type_label }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ brandModel(item) }}
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Badge
                                        v-if="item.is_low_stock"
                                        variant="destructive"
                                        :title="`Minimum stock: ${formatNumber(item.minimum_stock)} ${item.unit_symbol}`"
                                    >
                                        <CircleAlert /> Low
                                    </Badge>
                                    <span
                                        :class="
                                            item.is_low_stock
                                                ? 'font-semibold text-destructive'
                                                : ''
                                        "
                                    >
                                        {{
                                            formatNumber(item.quantity_on_hand)
                                        }}
                                    </span>
                                    <span
                                        class="font-mono text-xs text-muted-foreground"
                                        >{{ item.unit_symbol }}</span
                                    >
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    :variant="
                                        statusVariant[item.status] ?? 'outline'
                                    "
                                    >{{ item.status_label }}</Badge
                                >
                            </td>
                            <td v-if="can.manage" class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Edit ${item.name}`"
                                        @click="openEdit(item)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Delete ${item.name}`"
                                        @click="confirmDelete(item)"
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
                :icon="Package"
                title="No items found"
                description="Try different filters, or add a new item."
            />
            <Pagination :paginator="props.items" />
        </Panel>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? `Edit ${editing.name}` : 'New item'"
            :icon="Package"
            size="xl"
        >
            <form @submit.prevent="submit">
                <FormSection title="Identification">
                    <Field
                        label="Item code"
                        for="item-code"
                        :error="form.errors.item_code"
                        hint="Leave blank to generate one, e.g. ITM-00001."
                    >
                        <Input
                            id="item-code"
                            v-model="form.item_code"
                            placeholder="Auto-generated"
                        />
                    </Field>
                    <Field
                        label="Name"
                        for="item-name"
                        :error="form.errors.name"
                        required
                    >
                        <Input
                            id="item-name"
                            v-model="form.name"
                            placeholder="e.g. Stepper motor NEMA 17"
                        />
                    </Field>
                    <Field
                        label="Type"
                        for="item-type"
                        :error="form.errors.item_type"
                        required
                    >
                        <SelectInput
                            id="item-type"
                            v-model="form.item_type"
                            :options="types"
                        />
                    </Field>
                    <Field
                        label="Category"
                        for="item-category"
                        :error="form.errors.category_id"
                        required
                    >
                        <SelectInput
                            id="item-category"
                            v-model="form.category_id"
                            :options="categoryChoices"
                            placeholder="Select a category"
                        />
                    </Field>
                    <Field
                        label="Default unit"
                        for="item-unit"
                        :error="form.errors.default_unit_id"
                        required
                    >
                        <SelectInput
                            id="item-unit"
                            v-model="form.default_unit_id"
                            :options="unitChoices"
                            placeholder="Select a unit"
                        />
                    </Field>
                </FormSection>

                <FormSection title="Details">
                    <Field
                        label="Brand"
                        for="item-brand"
                        :error="form.errors.brand"
                    >
                        <Input id="item-brand" v-model="form.brand" />
                    </Field>
                    <Field
                        label="Model"
                        for="item-model"
                        :error="form.errors.model"
                    >
                        <Input id="item-model" v-model="form.model" />
                    </Field>
                    <Field
                        label="Manufacturer"
                        for="item-manufacturer"
                        :error="form.errors.manufacturer"
                    >
                        <Input
                            id="item-manufacturer"
                            v-model="form.manufacturer"
                        />
                    </Field>
                    <Field
                        label="Part number"
                        for="item-part-number"
                        :error="form.errors.part_number"
                    >
                        <Input
                            id="item-part-number"
                            v-model="form.part_number"
                        />
                    </Field>
                    <Field
                        class="sm:col-span-2"
                        label="Description"
                        for="item-description"
                        :error="form.errors.description"
                    >
                        <TextareaInput
                            id="item-description"
                            v-model="form.description"
                        />
                    </Field>
                    <Field
                        class="sm:col-span-2"
                        label="Specification"
                        for="item-specification"
                        :error="form.errors.specification"
                    >
                        <TextareaInput
                            id="item-specification"
                            v-model="form.specification"
                            placeholder="Voltage, dimensions, tolerances…"
                        />
                    </Field>
                </FormSection>

                <FormSection title="Stock">
                    <Field
                        label="Quantity on hand"
                        for="item-quantity"
                        :error="form.errors.quantity_on_hand"
                        required
                    >
                        <Input
                            id="item-quantity"
                            v-model="form.quantity_on_hand"
                            type="number"
                            min="0"
                            step="any"
                        />
                    </Field>
                    <Field
                        label="Minimum stock"
                        for="item-minimum"
                        :error="form.errors.minimum_stock"
                        hint="Flag as low stock at or below this amount. 0 disables."
                        required
                    >
                        <Input
                            id="item-minimum"
                            v-model="form.minimum_stock"
                            type="number"
                            min="0"
                            step="any"
                        />
                    </Field>
                    <Field
                        label="Unit cost"
                        for="item-cost"
                        :error="form.errors.unit_cost"
                    >
                        <Input
                            id="item-cost"
                            v-model="form.unit_cost"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                    </Field>
                    <Field
                        label="Status"
                        for="item-status"
                        :error="form.errors.status"
                        required
                    >
                        <SelectInput
                            id="item-status"
                            v-model="form.status"
                            :options="statuses"
                        />
                    </Field>
                </FormSection>

                <FormFooter
                    :submit-label="editing ? 'Save item' : 'Create item'"
                    :processing="form.processing"
                    @cancel="dialogOpen = false"
                />
            </form>
        </FormDialog>

        <ConfirmDialog
            v-model:open="deleteOpen"
            :title="`Delete ${deleting?.name ?? 'item'}?`"
            description="Items used in an innovation bill of materials cannot be deleted; set their status to Discontinued instead."
            confirm-label="Delete item"
            :processing="deleteProcessing"
            @confirm="destroy"
        />
    </div>
</template>
