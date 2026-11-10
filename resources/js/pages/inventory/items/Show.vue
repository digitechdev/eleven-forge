<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CircleAlert,
    FileText,
    Lightbulb,
    Package,
    Warehouse,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/common/EmptyState.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Panel from '@/components/common/Panel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatNumber } from '@/lib/format';
import items from '@/routes/inventory/items';
import {
    index as itemsIndexCrumb,
    show as itemsShowCrumb,
} from '@/routes/inventory/items';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

type Item = {
    id: number;
    item_code: string;
    name: string;
    item_type_label: string;
    category_path: string;
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
    created_by: string | null;
    created_at: string | null;
    updated_at: string | null;
};

type Usage = {
    id: number;
    innovation_code: string;
    innovation_name: string;
    innovation_url: string | null;
    quantity_required: number;
    unit_symbol: string;
    is_optional: boolean;
    usage_description: string | null;
};

const props = defineProps<{
    item: Item;
    usages: Usage[];
}>();

useBreadcrumbs(() => [
    { title: 'Inventory', href: itemsIndexCrumb() },
    { title: 'Items', href: itemsIndexCrumb() },
    { title: props.item.name, href: itemsShowCrumb(props.item.id) },
]);

const details = computed(() => [
    { label: 'Item code', value: props.item.item_code, mono: true },
    { label: 'Type', value: props.item.item_type_label },
    { label: 'Category', value: props.item.category_path },
    { label: 'Default unit', value: props.item.unit_symbol, mono: true },
    { label: 'Brand', value: props.item.brand },
    { label: 'Model', value: props.item.model },
    { label: 'Manufacturer', value: props.item.manufacturer },
    { label: 'Part number', value: props.item.part_number, mono: true },
    { label: 'Created by', value: props.item.created_by },
    { label: 'Created', value: formatDateTime(props.item.created_at) },
    { label: 'Last updated', value: formatDateTime(props.item.updated_at) },
]);

const stockValue = computed(() =>
    props.item.unit_cost === null
        ? null
        : props.item.quantity_on_hand * props.item.unit_cost,
);
</script>

<template>
    <Head :title="item.name" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            :eyebrow="`Inventory · ${item.item_code}`"
            :title="item.name"
            :description="item.description"
        >
            <template #meta>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        :variant="
                            item.status === 'ACTIVE' ? 'secondary' : 'outline'
                        "
                        >{{ item.status_label }}</Badge
                    >
                    <Badge v-if="item.is_low_stock" variant="destructive">
                        <CircleAlert /> Low stock
                    </Badge>
                </div>
            </template>
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="items.index()"><ArrowLeft /> All items</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <Panel title="Details" :icon="Package" class="lg:col-span-2">
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div v-for="detail in details" :key="detail.label">
                        <dt class="label-mono text-muted-foreground">
                            {{ detail.label }}
                        </dt>
                        <dd
                            :class="[
                                'mt-1 text-sm',
                                detail.mono ? 'font-mono' : '',
                            ]"
                        >
                            {{ detail.value || '—' }}
                        </dd>
                    </div>
                </dl>
            </Panel>

            <Panel title="Stock" :icon="Warehouse">
                <dl class="space-y-4">
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Quantity on hand
                        </dt>
                        <dd
                            :class="[
                                'mt-1 font-display text-2xl font-bold tabular-nums',
                                item.is_low_stock ? 'text-destructive' : '',
                            ]"
                        >
                            {{ formatNumber(item.quantity_on_hand) }}
                            <span
                                class="font-mono text-xs font-normal text-muted-foreground"
                                >{{ item.unit_symbol }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Minimum stock
                        </dt>
                        <dd class="mt-1 text-sm tabular-nums">
                            {{
                                item.minimum_stock > 0
                                    ? `${formatNumber(item.minimum_stock)} ${item.unit_symbol}`
                                    : 'Not tracked'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Unit cost
                        </dt>
                        <dd class="mt-1 text-sm tabular-nums">
                            {{
                                item.unit_cost === null
                                    ? '—'
                                    : `PHP ${formatNumber(item.unit_cost)}`
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Stock value
                        </dt>
                        <dd class="mt-1 text-sm tabular-nums">
                            {{
                                stockValue === null
                                    ? '—'
                                    : `PHP ${formatNumber(stockValue)}`
                            }}
                        </dd>
                    </div>
                </dl>
            </Panel>
        </div>

        <Panel v-if="item.specification" title="Specification" :icon="FileText">
            <p class="text-sm whitespace-pre-line">{{ item.specification }}</p>
        </Panel>

        <Panel
            title="Used in innovations"
            :icon="Lightbulb"
            description="Bills of materials that include this item."
            flush
        >
            <div v-if="usages.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Innovation</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Quantity
                            </th>
                            <th class="px-5 py-2.5 font-medium">Usage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="usage in usages"
                            :key="usage.id"
                            class="hover:bg-accent/40"
                        >
                            <td class="px-5 py-3">
                                <span
                                    class="mr-2 font-mono text-xs text-muted-foreground"
                                    >{{ usage.innovation_code }}</span
                                >
                                <Link
                                    v-if="usage.innovation_url"
                                    :href="usage.innovation_url"
                                    class="font-medium hover:text-primary hover:underline"
                                    >{{ usage.innovation_name }}</Link
                                >
                                <span v-else class="font-medium">{{
                                    usage.innovation_name
                                }}</span>
                                <Badge
                                    v-if="usage.is_optional"
                                    variant="outline"
                                    class="ml-2"
                                    >Optional</Badge
                                >
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                {{ formatNumber(usage.quantity_required) }}
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                    >{{ usage.unit_symbol }}</span
                                >
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ usage.usage_description || '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="Lightbulb"
                title="Not used yet"
                description="No innovation bill of materials includes this item."
            />
        </Panel>
    </div>
</template>
