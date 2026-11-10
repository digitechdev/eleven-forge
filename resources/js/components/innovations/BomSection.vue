<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Boxes, CircleCheck, Copy, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import Panel from '@/components/common/Panel.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import InputError from '@/components/InputError.vue';
import { formulaStatusTone } from '@/components/innovations/types';
import type {
    BomLine,
    DetailOptions,
    Formula,
} from '@/components/innovations/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatNumber } from '@/lib/format';
import formulaRoutes from '@/routes/innovations/formulas';
import bom from '@/routes/innovations/items';

/** The versioned bill of materials: a version picker plus the selected formula's lines. */
const props = defineProps<{
    innovationId: number;
    formulas: Formula[];
    selectedFormulaId: number | null;
    options: DetailOptions | null;
    canUpdate: boolean;
    canCreateFormula: boolean;
    hint?: string | null;
}>();

const page = usePage();
const formulaError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.formula,
);

const selectedId = ref<number | null>(props.selectedFormulaId);

watch(
    () => props.selectedFormulaId,
    (id) => (selectedId.value = id),
);

const formula = computed<Formula | null>(
    () =>
        props.formulas.find((f) => f.id === selectedId.value) ??
        props.formulas[props.formulas.length - 1] ??
        null,
);

const items = computed<BomLine[]>(() => formula.value?.items ?? []);

/** Lines can change on an editable formula, or when there is no formula yet (the first line starts version 1). */
const canEditLines = computed(() =>
    formula.value ? formula.value.can.update : props.canUpdate,
);

const versionOptions = computed(() =>
    [...props.formulas].reverse().map((f) => ({
        value: f.id,
        label: `Version ${f.version} · ${f.status_label}`,
    })),
);

const readOnlyReason = computed(() => {
    if (!formula.value || formula.value.can.update || !props.canUpdate) {
        return null;
    }

    if (formula.value.status === 'ARCHIVED') {
        return 'Archived versions are read-only. Start a new version to make changes.';
    }

    return 'Used by a build order, so its lines are locked. Start a new version to make changes.';
});

const activeFormula = computed(() =>
    props.formulas.find((f) => f.status === 'ACTIVE'),
);

type BomForm = {
    innovation_formula_id: number | null;
    inventory_item_id: number | null;
    unit_id: number | null;
    quantity_required: number | string;
    usage_description: string | null;
    specification_notes: string | null;
    is_optional: boolean;
    sort_order: number | string;
};

const blank = (): BomForm => ({
    innovation_formula_id: formula.value?.id ?? null,
    inventory_item_id: null,
    unit_id: null,
    quantity_required: 1,
    usage_description: null,
    specification_notes: null,
    is_optional: false,
    sort_order: items.value.length,
});

const dialogOpen = ref(false);
const editing = ref<BomLine | null>(null);
const itemSearch = ref('');
const form = useForm<BomForm>(blank());

const removing = ref<BomLine | null>(null);
const removeOpen = ref(false);
const removeProcessing = ref(false);

const formulaDialogOpen = ref(false);
const formulaForm = useForm<{
    yield_quantity: number | string;
    notes: string | null;
}>({ yield_quantity: 1, notes: null });

const activateOpen = ref(false);
const activateProcessing = ref(false);
const versionProcessing = ref(false);

/** Items not yet on the formula (plus the line being edited), narrowed by the search box. */
const itemOptions = computed(() => {
    const used = new Set(
        items.value
            .filter((line) => line.id !== editing.value?.id)
            .map((line) => line.inventory_item_id),
    );
    const term = itemSearch.value.trim().toLowerCase();

    return (props.options?.inventoryItems ?? [])
        .filter((item) => !used.has(item.value))
        .filter(
            (item) =>
                !term ||
                item.value === form.inventory_item_id ||
                item.label.toLowerCase().includes(term) ||
                item.item_code.toLowerCase().includes(term),
        )
        .map((item) => ({
            value: item.value,
            label: `${item.item_code} — ${item.label}`,
        }));
});

function money(value: number | null): string {
    return value === null ? '—' : `₱${formatNumber(value)}`;
}

function selectVersion(value: string | number | null | undefined): void {
    selectedId.value =
        value === null || value === undefined ? null : Number(value);
}

function selectItem(value: string | number | null | undefined): void {
    const id = value === null || value === undefined ? null : Number(value);
    form.inventory_item_id = id;

    const item = props.options?.inventoryItems.find((i) => i.value === id);

    if (item) {
        form.unit_id = item.default_unit_id;
    }
}

function openCreate(): void {
    editing.value = null;
    itemSearch.value = '';
    form.defaults(blank());
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(line: BomLine): void {
    editing.value = line;
    itemSearch.value = '';
    form.defaults({
        innovation_formula_id: formula.value?.id ?? null,
        inventory_item_id: line.inventory_item_id,
        unit_id: line.unit_id,
        quantity_required: line.quantity_required,
        usage_description: line.usage_description,
        specification_notes: line.specification_notes,
        is_optional: line.is_optional,
        sort_order: line.sort_order,
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
        form.put(
            bom.update([props.innovationId, editing.value.id]).url,
            options,
        );
    } else {
        form.post(bom.store(props.innovationId).url, options);
    }
}

function confirmRemove(line: BomLine): void {
    removing.value = line;
    removeOpen.value = true;
}

function remove(): void {
    if (!removing.value) {
        return;
    }

    router.delete(bom.destroy([props.innovationId, removing.value.id]).url, {
        preserveScroll: true,
        onStart: () => (removeProcessing.value = true),
        onFinish: () => {
            removeProcessing.value = false;
            removeOpen.value = false;
        },
    });
}

function newVersion(): void {
    router.post(
        formulaRoutes.store(props.innovationId).url,
        { source_formula_id: formula.value?.id ?? null },
        {
            preserveScroll: true,
            onStart: () => (versionProcessing.value = true),
            onFinish: () => (versionProcessing.value = false),
        },
    );
}

function openFormulaEdit(): void {
    if (!formula.value) {
        return;
    }

    formulaForm.defaults({
        yield_quantity: formula.value.yield_quantity,
        notes: formula.value.notes,
    });
    formulaForm.reset();
    formulaForm.clearErrors();
    formulaDialogOpen.value = true;
}

function submitFormula(): void {
    if (!formula.value) {
        return;
    }

    formulaForm.put(
        formulaRoutes.update([props.innovationId, formula.value.id]).url,
        {
            preserveScroll: true,
            onSuccess: () => (formulaDialogOpen.value = false),
        },
    );
}

function activate(): void {
    if (!formula.value) {
        return;
    }

    router.patch(
        formulaRoutes.activate([props.innovationId, formula.value.id]).url,
        {},
        {
            preserveScroll: true,
            onStart: () => (activateProcessing.value = true),
            onFinish: () => {
                activateProcessing.value = false;
                activateOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <Panel
        title="Bill of materials"
        :icon="Boxes"
        :description="hint ?? 'Physical inventory items this innovation uses.'"
        flush
    >
        <template v-if="formulas.length || canEditLines" #actions>
            <SelectInput
                v-if="formulas.length > 1"
                :model-value="formula?.id"
                class="w-48"
                :options="versionOptions"
                aria-label="Formula version"
                @update:model-value="selectVersion"
            />
            <Button
                v-if="formula?.can.activate"
                size="sm"
                variant="outline"
                @click="activateOpen = true"
                ><CircleCheck /> Activate</Button
            >
            <Button
                v-if="formula && canCreateFormula"
                size="sm"
                variant="outline"
                :disabled="versionProcessing"
                @click="newVersion"
                ><Copy /> New version</Button
            >
            <Button
                v-if="formula?.can.update"
                size="sm"
                variant="outline"
                @click="openFormulaEdit"
                ><Pencil /> Edit formula</Button
            >
            <Button v-if="canEditLines" size="sm" @click="openCreate"
                ><Plus /> Add item</Button
            >
        </template>

        <div
            v-if="formula"
            class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b px-5 py-3 text-sm"
        >
            <div class="flex items-center gap-2">
                <span class="font-medium">Version {{ formula.version }}</span>
                <Badge
                    variant="outline"
                    :class="formulaStatusTone[formula.status]"
                    >{{ formula.status_label }}</Badge
                >
            </div>
            <div>
                <span class="label-mono text-muted-foreground">Yield </span>
                <span class="tabular-nums">{{
                    formatNumber(formula.yield_quantity, 3)
                }}</span>
            </div>
            <div>
                <span class="label-mono text-muted-foreground"
                    >Est. unit cost
                </span>
                <span class="font-medium tabular-nums">{{
                    money(formula.estimated_unit_cost)
                }}</span>
            </div>
            <div v-if="formula.activated_at">
                <span class="label-mono text-muted-foreground">Activated </span>
                <span>{{ formatDate(formula.activated_at) }}</span>
            </div>
            <p v-if="formula.notes" class="text-muted-foreground">
                {{ formula.notes }}
            </p>
            <p
                v-if="readOnlyReason"
                class="basis-full text-xs text-muted-foreground"
            >
                {{ readOnlyReason }}
            </p>
            <InputError class="basis-full" :message="formulaError" />
        </div>

        <div v-if="items.length" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted/40 text-left">
                    <tr class="label-mono text-muted-foreground">
                        <th class="px-5 py-2.5 font-medium">Item</th>
                        <th class="px-5 py-2.5 text-right font-medium">Qty</th>
                        <th class="px-5 py-2.5 font-medium">Usage</th>
                        <th class="px-5 py-2.5 text-right font-medium">
                            Unit cost
                        </th>
                        <th class="px-5 py-2.5 text-right font-medium">
                            Est. cost
                        </th>
                        <th v-if="canEditLines" class="px-5 py-2.5">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="line in items"
                        :key="line.id"
                        class="align-top hover:bg-accent/40"
                    >
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <Link
                                    v-if="line.item_url"
                                    :href="line.item_url"
                                    class="font-medium hover:text-primary hover:underline"
                                    >{{ line.item_name }}</Link
                                >
                                <span v-else class="font-medium">{{
                                    line.item_name ?? 'Unknown item'
                                }}</span>
                                <Badge v-if="line.is_optional" variant="outline"
                                    >Optional</Badge
                                >
                            </div>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ line.item_code }}
                            </p>
                        </td>
                        <td
                            class="px-5 py-3 text-right whitespace-nowrap tabular-nums"
                        >
                            {{ formatNumber(line.quantity_required, 3) }}
                            <span
                                class="font-mono text-xs text-muted-foreground"
                                >{{ line.unit_symbol }}</span
                            >
                        </td>
                        <td class="max-w-64 px-5 py-3 text-muted-foreground">
                            <p v-if="line.usage_description">
                                {{ line.usage_description }}
                            </p>
                            <p
                                v-if="line.specification_notes"
                                class="text-xs italic"
                            >
                                {{ line.specification_notes }}
                            </p>
                            <span
                                v-if="
                                    !line.usage_description &&
                                    !line.specification_notes
                                "
                                >—</span
                            >
                        </td>
                        <td
                            class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                        >
                            {{ money(line.unit_cost) }}
                        </td>
                        <td
                            class="px-5 py-3 text-right font-medium tabular-nums"
                        >
                            {{ money(line.estimated_cost) }}
                        </td>
                        <td v-if="canEditLines" class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="`Edit ${line.item_name}`"
                                    @click="openEdit(line)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="`Remove ${line.item_name}`"
                                    @click="confirmRemove(line)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="border-t bg-muted/20">
                    <tr>
                        <td
                            colspan="4"
                            class="px-5 py-3 text-right label-mono text-muted-foreground"
                        >
                            Estimated total<span
                                v-if="formula?.has_unpriced_lines"
                            >
                                (excludes unpriced items)</span
                            >
                        </td>
                        <td class="px-5 py-3 text-right font-bold tabular-nums">
                            {{ money(formula?.total ?? 0) }}
                        </td>
                        <td v-if="canEditLines" />
                    </tr>
                </tfoot>
            </table>
        </div>
        <EmptyState
            v-else
            :icon="Boxes"
            title="No items yet"
            :description="
                formula
                    ? 'This version has no lines yet.'
                    : 'Add the physical parts and materials this innovation needs.'
            "
        />
    </Panel>

    <FormDialog
        v-if="canEditLines && options"
        v-model:open="dialogOpen"
        :title="editing ? `Edit ${editing.item_name}` : 'Add BOM item'"
        :icon="Boxes"
    >
        <form @submit.prevent="submit">
            <FormSection>
                <Field
                    label="Inventory item"
                    for="bom-item"
                    :error="form.errors.inventory_item_id"
                    required
                    class="sm:col-span-2"
                >
                    <Input
                        v-model="itemSearch"
                        placeholder="Filter by name or code"
                        aria-label="Filter inventory items"
                    />
                    <SelectInput
                        id="bom-item"
                        :model-value="form.inventory_item_id"
                        :options="itemOptions"
                        placeholder="Choose an item"
                        @update:model-value="selectItem"
                    />
                </Field>
                <Field
                    label="Quantity required"
                    for="bom-quantity"
                    :error="form.errors.quantity_required"
                    required
                >
                    <Input
                        id="bom-quantity"
                        v-model="form.quantity_required"
                        type="number"
                        min="0"
                        step="0.001"
                    />
                </Field>
                <Field
                    label="Unit"
                    for="bom-unit"
                    :error="form.errors.unit_id"
                    hint="Defaults to the item's unit."
                >
                    <SelectInput
                        id="bom-unit"
                        v-model="form.unit_id"
                        :options="options.units"
                        placeholder="Item default"
                    />
                </Field>
                <Field
                    label="Usage"
                    for="bom-usage"
                    :error="form.errors.usage_description"
                    class="sm:col-span-2"
                >
                    <TextareaInput
                        id="bom-usage"
                        v-model="form.usage_description"
                        placeholder="How or where the item is used"
                    />
                </Field>
                <Field
                    label="Specification notes"
                    for="bom-spec"
                    :error="form.errors.specification_notes"
                    class="sm:col-span-2"
                >
                    <TextareaInput
                        id="bom-spec"
                        v-model="form.specification_notes"
                        placeholder="Innovation-specific requirements"
                    />
                </Field>
                <Field
                    label="Sort order"
                    for="bom-sort"
                    :error="form.errors.sort_order"
                >
                    <Input
                        id="bom-sort"
                        v-model="form.sort_order"
                        type="number"
                        min="0"
                    />
                </Field>
                <Label
                    class="flex items-center gap-2.5 self-end pb-2 font-normal"
                >
                    <Checkbox v-model="form.is_optional" />
                    Optional item
                </Label>
            </FormSection>
            <FormFooter
                :submit-label="editing ? 'Save line' : 'Add item'"
                :processing="form.processing"
                @cancel="dialogOpen = false"
            />
        </form>
    </FormDialog>

    <ConfirmDialog
        v-model:open="removeOpen"
        :title="`Remove ${removing?.item_name ?? 'item'}?`"
        description="The item stays in inventory; only this bill of materials line is removed."
        confirm-label="Remove"
        :processing="removeProcessing"
        @confirm="remove"
    />

    <FormDialog
        v-if="formula?.can.update"
        v-model:open="formulaDialogOpen"
        :title="`Edit version ${formula.version}`"
        :icon="Boxes"
        size="md"
    >
        <form @submit.prevent="submitFormula">
            <FormSection>
                <Field
                    label="Yield quantity"
                    for="formula-yield"
                    :error="formulaForm.errors.yield_quantity"
                    hint="Units built from one set of these lines."
                    required
                >
                    <Input
                        id="formula-yield"
                        v-model="formulaForm.yield_quantity"
                        type="number"
                        min="0"
                        step="0.001"
                    />
                </Field>
                <Field
                    label="Notes"
                    for="formula-notes"
                    :error="formulaForm.errors.notes"
                    class="sm:col-span-2"
                >
                    <TextareaInput
                        id="formula-notes"
                        v-model="formulaForm.notes"
                        placeholder="What changed in this version"
                    />
                </Field>
            </FormSection>
            <FormFooter
                submit-label="Save formula"
                :processing="formulaForm.processing"
                @cancel="formulaDialogOpen = false"
            />
        </form>
    </FormDialog>

    <ConfirmDialog
        v-model:open="activateOpen"
        :title="`Activate version ${formula?.version ?? ''}?`"
        :description="
            activeFormula
                ? `New build orders will use this version. Version ${activeFormula.version} is archived and becomes read-only.`
                : 'New build orders will use this version.'
        "
        confirm-label="Activate"
        :destructive="false"
        :processing="activateProcessing"
        @confirm="activate"
    />
</template>
