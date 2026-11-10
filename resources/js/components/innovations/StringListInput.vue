<script setup lang="ts">
import { Plus, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

/** A repeatable list of short text entries (e.g. key features or benefits). */
const props = defineProps<{
    id?: string;
    placeholder?: string;
    addLabel?: string;
    errors?: Partial<Record<string, string>>;
    errorKey?: string;
}>();

const model = defineModel<string[]>({ required: true });

function add(): void {
    model.value = [...model.value, ''];
}

function remove(index: number): void {
    model.value = model.value.filter((_, i) => i !== index);
}

function update(index: number, value: string | number | null): void {
    model.value = model.value.map((entry, i) =>
        i === index ? String(value ?? '') : entry,
    );
}

function entryError(index: number): string | undefined {
    return props.errorKey
        ? props.errors?.[`${props.errorKey}.${index}`]
        : undefined;
}
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="(entry, index) in model"
            :key="index"
            class="flex items-start gap-2"
        >
            <span
                class="mt-2.5 font-mono text-xs text-muted-foreground tabular-nums"
                >{{ String(index + 1).padStart(2, '0') }}</span
            >
            <div class="min-w-0 flex-1">
                <Input
                    :id="index === 0 ? id : undefined"
                    :model-value="entry"
                    :placeholder="placeholder"
                    @update:model-value="update(index, $event)"
                />
                <p
                    v-if="entryError(index)"
                    class="mt-1 text-sm text-destructive"
                >
                    {{ entryError(index) }}
                </p>
            </div>
            <Button
                type="button"
                size="icon"
                variant="ghost"
                :aria-label="`Remove entry ${index + 1}`"
                @click="remove(index)"
            >
                <X />
            </Button>
        </div>
        <Button type="button" variant="outline" size="sm" @click="add">
            <Plus /> {{ addLabel ?? 'Add entry' }}
        </Button>
    </div>
</template>
