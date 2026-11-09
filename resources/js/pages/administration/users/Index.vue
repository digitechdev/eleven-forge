<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/common/PageHeader.vue';
import PageRoadmap from '@/components/common/PageRoadmap.vue';
import type { RoadmapArea } from '@/components/common/PageRoadmap.vue';
import administration from '@/routes/administration';
import innovations from '@/routes/innovations';

const page = usePage();
const can = computed(() => page.props.auth.access?.can);

const tasks = [
    'Build admin-created accounts with name, email, password setup, and active or inactive status.',
    'Assign roles to users and show the permissions each assignment provides.',
    'Add safe account updates and deactivation while preserving ownership history.',
];

const related = computed<RoadmapArea[]>(() => [
    {
        label: 'Roles',
        description: 'Roles group permissions and are assigned to users.',
        href: can.value?.manageRoles ? administration.roles.index() : null,
    },
    {
        label: 'Permissions',
        description:
            'Permissions define which pages and actions a role can use.',
        href: can.value?.manageRoles
            ? administration.permissions.index()
            : null,
    },
    {
        label: 'Innovations',
        description:
            'Creator and last-editor links keep an innovation tied to its users.',
        href: can.value?.viewInnovations ? innovations.index() : null,
    },
]);
</script>
<template>
    <Head title="Users" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Users"
            description="Control who can sign in and what they can manage."
        />
        <PageRoadmap :tasks="tasks" :related="related" />
    </div>
</template>
