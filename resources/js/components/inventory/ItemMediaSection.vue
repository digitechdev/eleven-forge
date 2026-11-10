<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    Image as ImageIcon,
    Plus,
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatBytes } from '@/lib/format';
import gallery from '@/routes/inventory/items/media';

export type ItemMediaEntry = {
    id: number;
    media_type: 'IMAGE' | 'VIDEO';
    url: string;
    original_name: string;
    mime_type: string;
    size: number;
    sort_order: number;
};

const props = defineProps<{
    itemId: number;
    media: ItemMediaEntry[];
    limit: number;
    canUpdate: boolean;
}>();

const dialogOpen = ref(false);
const form = useForm<{ file: File | null }>({ file: null });

const removing = ref<ItemMediaEntry | null>(null);
const removeOpen = ref(false);
const removeProcessing = ref(false);
const moving = ref(false);

function openUpload(): void {
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function pickFile(event: Event): void {
    form.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit(): void {
    form.post(gallery.store(props.itemId).url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (dialogOpen.value = false),
    });
}

function move(entry: ItemMediaEntry, position: number): void {
    router.put(
        gallery.update([props.itemId, entry.id]).url,
        { sort_order: position },
        {
            preserveScroll: true,
            onStart: () => (moving.value = true),
            onFinish: () => (moving.value = false),
        },
    );
}

function confirmRemove(entry: ItemMediaEntry): void {
    removing.value = entry;
    removeOpen.value = true;
}

function remove(): void {
    if (!removing.value) {
        return;
    }

    router.delete(gallery.destroy([props.itemId, removing.value.id]).url, {
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
        title="Media"
        :icon="ImageIcon"
        :description="`Photos and videos of this item · ${media.length} of ${limit} files.`"
    >
        <template v-if="canUpdate" #actions>
            <Button
                size="sm"
                :disabled="media.length >= limit"
                @click="openUpload"
            >
                <Plus /> Upload
            </Button>
        </template>

        <ul
            v-if="media.length"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <li
                v-for="(entry, index) in media"
                :key="entry.id"
                class="overflow-hidden rounded-sm border bg-background"
            >
                <div class="relative aspect-video bg-muted">
                    <img
                        v-if="entry.media_type === 'IMAGE'"
                        :src="entry.url"
                        :alt="entry.original_name"
                        class="size-full object-cover"
                        loading="lazy"
                    />
                    <video
                        v-else
                        :src="entry.url"
                        class="size-full object-cover"
                        controls
                        preload="metadata"
                    />
                    <div class="absolute top-2 left-2 flex gap-1">
                        <Badge variant="secondary">
                            <component
                                :is="
                                    entry.media_type === 'VIDEO'
                                        ? Video
                                        : ImageIcon
                                "
                            />
                            {{
                                entry.media_type === 'VIDEO' ? 'Video' : 'Image'
                            }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ entry.original_name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatBytes(entry.size) }}
                        </p>
                    </div>
                    <div v-if="canUpdate" class="flex shrink-0">
                        <Button
                            size="icon-sm"
                            variant="ghost"
                            :aria-label="`Move ${entry.original_name} earlier`"
                            :disabled="index === 0 || moving"
                            @click="move(entry, index - 1)"
                        >
                            <ArrowUp />
                        </Button>
                        <Button
                            size="icon-sm"
                            variant="ghost"
                            :aria-label="`Move ${entry.original_name} later`"
                            :disabled="index === media.length - 1 || moving"
                            @click="move(entry, index + 1)"
                        >
                            <ArrowDown />
                        </Button>
                        <Button
                            size="icon-sm"
                            variant="ghost"
                            :aria-label="`Delete ${entry.original_name}`"
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
            description="Upload photos or short videos of this item."
        />
    </Panel>

    <FormDialog
        v-if="canUpdate"
        v-model:open="dialogOpen"
        title="Upload media"
        :icon="ImageIcon"
    >
        <form @submit.prevent="submit">
            <FormSection :columns="1">
                <Field
                    label="File"
                    for="item-media-file"
                    :error="form.errors.file"
                    hint="JPEG, PNG, or WebP images up to 10 MB; MP4 or WebM videos up to 100 MB."
                    required
                >
                    <Input
                        id="item-media-file"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                        @change="pickFile"
                    />
                </Field>
            </FormSection>
            <FormFooter
                submit-label="Upload"
                :processing="form.processing"
                @cancel="dialogOpen = false"
            />
        </form>
    </FormDialog>

    <ConfirmDialog
        v-model:open="removeOpen"
        :title="`Delete ${removing?.original_name ?? 'media'}?`"
        description="The file is removed from storage. This cannot be undone."
        confirm-label="Delete"
        :processing="removeProcessing"
        @confirm="remove"
    />
</template>
