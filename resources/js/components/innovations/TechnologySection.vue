<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Cpu, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import Panel from '@/components/common/Panel.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import type {
    DetailOptions,
    TechnologyLink,
} from '@/components/innovations/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import links from '@/routes/innovations/technologies';

const props = defineProps<{
    innovationId: number;
    technologies: TechnologyLink[];
    options: DetailOptions | null;
    canUpdate: boolean;
    hint?: string | null;
}>();

type TechnologyForm = {
    technology_id: number | null;
    version: string | null;
    purpose: string | null;
};

const dialogOpen = ref(false);
const editing = ref<TechnologyLink | null>(null);
const form = useForm<TechnologyForm>({
    technology_id: null,
    version: null,
    purpose: null,
});

const removing = ref<TechnologyLink | null>(null);
const removeOpen = ref(false);
const removeProcessing = ref(false);

/** Technologies not linked yet, plus the one being edited. */
const technologyOptions = computed(() => {
    const used = new Set(
        props.technologies
            .filter((link) => link.id !== editing.value?.id)
            .map((link) => link.technology_id),
    );

    return (props.options?.technologies ?? []).filter(
        (technology) => !used.has(technology.value),
    );
});

function openCreate(): void {
    editing.value = null;
    form.defaults({ technology_id: null, version: null, purpose: null });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(link: TechnologyLink): void {
    editing.value = link;
    form.defaults({
        technology_id: link.technology_id,
        version: link.version,
        purpose: link.purpose,
    });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };

    if (editing.value) {
        form.put(
            links.update([props.innovationId, editing.value.id]).url,
            options,
        );
    } else {
        form.post(links.store(props.innovationId).url, options);
    }
}

function confirmRemove(link: TechnologyLink): void {
    removing.value = link;
    removeOpen.value = true;
}

function remove(): void {
    if (!removing.value) {
        return;
    }

    router.delete(links.destroy([props.innovationId, removing.value.id]).url, {
        preserveScroll: true,
        onStart: () => (removeProcessing.value = true),
        onFinish: () => {
            removeProcessing.value = false;
            removeOpen.value = false;
        },
    });
}
</script>

<template>
    <Panel
        title="Technologies"
        :icon="Cpu"
        :description="
            hint ??
            'Software tools, platforms, and frameworks this innovation uses.'
        "
        flush
    >
        <template v-if="canUpdate" #actions>
            <Button size="sm" @click="openCreate"
                ><Plus /> Link technology</Button
            >
        </template>

        <ul v-if="technologies.length" class="divide-y">
            <li
                v-for="link in technologies"
                :key="link.id"
                class="flex items-start justify-between gap-4 px-5 py-3"
            >
                <div class="min-w-0 space-y-0.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ link.name }}</span>
                        <Badge
                            v-if="link.version"
                            variant="outline"
                            class="font-mono"
                            >{{ link.version }}</Badge
                        >
                        <span
                            v-if="link.category"
                            class="label-mono text-muted-foreground"
                            >{{ link.category }}</span
                        >
                    </div>
                    <p
                        v-if="link.purpose"
                        class="text-sm text-muted-foreground"
                    >
                        {{ link.purpose }}
                    </p>
                </div>
                <div v-if="canUpdate" class="flex shrink-0 gap-1">
                    <Button
                        size="icon"
                        variant="ghost"
                        :aria-label="`Edit ${link.name}`"
                        @click="openEdit(link)"
                    >
                        <Pencil />
                    </Button>
                    <Button
                        size="icon"
                        variant="ghost"
                        :aria-label="`Remove ${link.name}`"
                        @click="confirmRemove(link)"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </li>
        </ul>
        <EmptyState
            v-else
            :icon="Cpu"
            title="No technologies linked"
            description="Link the languages, frameworks, and platforms behind this innovation."
        />
    </Panel>

    <FormDialog
        v-if="canUpdate && options"
        v-model:open="dialogOpen"
        :title="editing ? `Edit ${editing.name}` : 'Link technology'"
        :icon="Cpu"
        size="md"
    >
        <form @submit.prevent="submit">
            <FormSection :columns="1">
                <Field
                    label="Technology"
                    for="technology-id"
                    :error="form.errors.technology_id"
                    required
                >
                    <SelectInput
                        id="technology-id"
                        v-model="form.technology_id"
                        :options="technologyOptions"
                        placeholder="Choose a technology"
                    />
                </Field>
                <Field
                    label="Version"
                    for="technology-version"
                    :error="form.errors.version"
                >
                    <Input
                        id="technology-version"
                        v-model="form.version"
                        placeholder="e.g. 13.x"
                    />
                </Field>
                <Field
                    label="Purpose"
                    for="technology-purpose"
                    :error="form.errors.purpose"
                >
                    <TextareaInput
                        id="technology-purpose"
                        v-model="form.purpose"
                        placeholder="How the technology is used"
                    />
                </Field>
            </FormSection>
            <FormFooter
                :submit-label="editing ? 'Save link' : 'Link technology'"
                :processing="form.processing"
                @cancel="dialogOpen = false"
            />
        </form>
    </FormDialog>

    <ConfirmDialog
        v-model:open="removeOpen"
        :title="`Remove ${removing?.name ?? 'technology'}?`"
        description="The technology stays in the catalog; only its link to this innovation is removed."
        confirm-label="Remove"
        :processing="removeProcessing"
        @confirm="remove"
    />
</template>
