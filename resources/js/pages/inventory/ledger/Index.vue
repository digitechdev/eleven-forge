<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BookOpen, Plus, Search, Undo2 } from '@lucide/vue';
import { ref } from 'vue';
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
import PostTransactionDialog from '@/components/stock/PostTransactionDialog.vue';
import type { PostingOptions } from '@/components/stock/PostTransactionDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useFilters } from '@/composables/useFilters';
import { formatDateTime, formatNumber } from '@/lib/format';
import items from '@/routes/inventory/items';
import ledger from '@/routes/inventory/ledger';
import type { Paginated } from '@/types';

useBreadcrumbs([
    { title: 'Inventory', href: items.index() },
    { title: 'Stock movements', href: ledger.index() },
]);

type Entry = {
    id: number;
    entry_number: string;
    posted_at: string;
    transaction_type: string;
    transaction_type_label: string;
    item_id: number;
    item_code: string;
    item_name: string;
    item_is_deleted: boolean;
    unit_symbol: string;
    location_code: string;
    location_name: string;
    quantity_in: number;
    quantity_out: number;
    unit_cost: number;
    total_value: number;
    balance_after: number;
    reference_number: string | null;
    created_by: string | null;
    notes: string | null;
    reversed_by: string | null;
    reverses: string | null;
    can_reverse: boolean;
};

type Option = { value: string; label: string };
type RecordOption = { value: number; label: string };

const props = defineProps<{
    entries: Paginated<Entry>;
    filters: {
        item?: string;
        location?: string;
        type?: string;
        from?: string;
        to?: string;
        reference?: string;
    };
    items: RecordOption[];
    locations: RecordOption[];
    types: Option[];
    posting: PostingOptions | null;
    can: { post: boolean };
}>();

const { filters } = useFilters(
    {
        item: props.filters.item ? Number(props.filters.item) : null,
        location: props.filters.location
            ? Number(props.filters.location)
            : null,
        type: props.filters.type ?? null,
        from: props.filters.from ?? null,
        to: props.filters.to ?? null,
        reference: props.filters.reference ?? null,
    },
    () => ledger.index().url,
);

const typeVariant: Record<string, 'secondary' | 'outline' | 'destructive'> = {
    ISSUE: 'outline',
    BUILD_CONSUMPTION: 'outline',
    REVERSAL: 'destructive',
};

const postingOpen = ref(false);

const reversing = ref<Entry | null>(null);
const reverseOpen = ref(false);
const reverseForm = useForm({ reason: '' });

/** "LED-01M720CRH450T9G6C0WWE0DRWS" → "LED-…0DRWS"; the full number is shown on hover. */
function shortEntry(entryNumber: string): string {
    return entryNumber.length > 14
        ? `${entryNumber.slice(0, 4)}…${entryNumber.slice(-5)}`
        : entryNumber;
}

function confirmReverse(entry: Entry): void {
    reversing.value = entry;
    reverseForm.reset();
    reverseForm.clearErrors();
    reverseOpen.value = true;
}

function reverse(): void {
    if (!reversing.value) {
        return;
    }

    reverseForm.post(ledger.reverse(reversing.value.id).url, {
        preserveScroll: true,
        onSuccess: () => (reverseOpen.value = false),
    });
}
</script>

<template>
    <Head title="Stock movements" />

    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Inventory"
            title="Stock movements"
            description="Every stock movement, newest first. Entries are never edited; mistakes are corrected with a reversal."
        >
            <template v-if="can.post && posting" #actions>
                <Button @click="postingOpen = true"
                    ><Plus /> Post transaction</Button
                >
            </template>
        </PageHeader>

        <Panel flush>
            <div class="flex flex-wrap items-center gap-3 border-b px-5 py-3">
                <div class="relative min-w-48 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="filters.reference"
                        class="pl-9"
                        placeholder="Search reference number"
                        aria-label="Search reference number"
                    />
                </div>
                <SelectInput
                    v-model="filters.item"
                    class="w-56"
                    :options="props.items"
                    placeholder="All items"
                    aria-label="Filter by item"
                />
                <SelectInput
                    v-model="filters.location"
                    class="w-44"
                    :options="locations"
                    placeholder="All locations"
                    aria-label="Filter by location"
                />
                <SelectInput
                    v-model="filters.type"
                    class="w-44"
                    :options="types"
                    placeholder="All types"
                    aria-label="Filter by transaction type"
                />
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Posted</span>
                    <Input
                        v-model="filters.from"
                        type="date"
                        class="w-40"
                        aria-label="Posted from"
                    />
                    <span class="text-muted-foreground" aria-hidden="true"
                        >–</span
                    >
                    <Input
                        v-model="filters.to"
                        type="date"
                        class="w-40"
                        aria-label="Posted until"
                    />
                </div>
            </div>

            <div v-if="props.entries.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-4 py-2.5 font-medium">Posted</th>
                            <th class="px-4 py-2.5 font-medium">Item</th>
                            <th class="px-4 py-2.5 font-medium">Location</th>
                            <th class="px-4 py-2.5 font-medium">Type</th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Quantity
                            </th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Value
                            </th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Balance
                            </th>
                            <th class="px-4 py-2.5 font-medium">Reference</th>
                            <th v-if="can.post" class="w-12 px-4 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="entry in props.entries.data" :key="entry.id">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="font-medium">
                                    {{ formatDateTime(entry.posted_at) }}
                                </p>
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                    :title="entry.entry_number"
                                >
                                    {{ shortEntry(entry.entry_number) }}
                                    <template v-if="entry.created_by">
                                        ·
                                        <span class="font-sans">{{
                                            entry.created_by
                                        }}</span>
                                    </template>
                                </p>
                            </td>
                            <td class="max-w-56 px-4 py-3">
                                <span
                                    v-if="entry.item_is_deleted"
                                    class="block truncate font-medium"
                                    :title="entry.item_name"
                                    >{{ entry.item_name }}</span
                                >
                                <Link
                                    v-else
                                    :href="items.show(entry.item_id)"
                                    class="block truncate font-medium hover:text-primary hover:underline"
                                    :title="entry.item_name"
                                >
                                    {{ entry.item_name }}
                                </Link>
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ entry.item_code }}
                                </p>
                            </td>
                            <td
                                class="px-4 py-3 whitespace-nowrap"
                                :title="entry.location_code"
                            >
                                {{ entry.location_name }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Badge
                                    :variant="
                                        typeVariant[entry.transaction_type] ??
                                        'secondary'
                                    "
                                    >{{ entry.transaction_type_label }}</Badge
                                >
                                <p
                                    v-if="entry.reverses"
                                    class="mt-1 text-xs text-muted-foreground"
                                    :title="entry.reverses"
                                >
                                    Reverses
                                    <span class="font-mono">{{
                                        shortEntry(entry.reverses)
                                    }}</span>
                                </p>
                                <p
                                    v-if="entry.reversed_by"
                                    class="mt-1 text-xs text-muted-foreground"
                                    :title="entry.reversed_by"
                                >
                                    Reversed by
                                    <span class="font-mono">{{
                                        shortEntry(entry.reversed_by)
                                    }}</span>
                                </p>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span
                                    :class="[
                                        'font-semibold',
                                        entry.quantity_in
                                            ? 'text-chart-5'
                                            : 'text-destructive',
                                    ]"
                                    >{{ entry.quantity_in ? '+' : '−'
                                    }}{{
                                        formatNumber(
                                            entry.quantity_in ||
                                                entry.quantity_out,
                                        )
                                    }}</span
                                >
                                <span
                                    class="ml-1 font-mono text-xs text-muted-foreground"
                                    >{{ entry.unit_symbol }}</span
                                >
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <p
                                    :class="
                                        entry.total_value < 0
                                            ? 'text-destructive'
                                            : ''
                                    "
                                >
                                    {{ formatNumber(entry.total_value) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    @ {{ formatNumber(entry.unit_cost) }}
                                </p>
                            </td>
                            <td
                                class="px-4 py-3 text-right font-medium whitespace-nowrap"
                            >
                                {{ formatNumber(entry.balance_after) }}
                                <span
                                    class="font-mono text-xs font-normal text-muted-foreground"
                                    >{{ entry.unit_symbol }}</span
                                >
                            </td>
                            <td class="max-w-48 px-4 py-3">
                                <p
                                    v-if="entry.reference_number"
                                    class="truncate font-mono text-xs"
                                    :title="entry.reference_number"
                                >
                                    {{ entry.reference_number }}
                                </p>
                                <p
                                    v-if="entry.notes"
                                    class="truncate text-xs text-muted-foreground"
                                    :title="entry.notes"
                                >
                                    {{ entry.notes }}
                                </p>
                                <span
                                    v-if="
                                        !entry.reference_number && !entry.notes
                                    "
                                    class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                            <td v-if="can.post" class="px-4 py-3">
                                <div class="flex justify-end">
                                    <Button
                                        v-if="entry.can_reverse"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Reverse ${entry.entry_number}`"
                                        :title="`Reverse ${entry.entry_number}`"
                                        @click="confirmReverse(entry)"
                                    >
                                        <Undo2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="BookOpen"
                title="No stock movements found"
                description="Try different filters, or post a transaction."
            />
            <Pagination :paginator="props.entries" />
        </Panel>

        <PostTransactionDialog
            v-if="posting"
            v-model:open="postingOpen"
            :options="posting"
            :item-id="filters.item"
            :location-id="filters.location"
        />

        <FormDialog
            v-model:open="reverseOpen"
            :title="`Reverse ${reversing?.entry_number ?? 'entry'}?`"
            description="A reversal posts the opposite movement at the same location and cost. Both entries stay in the ledger, and an entry can be reversed only once."
            :icon="Undo2"
            size="md"
        >
            <form @submit.prevent="reverse">
                <FormSection :columns="1">
                    <Field
                        label="Reason"
                        for="reverse-reason"
                        :error="reverseForm.errors.reason"
                        required
                    >
                        <TextareaInput
                            id="reverse-reason"
                            v-model="reverseForm.reason"
                            placeholder="Why is this entry being reversed?"
                        />
                    </Field>
                </FormSection>
                <FormFooter
                    submit-label="Post reversal"
                    :processing="reverseForm.processing"
                    @cancel="reverseOpen = false"
                />
            </form>
        </FormDialog>
    </div>
</template>
