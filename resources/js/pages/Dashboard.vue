<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Boxes,
    Globe,
    Lightbulb,
    Lock,
    PackageOpen,
    Plus,
    Ruler,
    Star,
    TriangleAlert,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Panel from '@/components/common/Panel.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import StatCard from '@/components/common/StatCard.vue';
import CountBars from '@/components/dashboard/CountBars.vue';
import DeferredPanel from '@/components/skeletons/DeferredPanel.vue';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatNumber } from '@/lib/format';
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

type Shortcut = {
    key: string;
    label: string;
    description: string;
    href: string;
};

/** Deferred props are undefined while loading and null when the user may not see them. */
defineProps<{
    innovationStats: InnovationStats | null;
    lowStockItems?: LowStockItem[] | null;
    recentInnovations?: RecentInnovation[] | null;
    shortcuts: Shortcut[];
}>();

const page = usePage();

const shortcutIcons: Record<string, Component> = {
    'create-innovation': Plus,
    'manage-items': Boxes,
    'manage-units': Ruler,
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
    </div>
</template>
