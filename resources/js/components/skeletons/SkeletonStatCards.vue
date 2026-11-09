<script setup lang="ts">
import Bone from './Bone.vue';

withDefaults(defineProps<{ count?: number }>(), { count: 4 });
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="i in count"
            :key="i"
            class="rounded-sm border bg-card p-5 shadow-xs"
        >
            <div class="mb-4 flex items-start justify-between">
                <Bone class="h-3 w-24" />
                <Bone class="size-10 rounded-sm" />
            </div>
            <Bone class="h-8 w-28" />
            <Bone class="mt-4 h-2 w-full rounded-full" />
        </div>
    </div>
</template>
