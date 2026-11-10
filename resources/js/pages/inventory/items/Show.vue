<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowLeftRight,
    CircleAlert,
    FileText,
    History,
    Image as ImageIcon,
    Lightbulb,
    MapPin,
    Package,
    Plus,
    ScrollText,
    Warehouse,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/common/EmptyState.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Panel from '@/components/common/Panel.vue';
import ItemMediaSection from '@/components/inventory/ItemMediaSection.vue';
import type { ItemMediaEntry } from '@/components/inventory/ItemMediaSection.vue';
import PostTransactionDialog from '@/components/stock/PostTransactionDialog.vue';
import type { PostingOptions } from '@/components/stock/PostTransactionDialog.vue';
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
    reorder_level: number;
    unit_cost: number | null;
    status: string;
    status_label: string;
    on_hand: number;
    available: number;
    stock_status: string;
    stock_status_label: string;
    stock_value: number;
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
    formula_label: string;
};

type Balance = {
    id: number;
    location_id: number;
    location_code: string;
    location_name: string;
    location_is_active: boolean;
    on_hand: number;
    reserved: number;
    available: number;
    status: string;
    status_label: string;
    ledger_url: string;
};

type Tab = 'overview' | 'stock' | 'movements' | 'media' | 'cost-history';

type LedgerEntry = {
    id: number;
    entry_number: string;
    posted_at: string;
    type: string;
    type_label: string;
    location_name: string;
    quantity_in: number;
    quantity_out: number;
    balance_after: number;
    reference_number: string | null;
};

type PriceChange = {
    id: number;
    effective_at: string;
    previous_unit_cost: number | null;
    new_unit_cost: number | null;
    changed_by: string | null;
    reason: string | null;
};

const props = defineProps<{
    tab: Tab;
    item: Item;
    balances: Balance[];
    movements: LedgerEntry[];
    movementsLimit: number;
    priceHistory: PriceChange[];
    media: ItemMediaEntry[];
    mediaLimit: number;
    ledgerUrl: string | null;
    posting: PostingOptions | null;
    can: { update: boolean; post: boolean };
    usages: Usage[];
}>();

const tabs: { value: Tab; label: string; icon: typeof Package }[] = [
    { value: 'overview', label: 'Overview', icon: Package },
    { value: 'stock', label: 'Stock', icon: MapPin },
    { value: 'movements', label: 'Movements', icon: ScrollText },
    { value: 'media', label: 'Media', icon: ImageIcon },
    { value: 'cost-history', label: 'Cost history', icon: History },
];

const stockStatusVariant: Record<
    string,
    'secondary' | 'outline' | 'destructive'
> = {
    IN_STOCK: 'secondary',
    LOW_STOCK: 'outline',
    OUT_OF_STOCK: 'destructive',
};

const postingOpen = ref(false);
const postingLocation = ref<number | null>(null);

function openPosting(balance: Balance | null = null): void {
    postingLocation.value =
        balance && balance.location_is_active ? balance.location_id : null;
    postingOpen.value = true;
}

function cost(value: number | null): string {
    return value === null ? '—' : `PHP ${formatNumber(value)}`;
}

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
                    <Badge
                        v-if="item.stock_status !== 'IN_STOCK'"
                        :variant="
                            stockStatusVariant[item.stock_status] ?? 'outline'
                        "
                    >
                        <CircleAlert /> {{ item.stock_status_label }}
                    </Badge>
                </div>
            </template>
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="items.index()"><ArrowLeft /> All items</Link>
                </Button>
            </template>
        </PageHeader>

        <nav
            class="flex flex-wrap gap-1 border-b border-border/60 pb-3"
            aria-label="Item sections"
        >
            <Button
                v-for="entry in tabs"
                :key="entry.value"
                size="sm"
                :variant="tab === entry.value ? 'secondary' : 'ghost'"
                as-child
            >
                <Link
                    :href="items.show(item.id, { query: { tab: entry.value } })"
                    :aria-current="tab === entry.value ? 'page' : undefined"
                    preserve-scroll
                    preserve-state
                    replace
                >
                    <component :is="entry.icon" /> {{ entry.label }}
                </Link>
            </Button>
        </nav>

        <template v-if="tab === 'overview'">
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
                                    item.stock_status === 'IN_STOCK'
                                        ? ''
                                        : 'text-destructive',
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
                                Available
                            </dt>
                            <dd class="mt-1 text-sm tabular-nums">
                                {{ formatNumber(item.available) }}
                                {{ item.unit_symbol }}
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
                                Reorder level
                            </dt>
                            <dd class="mt-1 text-sm tabular-nums">
                                {{
                                    item.reorder_level > 0
                                        ? `${formatNumber(item.reorder_level)} ${item.unit_symbol}`
                                        : 'Not set'
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
                                    item.unit_cost === null
                                        ? '—'
                                        : `PHP ${formatNumber(item.stock_value)}`
                                }}
                            </dd>
                        </div>
                    </dl>
                </Panel>
            </div>

            <Panel
                v-if="item.specification"
                title="Specification"
                :icon="FileText"
            >
                <p class="text-sm whitespace-pre-line">
                    {{ item.specification }}
                </p>
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
                                <th class="px-5 py-2.5 font-medium">
                                    Innovation
                                </th>
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
                                    <Badge variant="secondary" class="ml-2">{{
                                        usage.formula_label
                                    }}</Badge>
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
        </template>

        <Panel
            v-else-if="tab === 'stock'"
            title="Stock by location"
            :icon="MapPin"
            description="On hand, reserved, and available quantity at each location."
            flush
        >
            <template v-if="can.post && posting" #actions>
                <Button size="sm" @click="openPosting()"
                    ><Plus /> Post transaction</Button
                >
            </template>
            <div v-if="balances.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Location</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                On hand
                            </th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Reserved
                            </th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Available
                            </th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="balance in balances"
                            :key="balance.id"
                            class="hover:bg-accent/40"
                        >
                            <td class="px-5 py-3">
                                <span
                                    class="mr-2 font-mono text-xs text-muted-foreground"
                                    >{{ balance.location_code }}</span
                                >
                                <span class="font-medium">{{
                                    balance.location_name
                                }}</span>
                                <Badge
                                    v-if="!balance.location_is_active"
                                    variant="outline"
                                    class="ml-2"
                                    >Inactive</Badge
                                >
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                {{ formatNumber(balance.on_hand) }}
                            </td>
                            <td
                                class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                            >
                                {{ formatNumber(balance.reserved) }}
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                {{ formatNumber(balance.available) }}
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                    >{{ item.unit_symbol }}</span
                                >
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    :variant="
                                        stockStatusVariant[balance.status] ??
                                        'outline'
                                    "
                                >
                                    <CircleAlert
                                        v-if="balance.status !== 'IN_STOCK'"
                                    />
                                    {{ balance.status_label }}
                                </Badge>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        v-if="can.post && posting"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Post a transaction at ${balance.location_name}`"
                                        @click="openPosting(balance)"
                                    >
                                        <ArrowLeftRight />
                                    </Button>
                                    <Button
                                        as-child
                                        size="icon"
                                        variant="ghost"
                                    >
                                        <Link
                                            :href="balance.ledger_url"
                                            :aria-label="`Movements at ${balance.location_name}`"
                                        >
                                            <History />
                                        </Link>
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
                title="No stock yet"
                description="Stock appears here once a transaction is posted for this item."
            />
        </Panel>

        <Panel
            v-else-if="tab === 'movements'"
            title="Stock movements"
            :icon="ScrollText"
            :description="`The ${movementsLimit} latest ledger entries for this item.`"
            flush
        >
            <template v-if="ledgerUrl" #actions>
                <Button size="sm" variant="outline" as-child>
                    <Link :href="ledgerUrl">Open in Stock movements</Link>
                </Button>
            </template>
            <div v-if="movements.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Date</th>
                            <th class="px-5 py-2.5 font-medium">Type</th>
                            <th class="px-5 py-2.5 font-medium">Location</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                In
                            </th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Out
                            </th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Balance
                            </th>
                            <th class="px-5 py-2.5 font-medium">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="entry in movements"
                            :key="entry.id"
                            class="hover:bg-accent/40"
                        >
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDateTime(entry.posted_at) }}
                            </td>
                            <td class="px-5 py-3">
                                <Badge variant="outline">{{
                                    entry.type_label
                                }}</Badge>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ entry.location_name }}
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                {{
                                    entry.quantity_in > 0
                                        ? formatNumber(entry.quantity_in)
                                        : '—'
                                }}
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                {{
                                    entry.quantity_out > 0
                                        ? formatNumber(entry.quantity_out)
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-5 py-3 text-right font-medium tabular-nums"
                            >
                                {{ formatNumber(entry.balance_after) }}
                            </td>
                            <td class="px-5 py-3 font-mono text-xs">
                                {{ entry.reference_number || '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="ScrollText"
                title="No movements yet"
                description="Opening balances, receipts, and issues for this item appear here."
            />
        </Panel>

        <ItemMediaSection
            v-else-if="tab === 'media'"
            :item-id="item.id"
            :media="media"
            :limit="mediaLimit"
            :can-update="can.update"
        />

        <Panel
            v-else-if="tab === 'cost-history'"
            title="Cost history"
            :icon="History"
            description="Every change to the reference unit cost, newest first. Cost changes never affect quantities."
            flush
        >
            <div v-if="priceHistory.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Date</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Unit cost
                            </th>
                            <th class="px-5 py-2.5 font-medium">Changed by</th>
                            <th class="px-5 py-2.5 font-medium">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="change in priceHistory"
                            :key="change.id"
                            class="hover:bg-accent/40"
                        >
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDateTime(change.effective_at) }}
                            </td>
                            <td
                                class="px-5 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                <span class="text-muted-foreground">{{
                                    cost(change.previous_unit_cost)
                                }}</span>
                                →
                                <span class="font-medium">{{
                                    cost(change.new_unit_cost)
                                }}</span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ change.changed_by || '—' }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ change.reason || '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="History"
                title="No cost changes"
                description="Changes to this item's unit cost are recorded here."
            />
        </Panel>

        <PostTransactionDialog
            v-if="posting"
            v-model:open="postingOpen"
            :options="posting"
            :item-id="item.id"
            :location-id="postingLocation"
        />
    </div>
</template>
