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
    'Build category creation and editing with optional parent, description, active state, and display order.',
    'Show the category hierarchy and let staff filter items by category.',
    'Preserve item assignments when a category is deactivated.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Inventory items',
        description: 'One category can contain many physical items.',
        href: can.value?.viewInventoryItems ? inventory.items.index() : null,
    },
    {
        label: 'Innovations',
        description:
            'Categorized items become parts in innovation bills of materials.',
        href: can.value?.viewInnovations ? innovations.index() : null,
    },
]);
</script>
<template>
    <Head title="Item categories" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Inventory"
            title="Item categories"
            description="Organize physical inventory into parent and child categories."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
