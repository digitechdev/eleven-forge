<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/components/common/Field.vue';
import FormSection from '@/components/common/FormSection.vue';
import Panel from '@/components/common/Panel.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import StringListInput from '@/components/innovations/StringListInput.vue';
import type {
    InnovationFields,
    InnovationFormOptions,
} from '@/components/innovations/types';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/** The full-page create/edit form for an innovation. */
const props = defineProps<{
    initial: InnovationFields;
    options: InnovationFormOptions;
    action: { url: string; method: 'post' | 'put' };
    submitLabel: string;
    cancelHref: string;
    codePlaceholder?: string;
}>();

const form = useForm<InnovationFields>({
    ...props.initial,
    key_features: [...props.initial.key_features],
    benefits: [...props.initial.benefits],
});

function submit(): void {
    form.submit(props.action.method, props.action.url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <Panel title="Identity">
            <FormSection>
                <Field
                    label="Name"
                    for="innovation-name"
                    :error="form.errors.name"
                    required
                >
                    <Input
                        id="innovation-name"
                        v-model="form.name"
                        placeholder="e.g. Smart Egg Board V4"
                    />
                </Field>
                <Field
                    label="Category"
                    for="innovation-category"
                    :error="form.errors.category_id"
                    required
                >
                    <SelectInput
                        id="innovation-category"
                        v-model="form.category_id"
                        :options="options.categories"
                        placeholder="Choose a category"
                    />
                </Field>
                <Field
                    label="Innovation code"
                    for="innovation-code"
                    :error="form.errors.innovation_code"
                    hint="Leave blank to generate the next code automatically."
                >
                    <Input
                        id="innovation-code"
                        v-model="form.innovation_code"
                        class="font-mono"
                        :placeholder="codePlaceholder ?? 'INV-YYYY-0001'"
                    />
                </Field>
                <Field
                    label="Slug"
                    for="innovation-slug"
                    :error="form.errors.slug"
                    hint="Leave blank to derive it from the name."
                >
                    <Input
                        id="innovation-slug"
                        v-model="form.slug"
                        class="font-mono"
                        placeholder="smart-egg-board-v4"
                    />
                </Field>
                <Field
                    label="Type"
                    for="innovation-type"
                    :error="form.errors.type"
                    required
                >
                    <SelectInput
                        id="innovation-type"
                        v-model="form.type"
                        :options="options.types"
                    />
                </Field>
                <Field
                    label="Developed on"
                    for="innovation-developed-at"
                    :error="form.errors.developed_at"
                >
                    <Input
                        id="innovation-developed-at"
                        v-model="form.developed_at"
                        type="date"
                    />
                </Field>
            </FormSection>
        </Panel>

        <Panel title="Story">
            <FormSection :columns="1">
                <Field
                    label="Tagline"
                    for="innovation-tagline"
                    :error="form.errors.tagline"
                >
                    <Input
                        id="innovation-tagline"
                        v-model="form.tagline"
                        placeholder="One line for showcase cards"
                    />
                </Field>
                <Field
                    label="Short description"
                    for="innovation-short-description"
                    :error="form.errors.short_description"
                >
                    <TextareaInput
                        id="innovation-short-description"
                        v-model="form.short_description"
                    />
                </Field>
                <Field
                    label="Description"
                    for="innovation-description"
                    :error="form.errors.description"
                >
                    <TextareaInput
                        id="innovation-description"
                        v-model="form.description"
                        class="min-h-32"
                    />
                </Field>
                <Field
                    label="Problem statement"
                    for="innovation-problem"
                    :error="form.errors.problem_statement"
                >
                    <TextareaInput
                        id="innovation-problem"
                        v-model="form.problem_statement"
                    />
                </Field>
                <Field
                    label="Solution"
                    for="innovation-solution"
                    :error="form.errors.solution_description"
                >
                    <TextareaInput
                        id="innovation-solution"
                        v-model="form.solution_description"
                    />
                </Field>
            </FormSection>
            <FormSection title="Highlights" class="mt-5">
                <Field
                    label="Key features"
                    for="innovation-features"
                    :error="form.errors.key_features"
                >
                    <StringListInput
                        id="innovation-features"
                        v-model="form.key_features"
                        placeholder="A main feature"
                        add-label="Add feature"
                        :errors="form.errors"
                        error-key="key_features"
                    />
                </Field>
                <Field
                    label="Benefits"
                    for="innovation-benefits"
                    :error="form.errors.benefits"
                >
                    <StringListInput
                        id="innovation-benefits"
                        v-model="form.benefits"
                        placeholder="A company or client benefit"
                        add-label="Add benefit"
                        :errors="form.errors"
                        error-key="benefits"
                    />
                </Field>
            </FormSection>
        </Panel>

        <Panel title="Lifecycle & visibility">
            <FormSection>
                <Field
                    label="Status"
                    for="innovation-status"
                    :error="form.errors.status"
                    required
                >
                    <SelectInput
                        id="innovation-status"
                        v-model="form.status"
                        :options="options.statuses"
                    />
                </Field>
                <Field
                    label="Visibility"
                    for="innovation-visibility"
                    :error="form.errors.visibility"
                    hint="Public innovations can appear on the showcase."
                    required
                >
                    <SelectInput
                        id="innovation-visibility"
                        v-model="form.visibility"
                        :options="options.visibilities"
                    />
                </Field>
                <Label class="flex items-center gap-2.5 font-normal">
                    <Checkbox v-model="form.is_featured" />
                    Featured — highlight on the showcase
                </Label>
            </FormSection>
        </Panel>

        <div class="flex justify-end gap-2">
            <Button variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">{{
                submitLabel
            }}</Button>
        </div>
    </form>
</template>
