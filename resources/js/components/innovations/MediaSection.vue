<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    Eye,
    EyeOff,
    Image as ImageIcon,
    Pencil,
    Plus,
    Star,
    Trash2,
    Video,
} from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import Panel from '@/components/common/Panel.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import TextareaInput from '@/components/common/TextareaInput.vue';
import type { DetailOptions, MediaEntry } from '@/components/innovations/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import gallery from '@/routes/innovations/media';

const props = defineProps<{
    innovationId: number;
    media: MediaEntry[];
    options: DetailOptions | null;
    canUpdate: boolean;
}>();

type MediaForm = {
    media_type: 'IMAGE' | 'VIDEO';
    file: File | null;
    video_url: string | null;
    title: string | null;
    caption: string | null;
    alt_text: string | null;
    is_cover: boolean;
    is_public: boolean;
    sort_order: number | string;
};

const blank = (): MediaForm => ({
    media_type: 'IMAGE',
    file: null,
    video_url: null,
    title: null,
    caption: null,
    alt_text: null,
    is_cover: props.media.length === 0,
    is_public: false,
    sort_order: props.media.length,
});

const dialogOpen = ref(false);
const editing = ref<MediaEntry | null>(null);
const form = useForm<MediaForm>(blank());

const removing = ref<MediaEntry | null>(null);
const removeOpen = ref(false);
const removeProcessing = ref(false);

function openCreate(): void {
    editing.value = null;
    form.defaults(blank());
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(entry: MediaEntry): void {
    editing.value = entry;
    form.defaults({
        media_type: entry.media_type,
        file: null,
        video_url: entry.video_url,
        title: entry.title,
        caption: entry.caption,
        alt_text: entry.alt_text,
        is_cover: entry.is_cover,
        is_public: entry.is_public,
        sort_order: entry.sort_order,
    });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function pickFile(event: Event): void {
    form.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };

    if (editing.value) {
        form.put(
            gallery.update([props.innovationId, editing.value.id]).url,
            options,
        );
    } else {
        form.post(gallery.store(props.innovationId).url, options);
    }
}

function confirmRemove(entry: MediaEntry): void {
    removing.value = entry;
    removeOpen.value = true;
}

function remove(): void {
    if (!removing.value) {
        return;
    }

    router.delete(
        gallery.destroy([props.innovationId, removing.value.id]).url,
        {
            preserveScroll: true,
            onStart: () => (removeProcessing.value = true),
            onFinish: () => {
                removeProcessing.value = false;
                removeOpen.value = false;
            },
        },
    );
}

function label(entry: MediaEntry): string {
    return (
        entry.title ??
        entry.alt_text ??
        (entry.media_type === 'VIDEO' ? 'Video' : 'Image')
    );
}
</script>

<template>
    <Panel
        title="Media"
        :icon="ImageIcon"
        description="Images and videos. Public media can appear on the showcase; the cover image leads the gallery."
    >
        <template v-if="canUpdate" #actions>
            <Button size="sm" @click="openCreate"><Plus /> Add media</Button>
        </template>

        <ul
            v-if="media.length"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <li
                v-for="entry in media"
                :key="entry.id"
                class="overflow-hidden rounded-sm border bg-background"
            >
                <div class="relative aspect-video bg-muted">
                    <img
                        v-if="entry.media_type === 'IMAGE' && entry.url"
                        :src="entry.url"
                        :alt="entry.alt_text ?? ''"
                        class="size-full object-cover"
                        loading="lazy"
                    />
                    <a
                        v-else-if="entry.url"
                        :href="entry.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex size-full flex-col items-center justify-center gap-2 text-muted-foreground hover:text-primary"
                    >
                        <Video class="size-8" />
                        <span class="max-w-[90%] truncate text-xs">{{
                            entry.url
                        }}</span>
                    </a>
                    <div class="absolute top-2 left-2 flex gap-1">
                        <Badge v-if="entry.is_cover"
                            ><Star class="fill-current" /> Cover</Badge
                        >
                        <Badge variant="secondary">
                            <component :is="entry.is_public ? Eye : EyeOff" />
                            {{ entry.is_public ? 'Public' : 'Internal' }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ label(entry) }}
                        </p>
                        <p
                            v-if="entry.caption"
                            class="line-clamp-2 text-xs text-muted-foreground"
                        >
                            {{ entry.caption }}
                        </p>
                    </div>
                    <div v-if="canUpdate" class="flex shrink-0">
                        <Button
                            size="icon-sm"
                            variant="ghost"
                            :aria-label="`Edit ${label(entry)}`"
                            @click="openEdit(entry)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            size="icon-sm"
                            variant="ghost"
                            :aria-label="`Delete ${label(entry)}`"
                            @click="confirmRemove(entry)"
                        >
                            <Trash2 />
                        </Button>
                    </div>
                </div>
            </li>
        </ul>
        <EmptyState
            v-else
            :icon="ImageIcon"
            title="No media yet"
            description="Upload photos or link demo videos of this innovation."
        />
    </Panel>

    <FormDialog
        v-if="canUpdate && options"
        v-model:open="dialogOpen"
        :title="editing ? `Edit ${label(editing)}` : 'Add media'"
        :icon="ImageIcon"
    >
        <form @submit.prevent="submit">
            <FormSection>
                <Field
                    v-if="!editing"
                    label="Media type"
                    for="media-type"
                    :error="form.errors.media_type"
                    required
                >
                    <SelectInput
                        id="media-type"
                        v-model="form.media_type"
                        :options="options.mediaTypes"
                    />
                </Field>
                <Field
                    v-if="form.media_type === 'IMAGE' && !editing"
                    label="Image file"
                    for="media-file"
                    :error="form.errors.file"
                    hint="JPG, PNG, WebP, or GIF up to 5 MB."
                    required
                >
                    <Input
                        id="media-file"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        @change="pickFile"
                    />
                </Field>
                <Field
                    v-if="form.media_type === 'VIDEO'"
                    label="Video URL"
                    for="media-video-url"
                    :error="form.errors.video_url"
                    required
                >
                    <Input
                        id="media-video-url"
                        v-model="form.video_url"
                        type="url"
                        placeholder="https://"
                    />
                </Field>
                <Field
                    label="Title"
                    for="media-title"
                    :error="form.errors.title"
                >
                    <Input id="media-title" v-model="form.title" />
                </Field>
                <Field
                    v-if="form.media_type === 'IMAGE'"
                    label="Alt text"
                    for="media-alt"
                    :error="form.errors.alt_text"
                    required
                >
                    <Input
                        id="media-alt"
                        v-model="form.alt_text"
                        placeholder="What the image shows"
                    />
                </Field>
                <Field
                    label="Caption"
                    for="media-caption"
                    :error="form.errors.caption"
                    class="sm:col-span-2"
                >
                    <TextareaInput id="media-caption" v-model="form.caption" />
                </Field>
                <Field
                    label="Sort order"
                    for="media-sort"
                    :error="form.errors.sort_order"
                >
                    <Input
                        id="media-sort"
                        v-model="form.sort_order"
                        type="number"
                        min="0"
                    />
                </Field>
                <div class="grid content-end gap-2 pb-1">
                    <Label class="flex items-center gap-2.5 font-normal">
                        <Checkbox v-model="form.is_cover" />
                        Cover image
                    </Label>
                    <Label class="flex items-center gap-2.5 font-normal">
                        <Checkbox v-model="form.is_public" />
                        Show on the public showcase
                    </Label>
                </div>
            </FormSection>
            <FormFooter
                :submit-label="editing ? 'Save media' : 'Add media'"
                :processing="form.processing"
                @cancel="dialogOpen = false"
            />
        </form>
    </FormDialog>

    <ConfirmDialog
        v-model:open="removeOpen"
        :title="`Delete ${removing ? label(removing) : 'media'}?`"
        description="Uploaded files are removed from storage. This cannot be undone."
        confirm-label="Delete"
        :processing="removeProcessing"
        @confirm="remove"
    />
</template>
