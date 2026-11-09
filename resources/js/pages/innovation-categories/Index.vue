<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/common/PageHeader.vue';
import PageRoadmap from '@/components/common/PageRoadmap.vue';
import type { RoadmapArea } from '@/components/common/PageRoadmap.vue';
import innovations from '@/routes/innovations';
import { dashboard } from '@/routes';

const page = usePage();
const can = computed(() => page.props.auth.access?.can);

const tasks = [
    'Build category creation and editing with name, slug, description, active state, and display order.',
    'Let staff assign a category to an innovation and filter the registry by category.',
    'Handle category deactivation without losing existing innovation relationships.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Innovations',
        description: 'One category can classify many innovation records.',
        href: can.value?.viewInnovations ? innovations.index() : null,
    },
    {
        label: 'Dashboard',
        description:
            'Category summaries can feed future innovation overview cards.',
        href: dashboard(),
    },
]);
</script>
<template>
    <Head title="Innovation categories" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Registry"
            title="Innovation categories"
            description="Classify innovations by business area or purpose."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
