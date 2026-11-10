<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { MapPin, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useFilters } from '@/composables/useFilters';
import { formatNumber } from '@/lib/format';
import locations from '@/routes/inventory/locations';
import type { Paginated } from '@/types';

useBreadcrumbs([
    { title: 'Setup', href: locations.index() },
    { title: 'Locations', href: locations.index() },
]);

type Location = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    is_active: boolean;
    is_main: boolean;
    item_count: number;
    total_on_hand: number;
    can_delete: boolean;
};

const props = defineProps<{
    locations: Paginated<Location>;
    filters: { search?: string; status?: string };
    can: { manage: boolean };
}>();

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        status: props.filters.status ?? null,
    },
    () => locations.index().url,
);

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const dialogOpen = ref(false);
const editing = ref<Location | null>(null);
const form = useForm({ code: '', name: '', description: '', is_active: true });

const deleting = ref<Location | null>(null);
const deleteOpen = ref(false);
const deleteProcessing = ref(false);

function openCreate(): void {
    editing.value = null;
    form.defaults({ code: '', name: '', description: '', is_active: true });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(location: Location): void {
    editing.value = location;
    form.defaults({
        code: location.code,
        name: location.name,
        description: location.description ?? '',
        is_active: location.is_active,
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
        form.put(locations.update(editing.value.id).url, options);
    } else {
        form.post(locations.store().url, options);
    }
}

function confirmDelete(location: Location): void {
    deleting.value = location;
    deleteOpen.value = true;
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(locations.destroy(deleting.value.id).url, {
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
    <Head title="Locations" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Inventory"
            title="Locations"
            description="Warehouses, rooms, and branches where stock is kept."
        >
            <template v-if="can.manage" #actions>
                <Button @click="openCreate"><Plus /> New location</Button>
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
                        placeholder="Search code or name"
                        aria-label="Search locations"
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

            <div v-if="props.locations.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Code</th>
                            <th class="px-5 py-2.5 font-medium">Name</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Items
                            </th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                On hand
                            </th>
                            <th v-if="can.manage" class="px-5 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="location in props.locations.data"
                            :key="location.id"
                            class="hover:bg-accent/40"
                        >
                            <td class="px-5 py-3 font-mono text-xs">
                                {{ location.code }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{
                                        location.name
                                    }}</span>
                                    <Badge
                                        v-if="location.is_main"
                                        variant="outline"
                                        >Default</Badge
                                    >
                                </div>
                                <p
                                    v-if="location.description"
                                    class="mt-0.5 line-clamp-1 text-xs text-muted-foreground"
                                >
                                    {{ location.description }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    :variant="
                                        location.is_active
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                    >{{
                                        location.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}</Badge
                                >
                            </td>
                            <td
                                class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                            >
                                {{ formatNumber(location.item_count, 0) }}
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                {{ formatNumber(location.total_on_hand) }}
                            </td>
                            <td v-if="can.manage" class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Edit ${location.name}`"
                                        @click="openEdit(location)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        v-if="location.can_delete"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Delete ${location.name}`"
                                        @click="confirmDelete(location)"
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
                :icon="MapPin"
                title="No locations found"
                description="Try a different search, or add a new location."
            />
            <Pagination :paginator="props.locations" />
        </Panel>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? `Edit ${editing.name}` : 'New location'"
            :icon="MapPin"
            size="md"
        >
            <form @submit.prevent="submit">
                <FormSection>
                    <Field
                        label="Code"
                        for="location-code"
                        :error="form.errors.code"
                        :hint="
                            editing?.is_main
                                ? 'The default location keeps its code.'
                                : 'Stored in uppercase, e.g. WH-01.'
                        "
                        required
                    >
                        <Input
                            id="location-code"
                            v-model="form.code"
                            class="font-mono uppercase"
                            placeholder="e.g. WH-01"
                            :disabled="editing?.is_main"
                        />
                    </Field>
                    <Field
                        label="Name"
                        for="location-name"
                        :error="form.errors.name"
                        required
                    >
                        <Input
                            id="location-name"
                            v-model="form.name"
                            placeholder="e.g. Main warehouse"
                        />
                    </Field>
                    <Field
                        class="sm:col-span-2"
                        label="Description"
                        for="location-description"
                        :error="form.errors.description"
                    >
                        <TextareaInput
                            id="location-description"
                            v-model="form.description"
                        />
                    </Field>
                    <Label
                        class="flex items-center gap-2.5 font-normal sm:col-span-2"
                    >
                        <Checkbox v-model="form.is_active" />
                        Active — can receive new stock postings
                    </Label>
                </FormSection>
                <FormFooter
                    :submit-label="
                        editing ? 'Save location' : 'Create location'
                    "
                    :processing="form.processing"
                    @cancel="dialogOpen = false"
                />
            </form>
        </FormDialog>

        <ConfirmDialog
            v-model:open="deleteOpen"
            :title="`Delete ${deleting?.name ?? 'location'}?`"
            description="Locations that hold stock or have ledger history cannot be deleted; deactivate them instead. Their history stays visible."
            confirm-label="Delete location"
            :processing="deleteProcessing"
            @confirm="destroy"
        />
    </div>
</template>
