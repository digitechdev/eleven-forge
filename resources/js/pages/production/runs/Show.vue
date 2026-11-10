<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Boxes, ExternalLink, Hammer, Info } from '@lucide/vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Panel from '@/components/common/Panel.vue';
import StatCard from '@/components/common/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { formatDateTime, formatNumber } from '@/lib/format';
import {
    index as buildOrdersIndexCrumb,
    show as buildOrdersShowCrumb,
} from '@/routes/production/build-orders';
import { show as runsShowCrumb } from '@/routes/production/runs';

type RunDetail = {
    id: number;
    run_number: string;
    innovation: {
        id: number;
        code: string | null;
        name: string | null;
        url: string;
    };
    build_order: {
        id: number;
        number: string | null;
        status_label: string | null;
        planned_quantity: number;
        produced_quantity: number;
        url: string;
    };
    formula_version: number | null;
    formula_yield: number | null;
    location: string | null;
    output_quantity: number;
    total_input_cost: number;
    unit_production_cost: number;
    markup_percentage: number | null;
    suggested_selling_price: number | null;
    completed_at: string;
    completed_by: string | null;
    notes: string | null;
};

type RunInput = {
    id: number;
    item_code: string | null;
    item_name: string | null;
    item_url: string | null;
    unit_symbol: string | null;
    location: string | null;
    formula_quantity: number;
    consumed_quantity: number;
    unit_cost: number;
    line_cost: number;
    ledger_entry_number: string | null;
    balance_after: number | null;
};

const props = defineProps<{
    run: RunDetail;
    inputs: RunInput[];
    ledgerUrl: string | null;
}>();

useBreadcrumbs(() => [
    { title: 'Production', href: buildOrdersIndexCrumb() },
    { title: 'Builds', href: buildOrdersIndexCrumb() },
    {
        title: props.run.build_order.number ?? 'Build',
        href: buildOrdersShowCrumb(props.run.build_order.id),
    },
    { title: props.run.run_number, href: runsShowCrumb(props.run.id) },
]);

function money(value: number | null): string {
    return value === null ? '—' : `₱${formatNumber(value)}`;
}
</script>

<template>
    <Head :title="run.run_number" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
        <Link
            :href="run.build_order.url"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-primary"
        >
            <ArrowLeft class="size-4" /> Back to
            {{ run.build_order.number ?? 'build' }}
        </Link>

        <PageHeader
            eyebrow="Production run"
            :title="run.run_number"
            :description="`${run.innovation.name} · formula v${run.formula_version}`"
        >
            <template #meta>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">Completed</Badge>
                    <Badge variant="outline">{{ run.location }}</Badge>
                </div>
            </template>
            <template v-if="ledgerUrl" #actions>
                <Button variant="outline" as-child>
                    <Link :href="ledgerUrl"
                        ><ExternalLink /> Ledger entries</Link
                    >
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-3">
            <StatCard
                label="Output"
                :value="formatNumber(run.output_quantity, 3)"
                unit="units"
                :icon="Hammer"
            />
            <StatCard
                label="Total input cost"
                :value="money(run.total_input_cost)"
                :icon="Boxes"
                tone="steel"
            />
            <StatCard
                label="Unit production cost"
                :value="money(run.unit_production_cost)"
                :icon="Info"
                tone="patina"
            >
                Suggested price {{ money(run.suggested_selling_price) }}
                <span v-if="run.markup_percentage !== null"
                    >({{ run.markup_percentage }}% markup)</span
                >
            </StatCard>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Panel
                title="Consumed inputs"
                :icon="Boxes"
                description="Quantities and costs as they were when the run was completed."
                flush
                class="lg:col-span-2"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr class="label-mono text-muted-foreground">
                                <th class="px-5 py-2.5 font-medium">Item</th>
                                <th class="px-5 py-2.5 text-right font-medium">
                                    Per formula
                                </th>
                                <th class="px-5 py-2.5 text-right font-medium">
                                    Consumed
                                </th>
                                <th class="px-5 py-2.5 text-right font-medium">
                                    Unit cost
                                </th>
                                <th class="px-5 py-2.5 text-right font-medium">
                                    Line cost
                                </th>
                                <th class="px-5 py-2.5 font-medium">
                                    Ledger entry
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="input in inputs"
                                :key="input.id"
                                class="hover:bg-accent/40"
                            >
                                <td class="px-5 py-3">
                                    <Link
                                        v-if="input.item_url"
                                        :href="input.item_url"
                                        class="font-medium hover:text-primary hover:underline"
                                        >{{ input.item_name }}</Link
                                    >
                                    <span v-else class="font-medium">{{
                                        input.item_name ?? 'Unknown item'
                                    }}</span>
                                    <p
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ input.item_code }} ·
                                        {{ input.location }}
                                    </p>
                                </td>
                                <td
                                    class="px-5 py-3 text-right whitespace-nowrap text-muted-foreground tabular-nums"
                                >
                                    {{
                                        formatNumber(input.formula_quantity, 3)
                                    }}
                                </td>
                                <td
                                    class="px-5 py-3 text-right whitespace-nowrap tabular-nums"
                                >
                                    {{
                                        formatNumber(input.consumed_quantity, 3)
                                    }}
                                    <span
                                        class="font-mono text-xs text-muted-foreground"
                                        >{{ input.unit_symbol }}</span
                                    >
                                </td>
                                <td
                                    class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                                >
                                    {{ money(input.unit_cost) }}
                                </td>
                                <td
                                    class="px-5 py-3 text-right font-medium tabular-nums"
                                >
                                    {{ money(input.line_cost) }}
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-mono text-xs">
                                        {{ input.ledger_entry_number ?? '—' }}
                                    </p>
                                    <p
                                        v-if="input.balance_after !== null"
                                        class="text-xs text-muted-foreground tabular-nums"
                                    >
                                        Balance after
                                        {{
                                            formatNumber(input.balance_after, 3)
                                        }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t bg-muted/20">
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-5 py-3 text-right label-mono text-muted-foreground"
                                >
                                    Total input cost
                                </td>
                                <td
                                    class="px-5 py-3 text-right font-bold tabular-nums"
                                >
                                    {{ money(run.total_input_cost) }}
                                </td>
                                <td />
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </Panel>

            <Panel title="Details">
                <dl class="grid gap-3 text-sm">
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Innovation
                        </dt>
                        <dd>
                            <Link
                                :href="run.innovation.url"
                                class="hover:text-primary hover:underline"
                                >{{ run.innovation.name }}</Link
                            >
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ run.innovation.code }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">Build</dt>
                        <dd>
                            <Link
                                :href="run.build_order.url"
                                class="font-mono text-xs hover:text-primary hover:underline"
                                >{{ run.build_order.number }}</Link
                            >
                            <span class="text-muted-foreground">
                                · {{ run.build_order.status_label }} ·
                                {{
                                    formatNumber(
                                        run.build_order.produced_quantity,
                                        3,
                                    )
                                }}
                                of
                                {{
                                    formatNumber(
                                        run.build_order.planned_quantity,
                                        3,
                                    )
                                }}
                                built</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Formula
                        </dt>
                        <dd>
                            Version {{ run.formula_version }}
                            <span class="text-muted-foreground"
                                >· yields
                                {{ formatNumber(run.formula_yield, 3) }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Completed
                        </dt>
                        <dd>
                            {{ formatDateTime(run.completed_at) }}
                            <span class="text-muted-foreground"
                                >by {{ run.completed_by ?? 'unknown' }}</span
                            >
                        </dd>
                    </div>
                    <div v-if="run.notes">
                        <dt class="label-mono text-muted-foreground">Notes</dt>
                        <dd class="whitespace-pre-line">{{ run.notes }}</dd>
                    </div>
                </dl>
            </Panel>
        </div>
    </div>
</template>
