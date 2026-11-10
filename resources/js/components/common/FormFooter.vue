<script setup lang="ts">
import { Button } from '@/components/ui/button';

/** Sticky action bar at the bottom of a FormDialog. Extra buttons go in the default slot. */
defineProps<{ submitLabel: string; processing?: boolean }>();
defineEmits<{ cancel: [] }>();
</script>

<template>
    <div
        class="sticky bottom-0 -mx-6 mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-border/60 bg-background/80 px-6 py-3 backdrop-blur-md"
    >
        <Button type="button" variant="ghost" @click="$emit('cancel')"
            >Cancel</Button
        >
        <slot />
        <Button type="submit" :disabled="processing">{{ submitLabel }}</Button>
    </div>
</template>
