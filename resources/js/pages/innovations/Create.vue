<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/common/PageHeader.vue';
import InnovationForm from '@/components/innovations/InnovationForm.vue';
import type {
    InnovationFields,
    InnovationFormOptions,
} from '@/components/innovations/types';
import innovations from '@/routes/innovations';
import {
    create as innovationsCreateCrumb,
    index as innovationsIndexCrumb,
} from '@/routes/innovations';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([
    { title: 'Innovations', href: innovationsIndexCrumb() },
    { title: 'New innovation', href: innovationsCreateCrumb() },
]);

const props = defineProps<{
    options: InnovationFormOptions;
    suggestedCode: string;
}>();

const initial: InnovationFields = {
    innovation_code: null,
    name: '',
    slug: null,
    category_id: null,
    type: props.options.types[0]?.value ?? 'SOFTWARE',
    tagline: null,
    short_description: null,
    description: null,
    problem_statement: null,
    solution_description: null,
    key_features: [''],
    benefits: [''],
    status: 'CONCEPT',
    visibility: 'INTERNAL',
    developed_at: null,
    is_featured: false,
    markup_percentage: null,
};
</script>

<template>
    <Head title="New innovation" />

    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Registry"
            title="New innovation"
            description="Record the essentials now; the bill of materials, technologies, and media are added on the innovation page after saving."
        />

        <InnovationForm
            :initial="initial"
            :options="options"
            :action="{ url: innovations.store().url, method: 'post' }"
            submit-label="Create innovation"
            :cancel-href="innovations.index().url"
            :code-placeholder="suggestedCode"
        />
    </div>
</template>
