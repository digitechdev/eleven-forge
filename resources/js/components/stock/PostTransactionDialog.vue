<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowLeftRight } from '@lucide/vue';
import { computed, watch } from 'vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import { Input } from '@/components/ui/input';
import { todayIso } from '@/lib/format';
import ledger from '@/routes/inventory/ledger';

export type PostingItemOption = {
    value: number;
    label: string;
    unit_cost: number | null;
    unit_symbol: string;
    decimal_places: number;
};

export type PostingOptions = {
    items: PostingItemOption[];
    locations: { value: number; label: string }[];
    types: { value: string; label: string }[];
};

/**
 * The shared "Post transaction" form: opening balance, receive, issue, return, or adjustment.
 * Every movement goes through the stock ledger; mistakes are corrected with a reversal.
 */
const props = defineProps<{
    options: PostingOptions;
    itemId?: number | null;
    locationId?: number | null;
}>();

const open = defineModel<boolean>('open', { required: true });

type PostingForm = {
    inventory_item_id: number | null;
    location_id: number | null;
    transaction_type: string | null;
    direction: string | null;
    quantity: number | string;
    unit_cost: number | string | null;
    posted_at: string;
    reference_number: string;
    notes: string;
};

const form = useForm<PostingForm>(blankForm());

const directionOptions = [
    { value: 'increase', label: 'Increase stock' },
    { value: 'decrease', label: 'Decrease stock' },
];

const selectedItem = computed(
    () =>
        props.options.items.find(
            (item) => item.value === form.inventory_item_id,
        ) ?? null,
);
const isAdjustment = computed(() => form.transaction_type === 'ADJUSTMENT');
const needsCost = computed(() =>
    ['OPENING_BALANCE', 'RECEIVE'].includes(form.transaction_type ?? ''),
);
const quantityStep = computed(() => {
    const places = selectedItem.value?.decimal_places ?? 0;

    return places === 0 ? '1' : (1 / 10 ** places).toFixed(places);
});

function blankForm(): PostingForm {
    return {
        inventory_item_id: props.itemId ?? null,
        location_id: props.locationId ?? null,
        transaction_type: 'RECEIVE',
        direction: null,
        quantity: '',
        unit_cost: null,
        posted_at: todayIso(),
        reference_number: '',
        notes: '',
    };
}

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    form.unit_cost = selectedItem.value?.unit_cost ?? null;
});

watch(
    () => form.inventory_item_id,
    () => (form.unit_cost = selectedItem.value?.unit_cost ?? null),
);

watch(isAdjustment, (adjusting) => {
    if (!adjusting) {
        form.direction = null;
    }
});

function submit(): void {
    form.post(ledger.store().url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <FormDialog
        v-model:open="open"
        title="Post transaction"
        description="Every stock movement is recorded in the ledger. Posted entries cannot be edited; reverse them instead."
        :icon="ArrowLeftRight"
    >
        <form @submit.prevent="submit">
            <FormSection title="Movement">
                <Field
                    class="sm:col-span-2"
                    label="Item"
                    for="posting-item"
                    :error="form.errors.inventory_item_id"
                    required
                >
                    <SelectInput
                        id="posting-item"
                        v-model="form.inventory_item_id"
                        :options="options.items"
                        placeholder="Select an item"
                    />
                </Field>
                <Field
                    label="Location"
                    for="posting-location"
                    :error="form.errors.location_id"
                    required
                >
                    <SelectInput
                        id="posting-location"
                        v-model="form.location_id"
                        :options="options.locations"
                        placeholder="Select a location"
                    />
                </Field>
                <Field
                    label="Transaction type"
                    for="posting-type"
                    :error="form.errors.transaction_type"
                    required
                >
                    <SelectInput
                        id="posting-type"
                        v-model="form.transaction_type"
                        :options="options.types"
                    />
                </Field>
                <Field
                    v-if="isAdjustment"
                    label="Direction"
                    for="posting-direction"
                    :error="form.errors.direction"
                    required
                >
                    <SelectInput
                        id="posting-direction"
                        v-model="form.direction"
                        :options="directionOptions"
                        placeholder="Increase or decrease"
                    />
                </Field>
                <Field
                    label="Quantity"
                    for="posting-quantity"
                    :error="form.errors.quantity"
                    :hint="
                        selectedItem
                            ? `In ${selectedItem.unit_symbol}.`
                            : undefined
                    "
                    required
                >
                    <Input
                        id="posting-quantity"
                        v-model="form.quantity"
                        type="number"
                        min="0"
                        :step="quantityStep"
                    />
                </Field>
                <Field
                    label="Unit cost"
                    for="posting-cost"
                    :error="form.errors.unit_cost"
                    :hint="
                        needsCost
                            ? undefined
                            : 'Defaults to the item\'s current unit cost.'
                    "
                    :required="needsCost"
                >
                    <Input
                        id="posting-cost"
                        v-model="form.unit_cost"
                        type="number"
                        min="0"
                        step="0.01"
                    />
                </Field>
                <Field
                    label="Posting date"
                    for="posting-date"
                    :error="form.errors.posted_at"
                    required
                >
                    <Input
                        id="posting-date"
                        v-model="form.posted_at"
                        type="date"
                        :max="todayIso()"
                    />
                </Field>
            </FormSection>

            <FormSection title="Reference">
                <Field
                    label="Reference number"
                    for="posting-reference"
                    :error="form.errors.reference_number"
                    :required="isAdjustment"
                >
                    <Input
                        id="posting-reference"
                        v-model="form.reference_number"
                        placeholder="e.g. PO-2026-014"
                    />
                </Field>
                <Field
                    class="sm:col-span-2"
                    :label="isAdjustment ? 'Reason' : 'Notes'"
                    for="posting-notes"
                    :error="form.errors.notes"
                    :required="isAdjustment"
                >
                    <TextareaInput
                        id="posting-notes"
                        v-model="form.notes"
                        :placeholder="
                            isAdjustment
                                ? 'Why is stock being adjusted?'
                                : undefined
                        "
                    />
                </Field>
            </FormSection>

            <FormFooter
                submit-label="Post transaction"
                :processing="form.processing"
                @cancel="open = false"
            />
        </form>
    </FormDialog>
</template>
