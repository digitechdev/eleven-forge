<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/common/PageHeader.vue';
import PageRoadmap from '@/components/common/PageRoadmap.vue';
import type { RoadmapArea } from '@/components/common/PageRoadmap.vue';
import innovationCategories from '@/routes/innovation-categories';
import inventory from '@/routes/inventory';
import showcase from '@/routes/showcase';
import technologies from '@/routes/technologies';

const page = usePage();
const can = computed(() => page.props.auth.access?.can);

const tasks = [
    'Build create, edit, search, and restore flows for innovation details, status, category, and ownership.',
    'Add a bill of materials with inventory items, quantities, units, and optional parts.',
    'Link technologies with version and purpose, and attach images or video URLs.',
    'Control public visibility, featured status, and which media can appear in Showcase.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Innovation categories',
        description:
            'Each innovation belongs to a business or functional category.',
        href: can.value?.viewInnovationCategories
            ? innovationCategories.index()
            : null,
    },
    {
        label: 'Inventory items',
        description:
            'Physical parts used by an innovation become its bill of materials.',
        href: can.value?.viewInventoryItems ? inventory.items.index() : null,
    },
    {
        label: 'Technologies',
        description:
            'Software tools and platforms are linked with their version and purpose.',
        href: can.value?.viewTechnologies ? technologies.index() : null,
    },
    {
        label: 'Showcase',
        description:
            'Selected public innovations and media appear on the public site.',
        href: showcase.index(),
    },
]);
</script>
<template>
    <Head title="Innovations" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Registry"
            title="Innovations"
            description="The central record for each software, hardware, or hybrid innovation."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
