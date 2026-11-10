<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/** A small confirmation modal for destructive or irreversible actions. */
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        confirmLabel?: string;
        destructive?: boolean;
        processing?: boolean;
    }>(),
    { confirmLabel: 'Confirm', destructive: true },
);

const open = defineModel<boolean>('open', { required: true });

defineEmits<{ confirm: [] }>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{
                    description
                }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="ghost" @click="open = false">Cancel</Button>
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    :disabled="processing"
                    @click="$emit('confirm')"
                    >{{ confirmLabel }}</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
