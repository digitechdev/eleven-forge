<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, ArrowLeft, Info, Pencil, Star } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Panel from '@/components/common/Panel.vue';
import BomSection from '@/components/innovations/BomSection.vue';
import MediaSection from '@/components/innovations/MediaSection.vue';
import TechnologySection from '@/components/innovations/TechnologySection.vue';
import { statusTone } from '@/components/innovations/types';
import type {
    BomLine,
    DetailOptions,
    InnovationFields,
    MediaEntry,
    TechnologyLink,
} from '@/components/innovations/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime } from '@/lib/format';
import innovations from '@/routes/innovations';
import {
    index as innovationsIndexCrumb,
    show as innovationsShowCrumb,
} from '@/routes/innovations';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

type InnovationDetail = InnovationFields & {
    id: number;
    innovation_code: string;
    slug: string;
    category: string | null;
    type_label: string;
    status_label: string;
    visibility_label: string;
    creator: string | null;
    updater: string | null;
    created_at: string | null;
    updated_at: string | null;
};

const props = defineProps<{
    innovation: InnovationDetail;
    items: BomLine[];
    bomTotal: number;
    bomHasUnpricedLines: boolean;
    technologies: TechnologyLink[];
    media: MediaEntry[];
    options: DetailOptions | null;
    can: { update: boolean; delete: boolean };
}>();

useBreadcrumbs(() => [
    { title: 'Innovations', href: innovationsIndexCrumb() },
    {
        title: props.innovation.name,
        href: innovationsShowCrumb(props.innovation.id),
    },
]);

/** Software leads with technologies; hardware and hybrid lead with the BOM. */
const technologiesFirst = computed(() => props.innovation.type === 'SOFTWARE');

const bomHint = computed(() =>
    props.innovation.type === 'SOFTWARE'
        ? 'Software innovations rarely need physical parts — add items only if there is hardware involved.'
        : null,
);

const technologyHint = computed(() =>
    props.innovation.type === 'HARDWARE'
        ? 'Hardware innovations focus on the bill of materials — link firmware or tooling here if relevant.'
        : null,
);

const story = computed(() =>
    [
        { label: 'Description', value: props.innovation.description },
        { label: 'Problem', value: props.innovation.problem_statement },
        { label: 'Solution', value: props.innovation.solution_description },
    ].filter((entry) => entry.value),
);

const archiveOpen = ref(false);
const archiveProcessing = ref(false);

function archive(): void {
    router.delete(innovations.destroy(props.innovation.id).url, {
        onStart: () => (archiveProcessing.value = true),
        onFinish: () => {
            archiveProcessing.value = false;
            archiveOpen.value = false;
        },
    });
}
</script>

<template>
    <Head :title="innovation.name" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
        <Link
            :href="innovations.index().url"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-primary"
        >
            <ArrowLeft class="size-4" /> All innovations
        </Link>

        <PageHeader
            :eyebrow="innovation.innovation_code"
            :title="innovation.name"
            :description="innovation.tagline"
        >
            <template #meta>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="outline">{{ innovation.type_label }}</Badge>
                    <Badge
                        variant="outline"
                        :class="statusTone[innovation.status]"
                        >{{ innovation.status_label }}</Badge
                    >
                    <Badge
                        :variant="
                            innovation.visibility === 'PUBLIC'
                                ? 'secondary'
                                : 'outline'
                        "
                        >{{ innovation.visibility_label }}</Badge
                    >
                    <Badge v-if="innovation.is_featured"
                        ><Star class="fill-current" /> Featured</Badge
                    >
                </div>
            </template>
            <template v-if="can.update || can.delete" #actions>
                <Button
                    v-if="can.delete"
                    variant="outline"
                    @click="archiveOpen = true"
                >
                    <Archive /> Archive
                </Button>
                <Button v-if="can.update" as-child>
                    <Link :href="innovations.edit(innovation.id).url"
                        ><Pencil /> Edit</Link
                    >
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Panel title="Overview" :icon="Info">
                    <div class="space-y-5 text-sm">
                        <p
                            v-if="innovation.short_description"
                            class="text-base text-foreground/90"
                        >
                            {{ innovation.short_description }}
                        </p>
                        <div v-for="entry in story" :key="entry.label">
                            <h3 class="mb-1 label-mono text-muted-foreground">
                                {{ entry.label }}
                            </h3>
                            <p class="whitespace-pre-line">{{ entry.value }}</p>
                        </div>
                        <div
                            v-if="
                                innovation.key_features.length ||
                                innovation.benefits.length
                            "
                            class="grid gap-5 sm:grid-cols-2"
                        >
                            <div v-if="innovation.key_features.length">
                                <h3
                                    class="mb-1 label-mono text-muted-foreground"
                                >
                                    Key features
                                </h3>
                                <ul class="list-disc space-y-1 pl-5">
                                    <li
                                        v-for="feature in innovation.key_features"
                                        :key="feature"
                                    >
                                        {{ feature }}
                                    </li>
                                </ul>
                            </div>
                            <div v-if="innovation.benefits.length">
                                <h3
                                    class="mb-1 label-mono text-muted-foreground"
                                >
                                    Benefits
                                </h3>
                                <ul class="list-disc space-y-1 pl-5">
                                    <li
                                        v-for="benefit in innovation.benefits"
                                        :key="benefit"
                                    >
                                        {{ benefit }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p
                            v-if="
                                !innovation.short_description &&
                                !story.length &&
                                !innovation.key_features.length &&
                                !innovation.benefits.length
                            "
                            class="text-muted-foreground"
                        >
                            No description yet.
                        </p>
                    </div>
                </Panel>
            </div>

            <Panel title="Details">
                <dl class="grid gap-3 text-sm">
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Category
                        </dt>
                        <dd>{{ innovation.category ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">Slug</dt>
                        <dd class="font-mono text-xs break-all">
                            {{ innovation.slug }}
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Developed
                        </dt>
                        <dd>{{ formatDate(innovation.developed_at) }}</dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Created
                        </dt>
                        <dd>
                            {{ formatDateTime(innovation.created_at) }}
                            <span class="text-muted-foreground"
                                >by {{ innovation.creator ?? 'unknown' }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="label-mono text-muted-foreground">
                            Last updated
                        </dt>
                        <dd>
                            {{ formatDateTime(innovation.updated_at) }}
                            <span
                                v-if="innovation.updater"
                                class="text-muted-foreground"
                                >by {{ innovation.updater }}</span
                            >
                        </dd>
                    </div>
                </dl>
            </Panel>
        </div>

        <div class="flex flex-col gap-6">
            <div :class="technologiesFirst ? 'order-2' : 'order-1'">
                <BomSection
                    :innovation-id="innovation.id"
                    :items="items"
                    :total="bomTotal"
                    :has-unpriced-lines="bomHasUnpricedLines"
                    :options="options"
                    :can-update="can.update"
                    :hint="bomHint"
                />
            </div>
            <div :class="technologiesFirst ? 'order-1' : 'order-2'">
                <TechnologySection
                    :innovation-id="innovation.id"
                    :technologies="technologies"
                    :options="options"
                    :can-update="can.update"
                    :hint="technologyHint"
                />
            </div>
        </div>

        <MediaSection
            :innovation-id="innovation.id"
            :media="media"
            :options="options"
            :can-update="can.update"
        />

        <ConfirmDialog
            v-model:open="archiveOpen"
            :title="`Archive ${innovation.name}?`"
            description="The innovation is hidden from the registry and showcase. Managers can restore it later."
            confirm-label="Archive"
            :processing="archiveProcessing"
            @confirm="archive"
        />
    </div>
</template>
