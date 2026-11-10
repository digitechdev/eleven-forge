<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Ban,
    CalendarCheck,
    ClipboardList,
    Hammer,
    Play,
    Plus,
    Search,
} from '@lucide/vue';
import { onMounted, ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Pagination from '@/components/common/Pagination.vue';
import Panel from '@/components/common/Panel.vue';
import ProgressBar from '@/components/common/ProgressBar.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import type { Option } from '@/components/innovations/types';
import CompleteRunDialog from '@/components/production/CompleteRunDialog.vue';
import { buildOrderStatusTone } from '@/components/production/types';
import type { BuildOrderRow } from '@/components/production/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useFilters } from '@/composables/useFilters';
import { formatDate, formatNumber } from '@/lib/format';
import buildOrders from '@/routes/production/build-orders';
import { index as buildOrdersIndexCrumb } from '@/routes/production/build-orders';
import type { Paginated } from '@/types';

useBreadcrumbs([
    { title: 'Production', href: buildOrdersIndexCrumb() },
    { title: 'Builds', href: buildOrdersIndexCrumb() },
]);

const props = defineProps<{
    orders: Paginated<BuildOrderRow>;
    filters: {
        search?: string;
        status?: string;
        innovation?: string;
        location?: string;
    };
    options: {
        statuses: Option[];
        innovations: Option<number>[];
        locations: Option<number>[];
        buildableInnovations: Option<number>[];
        activeLocations: Option<number>[];
    };
    openCreate: boolean;
    can: { create: boolean };
}>();

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        status: props.filters.status ?? null,
        innovation: props.filters.innovation
            ? Number(props.filters.innovation)
            : null,
        location: props.filters.location
            ? Number(props.filters.location)
            : null,
    },
    () => buildOrders.index().url,
);

type CreateForm = {
    innovation_id: number | null;
    location_id: number | null;
    planned_quantity: number | string;
    notes: string | null;
    plan: boolean;
};

const blank = (): CreateForm => ({
    innovation_id:
        props.options.buildableInnovations.find(
            (option) => option.value === Number(props.filters.innovation),
        )?.value ?? null,
    location_id:
        props.options.activeLocations.length === 1
            ? props.options.activeLocations[0].value
            : null,
    planned_quantity: 1,
    notes: null,
    plan: true,
});

const createOpen = ref(false);
const form = useForm<CreateForm>(blank());

function openCreateDialog(): void {
    form.defaults(blank());
    form.reset();
    form.clearErrors();
    createOpen.value = true;
}

function submit(plan: boolean): void {
    form.plan = plan;
    form.post(buildOrders.store().url, {
        preserveScroll: true,
        onSuccess: () => (createOpen.value = false),
    });
}

onMounted(() => {
    if (props.openCreate) {
        openCreateDialog();
    }
});

const completing = ref<BuildOrderRow | null>(null);
const completeOpen = ref(false);

function openComplete(order: BuildOrderRow): void {
    completing.value = order;
    completeOpen.value = true;
}

const cancelling = ref<BuildOrderRow | null>(null);
const cancelOpen = ref(false);
const cancelProcessing = ref(false);

function confirmCancel(order: BuildOrderRow): void {
    cancelling.value = order;
    cancelOpen.value = true;
}

function cancel(): void {
    if (!cancelling.value) {
        return;
    }

    router.patch(
        buildOrders.cancel(cancelling.value.id).url,
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

function transition(order: BuildOrderRow, action: 'plan' | 'start'): void {
    router.patch(
        buildOrders[action](order.id).url,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Builds" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Production"
            title="Builds"
            description="Planned builds of innovations from their active formula, consuming parts at one location."
        >
            <template v-if="can.create" #actions>
                <Button @click="openCreateDialog"><Plus /> New build</Button>
            </template>
        </PageHeader>

        <Panel flush>
            <div class="flex flex-wrap items-center gap-3 border-b px-5 py-3">
                <div class="relative min-w-56 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="filters.search"
                        class="pl-9"
                        placeholder="Search build number or innovation"
                        aria-label="Search builds"
                    />
                </div>
                <SelectInput
                    v-model="filters.status"
                    class="w-40"
                    :options="options.statuses"
                    placeholder="All statuses"
                    aria-label="Filter by status"
                />
                <SelectInput
                    v-model="filters.innovation"
                    class="w-48"
                    :options="options.innovations"
                    placeholder="All innovations"
                    aria-label="Filter by innovation"
                />
                <SelectInput
                    v-model="filters.location"
                    class="w-40"
                    :options="options.locations"
                    placeholder="All locations"
                    aria-label="Filter by location"
                />
            </div>

            <div v-if="props.orders.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">Build</th>
                            <th class="px-5 py-2.5 font-medium">Innovation</th>
                            <th class="px-5 py-2.5 font-medium">Location</th>
                            <th class="px-5 py-2.5 font-medium">Progress</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 font-medium">Requested</th>
                            <th class="px-5 py-2.5 font-medium">Dates</th>
                            <th class="px-5 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="order in props.orders.data"
                            :key="order.id"
                            class="align-top hover:bg-accent/40"
                        >
                            <td class="px-5 py-3">
                                <Link
                                    :href="order.url"
                                    class="font-mono text-xs font-medium hover:text-primary hover:underline"
                                    >{{ order.order_number }}</Link
                                >
                                <p
                                    v-if="order.notes"
                                    class="line-clamp-1 max-w-48 text-xs text-muted-foreground"
                                >
                                    {{ order.notes }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <Link
                                    :href="order.innovation.url"
                                    class="font-medium hover:text-primary hover:underline"
                                    >{{ order.innovation.name }}</Link
                                >
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ order.innovation.code }} · v{{
                                        order.formula_version
                                    }}
                                </p>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ order.location ?? '—' }}
                            </td>
                            <td class="min-w-40 px-5 py-3">
                                <p class="mb-1.5 tabular-nums">
                                    {{
                                        formatNumber(order.produced_quantity, 3)
                                    }}
                                    <span class="text-muted-foreground"
                                        >/
                                        {{
                                            formatNumber(
                                                order.planned_quantity,
                                                3,
                                            )
                                        }}</span
                                    >
                                </p>
                                <ProgressBar
                                    :value="order.progress"
                                    size="sm"
                                />
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    variant="outline"
                                    :class="buildOrderStatusTone[order.status]"
                                    >{{ order.status_label }}</Badge
                                >
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <p>{{ order.requester ?? '—' }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(order.created_at) }}
                                </p>
                            </td>
                            <td
                                class="px-5 py-3 text-xs whitespace-nowrap text-muted-foreground"
                            >
                                <p>
                                    Started {{ formatDate(order.started_at) }}
                                </p>
                                <p>
                                    Completed
                                    {{ formatDate(order.completed_at) }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        v-if="order.can.complete"
                                        size="sm"
                                        variant="outline"
                                        @click="openComplete(order)"
                                        ><Hammer /> Complete run</Button
                                    >
                                    <Button
                                        v-if="order.can.plan"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Plan ${order.order_number}`"
                                        @click="transition(order, 'plan')"
                                    >
                                        <CalendarCheck />
                                    </Button>
                                    <Button
                                        v-if="order.can.start"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Start ${order.order_number}`"
                                        @click="transition(order, 'start')"
                                    >
                                        <Play />
                                    </Button>
                                    <Button
                                        v-if="order.can.cancel"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Cancel ${order.order_number}`"
                                        @click="confirmCancel(order)"
                                    >
                                        <Ban />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="ClipboardList"
                title="No builds found"
                :description="
                    can.create
                        ? 'Try a different filter, or plan a new build.'
                        : 'Try a different search or filter.'
                "
            />
            <Pagination :paginator="props.orders" />
        </Panel>

        <FormDialog
            v-if="can.create"
            v-model:open="createOpen"
            title="New build"
            description="The build uses the innovation's active formula and consumes parts at the chosen location."
            :icon="ClipboardList"
            size="md"
        >
            <form @submit.prevent="submit(true)">
                <FormSection>
                    <Field
                        label="Innovation"
                        for="order-innovation"
                        :error="form.errors.innovation_id"
                        hint="Only innovations with an active formula can be built."
                        required
                        class="sm:col-span-2"
                    >
                        <SelectInput
                            id="order-innovation"
                            v-model="form.innovation_id"
                            :options="options.buildableInnovations"
                            placeholder="Choose an innovation"
                        />
                    </Field>
                    <Field
                        label="Location"
                        for="order-location"
                        :error="form.errors.location_id"
                        hint="Where parts are taken from."
                        required
                    >
                        <SelectInput
                            id="order-location"
                            v-model="form.location_id"
                            :options="options.activeLocations"
                            placeholder="Choose a location"
                        />
                    </Field>
                    <Field
                        label="Planned quantity"
                        for="order-quantity"
                        :error="form.errors.planned_quantity"
                        required
                    >
                        <Input
                            id="order-quantity"
                            v-model="form.planned_quantity"
                            type="number"
                            min="0"
                            step="0.001"
                        />
                    </Field>
                    <Field
                        label="Notes"
                        for="order-notes"
                        :error="form.errors.notes"
                        class="sm:col-span-2"
                    >
                        <TextareaInput
                            id="order-notes"
                            v-model="form.notes"
                            placeholder="Purpose, client, or deadline"
                        />
                    </Field>
                </FormSection>
                <FormFooter
                    submit-label="Plan build"
                    :processing="form.processing"
                    @cancel="createOpen = false"
                >
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="submit(false)"
                        >Save as draft</Button
                    >
                </FormFooter>
            </form>
        </FormDialog>

        <CompleteRunDialog v-model:open="completeOpen" :order="completing" />

        <ConfirmDialog
            v-model:open="cancelOpen"
            :title="`Cancel ${cancelling?.order_number ?? 'build'}?`"
            description="Cancelled builds cannot be restarted. Parts already consumed by completed runs stay consumed."
            confirm-label="Cancel build"
            :processing="cancelProcessing"
            @confirm="cancel"
        />
    </div>
</template>
