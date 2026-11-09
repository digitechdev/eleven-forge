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
    'Build technology records with category, description, website, and active or deprecated status.',
    'Link technologies to innovations with the version used and its purpose.',
    'Add search and filters so staff can find innovations using a technology.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Innovations',
        description:
            'An innovation can use many technologies, and a technology can support many innovations.',
        href: can.value?.viewInnovations ? innovations.index() : null,
    },
    {
        label: 'Dashboard',
        description:
            'Technology usage can contribute to future portfolio summaries.',
        href: dashboard(),
    },
]);
</script>
<template>
    <Head title="Technologies" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Registry"
            title="Technologies"
            description="Maintain the non-physical tools and platforms used by innovations."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
