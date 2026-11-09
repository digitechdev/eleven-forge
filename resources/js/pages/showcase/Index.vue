<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/common/PageHeader.vue';
import PageRoadmap from '@/components/common/PageRoadmap.vue';
import type { RoadmapArea } from '@/components/common/PageRoadmap.vue';
import innovationCategories from '@/routes/innovation-categories';
import innovations from '@/routes/innovations';

const page = usePage();
const can = computed(() => page.props.auth.access?.can);

const tasks = [
    'Display only innovations explicitly marked public and ready to showcase.',
    'Show featured innovations, their approved images or video links, and accessible descriptions.',
    'Add public browsing and detail pages without exposing internal-only records or media.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Innovations',
        description:
            'The internal registry controls status, visibility, featured selection, and public media.',
        href: can.value?.viewInnovations ? innovations.index() : null,
    },
    {
        label: 'Innovation categories',
        description:
            'Categories classify the innovations that may be presented here.',
        href: can.value?.viewInnovationCategories
            ? innovationCategories.index()
            : null,
    },
]);
</script>
<template>
    <Head title="Showcase" />

    <div class="mx-auto w-full max-w-5xl space-y-6">
        <PageHeader
            eyebrow="Public showcase"
            title="Showcase"
            description="This public space will feature approved innovations and selected media."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
