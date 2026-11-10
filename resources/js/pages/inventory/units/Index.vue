<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Ruler, Search, Trash2 } from '@lucide/vue';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilters } from '@/composables/useFilters';
import units from '@/routes/inventory/units';
import type { Paginated } from '@/types';
import { index as unitsIndexCrumb } from '@/routes/inventory/units';
import { index as setupCrumb } from '@/routes/inventory/locations';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([
    { title: 'Setup', href: setupCrumb() },
    { title: 'Units', href: unitsIndexCrumb() },
]);

type Unit = {
    id: number;
    name: string;
    symbol: string;
    decimal_places: number;
    is_active: boolean;
    usage_count: number;
};

const props = defineProps<{
    units: Paginated<Unit>;
    filters: { search?: string; status?: string };
    can: { manage: boolean };
}>();

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        status: props.filters.status ?? null,
    },
    () => units.index().url,
);

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const dialogOpen = ref(false);
const editing = ref<Unit | null>(null);
const precisionOptions = [
    { value: 0, label: 'Whole units (0)' },
    { value: 1, label: '1 decimal place' },
    { value: 2, label: '2 decimal places' },
    { value: 3, label: '3 decimal places' },
];

const form = useForm({
    name: '',
    symbol: '',
    decimal_places: 0 as number,
    is_active: true,
});

const deleting = ref<Unit | null>(null);
const deleteOpen = ref(false);
const deleteProcessing = ref(false);

function openCreate(): void {
    editing.value = null;
    form.defaults({
        name: '',
        symbol: '',
        decimal_places: 0,
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(unit: Unit): void {
    editing.value = unit;
    form.defaults({
        name: unit.name,
        symbol: unit.symbol,
        decimal_places: unit.decimal_places,
        is_active: unit.is_active,
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
        form.put(units.update(editing.value.id).url, options);
    } else {
        form.post(units.store().url, options);
    }
}

function confirmDelete(unit: Unit): void {
    deleting.value = unit;
    deleteOpen.value = true;
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(units.destroy(deleting.value.id).url, {
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
    <Head title="Units" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Inventory"
            title="Units"
            description="Standard measurement units for inventory items and bills of materials."
        >
            <template v-if="can.manage" #actions>
                <Button @click="openCreate"><Plus /> New unit</Button>
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
                        placeholder="Search name or symbol"
                        aria-label="Search units"
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

            <div v-if="props.units.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Name</th>
                            <th class="px-5 py-2.5 font-medium">Symbol</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Decimals
                            </th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                In use
                            </th>
                            <th v-if="can.manage" class="px-5 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="unit in props.units.data"
                            :key="unit.id"
                            class="hover:bg-accent/40"
                        >
                            <td class="px-5 py-3 font-medium">
                                {{ unit.name }}
                            </td>
                            <td class="px-5 py-3 font-mono">
                                {{ unit.symbol }}
                            </td>
                            <td
                                class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                            >
                                {{ unit.decimal_places }}
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    :variant="
                                        unit.is_active ? 'secondary' : 'outline'
                                    "
                                    >{{
                                        unit.is_active ? 'Active' : 'Inactive'
                                    }}</Badge
                                >
                            </td>
                            <td
                                class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                            >
                                {{ unit.usage_count }}
                            </td>
                            <td v-if="can.manage" class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Edit ${unit.name}`"
                                        @click="openEdit(unit)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Delete ${unit.name}`"
                                        @click="confirmDelete(unit)"
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
                :icon="Ruler"
                title="No units found"
                description="Try a different search, or add a new unit."
            />
            <Pagination :paginator="props.units" />
        </Panel>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? `Edit ${editing.name}` : 'New unit'"
            :icon="Ruler"
            size="md"
        >
            <form @submit.prevent="submit">
                <FormSection>
                    <Field
                        label="Name"
                        for="unit-name"
                        :error="form.errors.name"
                        required
                    >
                        <Input
                            id="unit-name"
                            v-model="form.name"
                            placeholder="e.g. Piece"
                        />
                    </Field>
                    <Field
                        label="Symbol"
                        for="unit-symbol"
                        :error="form.errors.symbol"
                        required
                    >
                        <Input
                            id="unit-symbol"
                            v-model="form.symbol"
                            placeholder="e.g. pc"
                        />
                    </Field>
                    <Field
                        label="Precision"
                        for="unit-decimal-places"
                        :error="form.errors.decimal_places"
                        hint="How many decimal places quantities in this unit may have."
                        required
                    >
                        <SelectInput
                            id="unit-decimal-places"
                            v-model="form.decimal_places"
                            :options="precisionOptions"
                        />
                    </Field>
                    <Label class="flex items-center gap-2.5 font-normal">
                        <Checkbox v-model="form.is_active" />
                        Active — available for new items and BOM lines
                    </Label>
                </FormSection>
                <FormFooter
                    :submit-label="editing ? 'Save unit' : 'Create unit'"
                    :processing="form.processing"
                    @cancel="dialogOpen = false"
                />
            </form>
        </FormDialog>

        <ConfirmDialog
            v-model:open="deleteOpen"
            :title="`Delete ${deleting?.name ?? 'unit'}?`"
            description="Units that are already used by items or bills of materials cannot be deleted; deactivate them instead."
            confirm-label="Delete unit"
            :processing="deleteProcessing"
            @confirm="destroy"
        />
    </div>
</template>
