<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/common/PageHeader.vue';
import PageRoadmap from '@/components/common/PageRoadmap.vue';
import type { RoadmapArea } from '@/components/common/PageRoadmap.vue';
import innovations from '@/routes/innovations';
import inventory from '@/routes/inventory';
import { dashboard } from '@/routes';

const page = usePage();
const can = computed(() => page.props.auth.access?.can);

const tasks = [
    'Build item creation and editing for codes, type, specifications, category, unit, cost, and status.',
    'Track quantity on hand as a stock snapshot and flag items below minimum stock.',
    'Connect items to innovation bills of materials with required quantities and optional parts.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Item categories',
        description:
            'Categories organize items in a physical inventory hierarchy.',
        href: can.value?.viewItemCategories
            ? inventory.categories.index()
            : null,
    },
    {
        label: 'Units',
        description: 'Each item has a default measurement unit.',
        href: can.value?.viewUnits ? inventory.units.index() : null,
    },
    {
        label: 'Innovations',
        description: 'Items are reused in innovation bills of materials.',
        href: can.value?.viewInnovations ? innovations.index() : null,
    },
    {
        label: 'Dashboard',
        description:
            'Low-stock items can appear in a future dashboard summary.',
        href: dashboard(),
    },
]);
</script>
<template>
    <Head title="Inventory items" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Inventory"
            title="Inventory items"
            description="The master catalog for physical materials, components, and equipment."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
