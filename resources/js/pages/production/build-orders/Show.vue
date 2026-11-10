<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Ban,
    Boxes,
    CalendarCheck,
    ExternalLink,
    Factory,
    Hammer,
    Play,
} from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Panel from '@/components/common/Panel.vue';
import ProgressBar from '@/components/common/ProgressBar.vue';
import CompleteRunDialog from '@/components/production/CompleteRunDialog.vue';
import { buildOrderStatusTone } from '@/components/production/types';
import type { BuildOrderRow, BuildRunRow } from '@/components/production/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { formatDate, formatDateTime, formatNumber } from '@/lib/format';
import buildOrders from '@/routes/production/build-orders';
import {
    index as buildOrdersIndexCrumb,
    show as buildOrdersShowCrumb,
} from '@/routes/production/build-orders';

const props = defineProps<{
    order: BuildOrderRow;
    runs: BuildRunRow[];
}>();

useBreadcrumbs(() => [
    { title: 'Production', href: buildOrdersIndexCrumb() },
    { title: 'Builds', href: buildOrdersIndexCrumb() },
    {
        title: props.order.order_number,
        href: buildOrdersShowCrumb(props.order.id),
    },
]);

const completeOpen = ref(false);
const cancelOpen = ref(false);
const cancelProcessing = ref(false);

function transition(action: 'plan' | 'start'): void {
    router.patch(
        buildOrders[action](props.order.id).url,
        {},
        { preserveScroll: true },
    );
}

function cancel(): void {
    router.patch(
        buildOrders.cancel(props.order.id).url,
        {},
        {
            preserveScroll: true,
            onStart: () => (cancelProcessing.value = true),
            onFinish: () => {
                cancelProcessing.value = false;
                cancelOpen.value = false;
            },
        },
    );
}

function money(value: number): string {
    return `₱${formatNumber(value)}`;
}
</script>

<template>
    <Head :title="order.order_number" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
        <Link
            :href="buildOrders.index().url"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-primary"
        >
            <ArrowLeft class="size-4" /> All builds
        </Link>

        <PageHeader
            eyebrow="Build"
            :title="order.order_number"
            :description="`${order.innovation.name} · formula v${order.formula_version}`"
        >
            <template #meta>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="outline"
                        :class="buildOrderStatusTone[order.status]"
                        >{{ order.status_label }}</Badge
                    >
                    <Badge v-if="order.location" variant="outline">{{
                        order.location
                    }}</Badge>
                </div>
            </template>
            <template
                v-if="
                    order.can.complete ||
                    order.can.plan ||
                    order.can.start ||
                    order.can.cancel
                "
                #actions
            >
                <Button v-if="order.can.plan" @click="transition('plan')"
                    ><CalendarCheck /> Plan</Button
                >
                <Button v-if="order.can.start" @click="transition('start')"
                    ><Play /> Start</Button
                >
                <Button v-if="order.can.complete" @click="completeOpen = true"
                    ><Hammer /> Complete production run</Button
                >
                <Button
                    v-if="order.can.cancel"
                    variant="outline"
                    @click="cancelOpen = true"
                    ><Ban /> Cancel</Button
                >
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Panel title="Progress" :icon="Factory">
                    <p class="mb-2 text-sm tabular-nums">
                        {{ formatNumber(order.produced_quantity, 3) }}
                        <span class="text-muted-foreground"
                            >of {{ formatNumber(order.planned_quantity, 3) }}
                            built ·
                            {{ formatNumber(order.remaining_quantity, 3) }}
                            remaining</span
                        >
                    </p>
                    <ProgressBar :value="order.progress" />
                </Panel>

                <Panel
                    title="Production runs"
                    :icon="Hammer"
                    description="Runs completed against this build, with the stock movements each one posted."
                    flush
                >
                    <div v-if="runs.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/40 text-left">
                                <tr class="label-mono text-muted-foreground">
                                    <th class="px-5 py-2.5 font-medium">Run</th>
                                    <th
                                        class="px-5 py-2.5 text-right font-medium"
                                    >
                                        Output
                                    </th>
                                    <th
                                        class="px-5 py-2.5 text-right font-medium"
                                    >
                                        Total cost
                                    </th>
                                    <th
                                        class="px-5 py-2.5 text-right font-medium"
                                    >
                                        Unit cost
                                    </th>
                                    <th class="px-5 py-2.5 font-medium">
                                        Completed
                                    </th>
                                    <th class="px-5 py-2.5">
                                        <span class="sr-only"
                                            >Stock movements</span
                                        >
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="run in runs"
                                    :key="run.id"
                                    class="hover:bg-accent/40"
                                >
                                    <td class="px-5 py-3">
                                        <Link
                                            :href="run.url"
                                            class="font-mono text-xs font-medium hover:text-primary hover:underline"
                                            >{{ run.run_number }}</Link
                                        >
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right whitespace-nowrap tabular-nums"
                                    >
                                        {{
                                            formatNumber(run.output_quantity, 3)
                                        }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right font-medium tabular-nums"
                                    >
                                        {{ money(run.total_input_cost) }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                                    >
                                        {{ money(run.unit_production_cost) }}
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <p>
                                            {{
                                                formatDateTime(run.completed_at)
                                            }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            by
                                            {{ run.completed_by ?? 'unknown' }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            as-child
                                        >
                                            <Link
                                                :href="run.ledger_url"
                                                :aria-label="`Stock movements for ${run.run_number}`"
                                                ><ExternalLink /> Stock
                                                movements</Link
                                            >
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState
                        v-else
                        :icon="Boxes"
                        title="No production runs yet"
                        description="Runs appear here once production is recorded against this build."
                    />
                </Panel>
            </div>

            <Panel title="Details">
                <dl class="grid gap-3 text-sm">
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Innovation
                        </dt>
                        <dd>
                            <Link
                                :href="order.innovation.url"
                                class="hover:text-primary hover:underline"
                                >{{ order.innovation.name }}</Link
                            >
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ order.innovation.code }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Formula
                        </dt>
                        <dd>Version {{ order.formula_version }}</dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Location
                        </dt>
                        <dd>{{ order.location ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Requested
                        </dt>
                        <dd>
                            {{ formatDate(order.created_at) }}
                            <span class="text-muted-foreground"
                                >by {{ order.requester ?? 'unknown' }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Started
                        </dt>
                        <dd>{{ formatDate(order.started_at) }}</dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Completed
                        </dt>
                        <dd>{{ formatDate(order.completed_at) }}</dd>
                    </div>
                    <div v-if="order.notes">
                        <dt class="label-mono text-muted-foreground">Notes</dt>
                        <dd class="whitespace-pre-line">{{ order.notes }}</dd>
                    </div>
                </dl>
            </Panel>
        </div>

        <CompleteRunDialog
            v-if="order.can.complete"
            v-model:open="completeOpen"
            :order="order"
        />

        <ConfirmDialog
            v-model:open="cancelOpen"
            :title="`Cancel ${order.order_number}?`"
            description="Cancelled builds cannot be restarted. Parts already consumed by completed runs stay consumed."
            confirm-label="Cancel build"
            :processing="cancelProcessing"
            @confirm="cancel"
        />
    </div>
</template>
