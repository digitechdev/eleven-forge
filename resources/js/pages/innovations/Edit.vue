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
    edit as innovationsEditCrumb,
    index as innovationsIndexCrumb,
    show as innovationsShowCrumb,
} from '@/routes/innovations';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

const props = defineProps<{
    innovation: InnovationFields & { id: number };
    options: InnovationFormOptions;
}>();

useBreadcrumbs(() => [
    { title: 'Innovations', href: innovationsIndexCrumb() },
    {
        title: props.innovation.name,
        href: innovationsShowCrumb(props.innovation.id),
    },
    { title: 'Edit', href: innovationsEditCrumb(props.innovation.id) },
]);
</script>

<template>
    <Head :title="`Edit ${innovation.name}`" />

    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6">
        <PageHeader
            :eyebrow="innovation.innovation_code"
            :title="`Edit ${innovation.name}`"
        />

        <InnovationForm
            :initial="innovation"
            :options="options"
            :action="{
                url: innovations.update(innovation.id).url,
                method: 'put',
            }"
            submit-label="Save innovation"
            :cancel-href="innovations.show(innovation.id).url"
        />
    </div>
</template>
