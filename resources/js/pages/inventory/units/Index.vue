<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/common/PageHeader.vue';
import PageRoadmap from '@/components/common/PageRoadmap.vue';
import type { RoadmapArea } from '@/components/common/PageRoadmap.vue';
import innovations from '@/routes/innovations';
import inventory from '@/routes/inventory';

const page = usePage();
const can = computed(() => page.props.auth.access?.can);

const tasks = [
    'Build unit creation and editing with name, symbol, and active state.',
    'Assign a default unit to each inventory item.',
    'Let each bill-of-materials entry use the appropriate unit for its required quantity.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Inventory items',
        description: 'An item uses a default unit for its stock snapshot.',
        href: can.value?.viewInventoryItems ? inventory.items.index() : null,
    },
    {
        label: 'Innovations',
        description:
            'A bill-of-materials line records quantity and unit together.',
        href: can.value?.viewInnovations ? innovations.index() : null,
    },
]);
</script>
<template>
    <Head title="Units" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Inventory"
            title="Units"
            description="Standardize how item stock and required quantities are measured."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
