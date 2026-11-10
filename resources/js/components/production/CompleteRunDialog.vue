<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Hammer } from '@lucide/vue';
import { computed, watch } from 'vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import type { BuildOrderRow } from '@/components/production/types';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { formatNumber } from '@/lib/format';
import buildOrders from '@/routes/production/build-orders';

/**
 * Record a production run against a build, previewing the parts it will consume
 * against the stock available at the order's location.
 */
const props = defineProps<{ order: BuildOrderRow | null }>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm<{
    output_quantity: number | string;
    notes: string | null;
}>({ output_quantity: 1, notes: null });

watch(open, (isOpen) => {
    if (isOpen && props.order) {
        form.defaults({
            output_quantity: props.order.remaining_quantity,
            notes: null,
        });
        form.reset();
        form.clearErrors();
    }
});

const output = computed(() => {
    const value = Number(form.output_quantity);

    return Number.isFinite(value) && value > 0 ? value : 0;
});

/** Each required part scaled from the formula yield to the chosen output. */
const preview = computed(() => {
    const completion = props.order?.completion;

    if (!completion || completion.yield_quantity <= 0) {
        return [];
    }

    const multiplier = output.value / completion.yield_quantity;

    return completion.lines.map((line) => {
        const required =
            Math.round(line.quantity_required * multiplier * 1000) / 1000;

        return {
            ...line,
            required,
            cost: Math.round(required * line.unit_cost * 100) / 100,
            short: required > line.available,
        };
    });
});

const totalCost = computed(() =>
    preview.value.reduce((sum, line) => sum + line.cost, 0),
);

const hasShortfall = computed(() => preview.value.some((line) => line.short));

function submit(): void {
    if (!props.order) {
        return;
    }

    form.post(buildOrders.runs.store(props.order.id).url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <FormDialog
        v-model:open="open"
        :title="`Complete run for ${order?.order_number ?? 'build'}`"
        :description="
            order
                ? `${order.innovation.name} · ${formatNumber(order.remaining_quantity, 3)} of ${formatNumber(order.planned_quantity, 3)} remaining · parts taken from ${order.location}`
                : undefined
        "
        :icon="Hammer"
        size="xl"
    >
        <form @submit.prevent="submit">
            <FormSection>
                <Field
                    label="Output quantity"
                    for="run-output"
                    :error="form.errors.output_quantity"
                    :hint="`Up to ${formatNumber(order?.remaining_quantity ?? 0, 3)}.`"
                    required
                >
                    <Input
                        id="run-output"
                        v-model="form.output_quantity"
                        type="number"
                        min="0"
                        :max="order?.remaining_quantity"
                        step="0.001"
                    />
                </Field>
                <Field
                    label="Notes"
                    for="run-notes"
                    :error="form.errors.notes"
                    class="sm:col-span-2"
                >
                    <TextareaInput
                        id="run-notes"
                        v-model="form.notes"
                        placeholder="Batch details, issues, or who built it"
                    />
                </Field>
            </FormSection>

            <FormSection
                title="Parts to consume"
                description="Optional formula lines are not consumed. Costs use each item's current unit cost."
                :columns="1"
            >
                <div class="overflow-x-auto rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr class="label-mono text-muted-foreground">
                                <th class="px-4 py-2 font-medium">Item</th>
                                <th class="px-4 py-2 text-right font-medium">
                                    Required
                                </th>
                                <th class="px-4 py-2 text-right font-medium">
                                    Available
                                </th>
                                <th class="px-4 py-2 text-right font-medium">
                                    Cost
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="line in preview"
                                :key="line.inventory_item_id"
                            >
                                <td class="px-4 py-2">
                                    <p class="font-medium">
                                        {{ line.item_name }}
                                    </p>
                                    <p
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ line.item_code }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-2 text-right whitespace-nowrap tabular-nums"
                                >
                                    {{ formatNumber(line.required, 3) }}
                                    <span
                                        class="font-mono text-xs text-muted-foreground"
                                        >{{ line.unit_symbol }}</span
                                    >
                                </td>
                                <td
                                    class="px-4 py-2 text-right whitespace-nowrap tabular-nums"
                                >
                                    <Badge
                                        v-if="line.short"
                                        variant="destructive"
                                        >Short ·
                                        {{
                                            formatNumber(line.available, 3)
                                        }}</Badge
                                    >
                                    <span
                                        v-else
                                        class="text-muted-foreground"
                                        >{{
                                            formatNumber(line.available, 3)
                                        }}</span
                                    >
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums">
                                    ₱{{ formatNumber(line.cost) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t bg-muted/20">
                            <tr>
                                <td
                                    colspan="3"
                                    class="px-4 py-2 text-right label-mono text-muted-foreground"
                                >
                                    Total input cost
                                </td>
                                <td
                                    class="px-4 py-2 text-right font-bold tabular-nums"
                                >
                                    ₱{{ formatNumber(totalCost) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p v-if="hasShortfall" class="text-sm text-destructive">
                    Some parts are short at {{ order?.location }}. Receive stock
                    or lower the output quantity before completing.
                </p>
            </FormSection>

            <FormFooter
                submit-label="Complete run"
                :processing="form.processing || hasShortfall || output <= 0"
                @cancel="open = false"
            />
        </form>
    </FormDialog>
</template>
