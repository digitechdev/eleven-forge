<script setup lang="ts">
import type { Component } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/**
 * The standard modal for every create/edit form: a fixed header, a scrolling body,
 * and (via FormFooter inside the form) a sticky action bar.
 */
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: Component;
        size?: 'md' | 'lg' | 'xl';
    }>(),
    { size: 'lg' },
);

const open = defineModel<boolean>('open', { required: true });

const widths = { md: 'sm:max-w-lg', lg: 'sm:max-w-2xl', xl: 'sm:max-w-3xl' };
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            :class="[
                'flex max-h-[92vh] flex-col gap-0 overflow-hidden p-0',
                widths[size],
            ]"
        >
            <DialogHeader
                class="shrink-0 border-b border-border/60 bg-gradient-to-b from-primary/5 to-transparent px-6 py-4 text-left"
            >
                <DialogTitle class="flex items-center gap-2.5 text-lg">
                    <span
                        v-if="icon"
                        class="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15"
                    >
                        <component :is="icon" class="size-4" />
                    </span>
                    {{ title }}
                </DialogTitle>
                <DialogDescription v-if="description">{{
                    description
                }}</DialogDescription>
            </DialogHeader>
            <div class="min-h-0 flex-1 overflow-y-auto px-6 pt-5">
                <slot />
            </div>
        </DialogContent>
    </Dialog>
</template>
