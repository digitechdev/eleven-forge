<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Boxes,
    CircleSlash,
    ClipboardList,
    Coins,
    Hammer,
    Globe,
    Lightbulb,
    Lock,
    PackageOpen,
    Plus,
    Star,
    TriangleAlert,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import EmptyState from '@/components/common/EmptyState.vue';
import ProgressBar from '@/components/common/ProgressBar.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import Panel from '@/components/common/Panel.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import StatCard from '@/components/common/StatCard.vue';
import CountBars from '@/components/dashboard/CountBars.vue';
import DeferredPanel from '@/components/skeletons/DeferredPanel.vue';
import { Badge } from '@/components/ui/badge';
import { useFilters } from '@/composables/useFilters';
import { formatDate, formatNumber } from '@/lib/format';
import production from '@/routes/production';
import innovationRoutes from '@/routes/innovations';
import inventory from '@/routes/inventory';
import showcase from '@/routes/showcase';
import { dashboard as dashboardCrumb } from '@/routes';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([{ title: 'Dashboard', href: dashboardCrumb() }]);

type Count = { value: string; label: string; count: number };

type InnovationStats = {
    total: number;
    featured: number;
    public: number;
    internal: number;
    byStatus: Count[];
    byType: Count[];
};

type LowStockItem = {
    id: number;
    item_code: string;
    name: string;
    quantity_on_hand: number;
    minimum_stock: number;
    unit: string | null;
    url: string | null;
};

type RecentInnovation = {
    id: number;
    innovation_code: string;
    name: string;
    status: string;
    status_label: string;
    type_label: string;
    is_public: boolean;
    updated_at: string | null;
    url: string | null;
};

type StockStats = { value: number; low: number; out: number; url: string };

type ActiveBuildOrders = {
    count: number;
    orders: {
        id: number;
        order_number: string;
        innovation: string;
        status_label: string;
        planned: number;
        produced: number;
        url: string;
    }[];
};

type RecentRun = {
    id: number;
    run_number: string;
    innovation: string;
    output_quantity: number;
    unit_cost: number;
    completed_at: string;
    url: string;
};

type Movement = {
    id: number;
    entry_number: string;
    item: string;
    location: string;
    type_label: string;
    quantity: number;
    posted_at: string;
};

type Shortcut = {
    key: string;
    label: string;
    description: string;
    href: string;
};

/** Deferred props are undefined while loading and null when the user may not see them. */
const props = defineProps<{
    innovationStats: InnovationStats | null;
    stockStats: StockStats | null;
    locations: { value: number; label: string }[] | null;
    filters: { location: number | null };
    lowStockItems?: LowStockItem[] | null;
    recentInnovations?: RecentInnovation[] | null;
    activeBuildOrders?: ActiveBuildOrders | null;
    recentRuns?: RecentRun[] | null;
    recentMovements?: Movement[] | null;
    shortcuts: Shortcut[];
}>();

const { filters } = useFilters(
    { location: props.filters.location },
    () => dashboardCrumb().url,
);

const page = usePage();

const shortcutIcons: Record<string, Component> = {
    'create-innovation': Plus,
    'manage-items': Boxes,
    'post-stock': BookOpen,
    'build-orders': ClipboardList,
    'manage-users': Users,
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Overview"
            :title="`Welcome back, ${page.props.auth.user.name}!`"
            description="A snapshot of the innovation registry and inventory."
        >
            <template #actions>
                <SelectInput
                    v-if="locations"
                    v-model="filters.location"
                    class="w-48"
                    :options="locations"
                    placeholder="All locations"
                    aria-label="Filter stock and production by location"
                />
                <Link
                    :href="showcase.index()"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground"
                >
                    <Globe class="size-4" /> Public showcase
                </Link>
            </template>
        </PageHeader>

        <div
            v-if="shortcuts.length"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        >
            <Link
                v-for="shortcut in shortcuts"
                :key="shortcut.key"
                :href="shortcut.href"
                class="group flex hover-lift items-start gap-3 rounded-xl border border-border/70 bg-card p-4 shadow-soft"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary transition-all duration-300 ease-[var(--ease-spring)] group-hover:scale-110 group-hover:bg-primary group-hover:text-primary-foreground"
                >
                    <component
                        :is="shortcutIcons[shortcut.key] ?? ArrowRight"
                        class="size-4"
                    />
                </span>
                <span class="min-w-0 space-y-0.5">
                    <span class="flex items-center gap-1 text-sm font-semibold">
                        {{ shortcut.label }}
                        <ArrowRight
                            class="size-3.5 opacity-0 transition-all group-hover:translate-x-0.5 group-hover:opacity-100"
                        />
                    </span>
                    <span class="block text-xs text-muted-foreground">{{
                        shortcut.description
                    }}</span>
                </span>
            </Link>
        </div>

        <template v-if="innovationStats">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Innovations"
                    :value="innovationStats.total"
                    :icon="Lightbulb"
                />
                <StatCard
                    label="Public"
                    :value="innovationStats.public"
                    :icon="Globe"
                    tone="patina"
                />
                <StatCard
                    label="Internal"
                    :value="innovationStats.internal"
                    :icon="Lock"
                    tone="graphite"
                />
                <StatCard
                    label="Featured"
                    :value="innovationStats.featured"
                    :icon="Star"
                    tone="brass"
                />
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Panel title="By status" :icon="Lightbulb">
                    <CountBars :items="innovationStats.byStatus" />
                </Panel>
                <Panel title="By type" :icon="Lightbulb">
                    <CountBars :items="innovationStats.byType" />
                </Panel>
            </div>
        </template>

        <div
            v-if="stockStats || activeBuildOrders !== null"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <template v-if="stockStats">
                <Link :href="stockStats.url" class="block">
                    <StatCard
                        label="Stock value"
                        :value="formatNumber(stockStats.value)"
                        unit="PHP"
                        :icon="Coins"
                        tone="brass"
                    />
                </Link>
                <Link :href="stockStats.url" class="block">
                    <StatCard
                        label="Low stock"
                        :value="stockStats.low"
                        :icon="TriangleAlert"
                        tone="ember"
                    />
                </Link>
                <Link :href="stockStats.url" class="block">
                    <StatCard
                        label="Out of stock"
                        :value="stockStats.out"
                        :icon="CircleSlash"
                        tone="danger"
                    />
                </Link>
            </template>
            <Link
                v-if="activeBuildOrders"
                :href="production.buildOrders.index()"
                class="block"
            >
                <StatCard
                    label="Active builds"
                    :value="activeBuildOrders.count"
                    :icon="ClipboardList"
                    tone="steel"
                />
            </Link>
        </div>

        <div
            v-if="recentInnovations !== null || lowStockItems !== null"
            class="grid gap-6 lg:grid-cols-2"
        >
            <DeferredPanel
                v-if="recentInnovations !== null"
                data="recentInnovations"
                variant="list"
            >
                <Panel title="Recently updated" :icon="Lightbulb" flush>
                    <template #actions>
                        <Link
                            :href="innovationRoutes.index()"
                            class="text-xs font-medium text-primary hover:underline"
                            >All innovations</Link
                        >
                    </template>
                    <ul v-if="recentInnovations?.length" class="divide-y">
                        <li
                            v-for="innovation in recentInnovations"
                            :key="innovation.id"
                            class="flex items-center justify-between gap-3 px-5 py-3"
                        >
                            <div class="min-w-0">
                                <component
                                    :is="innovation.url ? Link : 'span'"
                                    :href="innovation.url ?? undefined"
                                    class="block truncate font-medium"
                                    :class="
                                        innovation.url ? 'hover:underline' : ''
                                    "
                                    >{{ innovation.name }}</component
                                >
                                <p
                                    class="truncate font-mono text-xs text-muted-foreground"
                                >
                                    {{ innovation.innovation_code }} ·
                                    {{ innovation.type_label }} ·
                                    {{ formatDate(innovation.updated_at) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <Badge
                                    v-if="innovation.is_public"
                                    variant="outline"
                                    class="rounded-sm"
                                    >Public</Badge
                                >
                                <Badge variant="secondary">{{
                                    innovation.status_label
                                }}</Badge>
                            </div>
                        </li>
                    </ul>
                    <EmptyState
                        v-else
                        :icon="Lightbulb"
                        title="No innovations yet"
                        description="Registered innovations will appear here."
                    />
                </Panel>
            </DeferredPanel>

            <DeferredPanel
                v-if="lowStockItems !== null"
                data="lowStockItems"
                variant="list"
            >
                <Panel title="Low stock" :icon="TriangleAlert" flush>
                    <template #actions>
                        <Link
                            :href="inventory.items.index()"
                            class="text-xs font-medium text-primary hover:underline"
                            >All items</Link
                        >
                    </template>
                    <ul v-if="lowStockItems?.length" class="divide-y">
                        <li
                            v-for="item in lowStockItems"
                            :key="item.id"
                            class="flex items-center justify-between gap-3 px-5 py-3"
                        >
                            <div class="min-w-0">
                                <component
                                    :is="item.url ? Link : 'span'"
                                    :href="item.url ?? undefined"
                                    class="block truncate font-medium"
                                    :class="item.url ? 'hover:underline' : ''"
                                    >{{ item.name }}</component
                                >
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ item.item_code }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right text-sm">
                                <span
                                    class="font-semibold text-destructive tabular-nums"
                                    >{{
                                        formatNumber(item.quantity_on_hand)
                                    }}</span
                                >
                                <span class="text-muted-foreground">
                                    /
                                    {{ formatNumber(item.minimum_stock) }}
                                    {{ item.unit }}</span
                                >
                            </div>
                        </li>
                    </ul>
                    <EmptyState
                        v-else
                        :icon="PackageOpen"
                        title="Stock levels look healthy"
                        description="No active item is at or below its minimum stock."
                    />
                </Panel>
            </DeferredPanel>
        </div>

        <div
            v-if="activeBuildOrders !== null || recentRuns !== null"
            class="grid gap-6 lg:grid-cols-2"
        >
            <DeferredPanel
                v-if="activeBuildOrders !== null"
                data="activeBuildOrders"
                variant="list"
            >
                <Panel title="Active builds" :icon="ClipboardList" flush>
                    <template #actions>
                        <Link
                            :href="production.buildOrders.index()"
                            class="text-xs font-medium text-primary hover:underline"
                            >All builds</Link
                        >
                    </template>
                    <ul
                        v-if="activeBuildOrders?.orders.length"
                        class="divide-y"
                    >
                        <li
                            v-for="order in activeBuildOrders.orders"
                            :key="order.id"
                            class="space-y-2 px-5 py-3"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <Link
                                        :href="order.url"
                                        class="block truncate font-medium hover:underline"
                                        >{{ order.innovation }}</Link
                                    >
                                    <p
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ order.order_number }}
                                    </p>
                                </div>
                                <Badge variant="secondary">{{
                                    order.status_label
                                }}</Badge>
                            </div>
                            <div class="flex items-center gap-3">
                                <ProgressBar
                                    :value="
                                        order.planned > 0
                                            ? (order.produced / order.planned) *
                                              100
                                            : 0
                                    "
                                    size="sm"
                                />
                                <span
                                    class="shrink-0 text-xs text-muted-foreground tabular-nums"
                                    >{{ formatNumber(order.produced) }} /
                                    {{ formatNumber(order.planned) }}</span
                                >
                            </div>
                        </li>
                    </ul>
                    <EmptyState
                        v-else
                        :icon="ClipboardList"
                        title="No active builds"
                        description="Planned and in-progress builds will appear here."
                    />
                </Panel>
            </DeferredPanel>

            <DeferredPanel
                v-if="recentRuns !== null"
                data="recentRuns"
                variant="list"
            >
                <Panel title="Recent production runs" :icon="Hammer" flush>
                    <template #actions>
                        <Link
                            :href="production.buildOrders.index()"
                            class="text-xs font-medium text-primary hover:underline"
                            >All builds</Link
                        >
                    </template>
                    <ul v-if="recentRuns?.length" class="divide-y">
                        <li
                            v-for="run in recentRuns"
                            :key="run.id"
                            class="flex items-center justify-between gap-3 px-5 py-3"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="run.url"
                                    class="block truncate font-medium hover:underline"
                                    >{{ run.innovation }}</Link
                                >
                                <p
                                    class="truncate font-mono text-xs text-muted-foreground"
                                >
                                    {{ run.run_number }} ·
                                    {{ formatDate(run.completed_at) }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right text-sm">
                                <span class="font-semibold tabular-nums">{{
                                    formatNumber(run.output_quantity)
                                }}</span>
                                <span class="text-muted-foreground">
                                    built ·
                                    {{ formatNumber(run.unit_cost) }}
                                    PHP/unit</span
                                >
                            </div>
                        </li>
                    </ul>
                    <EmptyState
                        v-else
                        :icon="Hammer"
                        title="No production runs yet"
                        description="Completed runs and their costs will appear here."
                    />
                </Panel>
            </DeferredPanel>
        </div>

        <DeferredPanel
            v-if="recentMovements !== null"
            data="recentMovements"
            variant="list"
        >
            <Panel title="Recent stock movements" :icon="BookOpen" flush>
                <template #actions>
                    <Link
                        :href="inventory.ledger.index()"
                        class="text-xs font-medium text-primary hover:underline"
                        >Stock movements</Link
                    >
                </template>
                <ul v-if="recentMovements?.length" class="divide-y">
                    <li
                        v-for="movement in recentMovements"
                        :key="movement.id"
                        class="flex items-center justify-between gap-3 px-5 py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium">
                                {{ movement.item }}
                            </p>
                            <p
                                class="truncate font-mono text-xs text-muted-foreground"
                            >
                                {{ movement.entry_number }} ·
                                {{ movement.location }} ·
                                {{ formatDate(movement.posted_at) }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge variant="outline">{{
                                movement.type_label
                            }}</Badge>
                            <span
                                :class="[
                                    'font-semibold tabular-nums',
                                    movement.quantity < 0
                                        ? 'text-destructive'
                                        : 'text-chart-5',
                                ]"
                                >{{ movement.quantity > 0 ? '+' : ''
                                }}{{ formatNumber(movement.quantity) }}</span
                            >
                        </div>
                    </li>
                </ul>
                <EmptyState
                    v-else
                    :icon="BookOpen"
                    title="No stock movements yet"
                    description="Receipts, issues, adjustments and production consumption will appear here."
                />
            </Panel>
        </DeferredPanel>
    </div>
</template>
