<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Factory, Plus } from '@lucide/vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Panel from '@/components/common/Panel.vue';
import ProgressBar from '@/components/common/ProgressBar.vue';
import type { ProductionSummary } from '@/components/innovations/types';
import { buildOrderStatusTone } from '@/components/production/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatNumber } from '@/lib/format';
import { show as buildOrdersShow } from '@/routes/production/build-orders';

/** The innovation's latest builds and production runs. */
defineProps<{
    production: ProductionSummary;
    canCreateBuildOrder: boolean;
}>();
</script>

<template>
    <Panel
        title="Production"
        :icon="Factory"
        description="Builds and completed runs for this innovation."
    >
        <template v-if="canCreateBuildOrder" #actions>
            <Button size="sm" as-child>
                <Link :href="production.newBuildOrderUrl"
                    ><Plus /> New build</Link
                >
            </Button>
        </template>

        <div
            v-if="production.buildOrders.length || production.runs.length"
            class="grid gap-6 text-sm md:grid-cols-2"
        >
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="label-mono text-muted-foreground">Builds</h3>
                    <Link
                        :href="production.buildOrdersUrl"
                        class="text-xs text-muted-foreground hover:text-primary hover:underline"
                        >View all</Link
                    >
                </div>
                <ul v-if="production.buildOrders.length" class="space-y-3">
                    <li
                        v-for="order in production.buildOrders"
                        :key="order.id"
                        class="space-y-1.5"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <Link
                                :href="buildOrdersShow(order.id).url"
                                class="font-mono text-xs font-medium hover:text-primary hover:underline"
                                >{{ order.order_number }}</Link
                            >
                            <Badge
                                variant="outline"
                                :class="buildOrderStatusTone[order.status]"
                                >{{ order.status_label }}</Badge
                            >
                        </div>
                        <ProgressBar :value="order.progress" size="sm" />
                        <p class="text-xs text-muted-foreground tabular-nums">
                            {{ formatNumber(order.produced_quantity, 3) }} of
                            {{ formatNumber(order.planned_quantity, 3) }} built
                            · v{{ order.formula_version }}
                        </p>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground">No builds yet.</p>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="label-mono text-muted-foreground">
                        Recent runs
                    </h3>
                </div>
                <ul v-if="production.runs.length" class="divide-y">
                    <li
                        v-for="run in production.runs"
                        :key="run.id"
                        class="flex items-center justify-between gap-2 py-2"
                    >
                        <div>
                            <Link
                                :href="run.url"
                                class="font-mono text-xs font-medium hover:text-primary hover:underline"
                                >{{ run.run_number }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(run.completed_at) }}
                            </p>
                        </div>
                        <div class="text-right tabular-nums">
                            <p>
                                {{ formatNumber(run.output_quantity, 3) }} built
                            </p>
                            <p class="text-xs text-muted-foreground">
                                ₱{{ formatNumber(run.unit_production_cost) }} /
                                unit
                            </p>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground">No runs yet.</p>
            </div>
        </div>
        <EmptyState
            v-else
            :icon="Factory"
            title="Not built yet"
            description="Builds appear here once production of this innovation is planned."
        />
    </Panel>
</template>
