<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/common/PageHeader.vue';
import PageRoadmap from '@/components/common/PageRoadmap.vue';
import type { RoadmapArea } from '@/components/common/PageRoadmap.vue';
import innovations from '@/routes/innovations';
import inventory from '@/routes/inventory';
import showcase from '@/routes/showcase';

const page = usePage();
const can = computed(() => page.props.auth.access?.can);

const tasks = [
    'Add live innovation counts by status and visibility.',
    'Surface low-stock inventory items and recent changes.',
    'Show useful shortcuts for the work each role can perform.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Innovations',
        description:
            'Innovation records will provide lifecycle and publication summaries.',
        href: can.value?.viewInnovations ? innovations.index() : null,
    },
    {
        label: 'Inventory items',
        description:
            'Stock snapshots and minimum levels will support low-stock alerts.',
        href: can.value?.viewInventoryItems ? inventory.items.index() : null,
    },
    {
        label: 'Showcase',
        description:
            'Public innovations are the selected output of the internal registry.',
        href: showcase.index(),
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Overview"
            :title="`Welcome back, ${page.props.auth.user.name}!`"
            description="Here is your Eleven Forge dashboard."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
