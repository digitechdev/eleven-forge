<script setup lang="ts">
import Bone from './Bone.vue';
import SkeletonPanel from './SkeletonPanel.vue';
import SkeletonStatCards from './SkeletonStatCards.vue';

export type SkeletonPageVariant =
    | 'dashboard'
    | 'list'
    | 'cards'
    | 'detail'
    | 'hero'
    | 'form';

/** A whole-page placeholder shown while navigating, shaped like the page being opened. */
defineProps<{ variant: SkeletonPageVariant }>();
</script>

<template>
    <div class="space-y-6 p-4 sm:p-6" role="status" aria-label="Loading page">
        <span class="sr-only">Loading…</span>

        <!-- Page header -->
        <div
            v-if="variant !== 'hero'"
            class="flex items-end justify-between gap-4"
        >
            <div class="space-y-2.5">
                <Bone class="h-3 w-24" />
                <Bone class="h-7 w-64" />
                <Bone class="h-3 w-96 max-w-[70vw]" />
            </div>
            <Bone class="hidden h-9 w-36 rounded-sm sm:block" />
        </div>

        <template v-if="variant === 'dashboard'">
            <SkeletonStatCards />
            <div class="grid gap-6 xl:grid-cols-3">
                <SkeletonPanel variant="chart" class="xl:col-span-2" />
                <SkeletonPanel variant="bars" :rows="4" />
            </div>
        </template>

        <template v-else-if="variant === 'list'">
            <div class="flex flex-wrap gap-2">
                <Bone class="h-9 w-72 rounded-md" />
                <Bone class="h-9 w-36 rounded-md" />
                <Bone class="h-9 w-36 rounded-md" />
            </div>
            <SkeletonPanel variant="table" :rows="8" :title="false" />
        </template>

        <template v-else-if="variant === 'cards'">
            <div class="flex gap-2">
                <Bone class="h-9 w-72 rounded-md" />
                <Bone class="h-9 w-36 rounded-md" />
            </div>
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="overflow-hidden rounded-sm border bg-card shadow-xs"
                >
                    <Bone class="h-24 rounded-none" />
                    <div class="space-y-3 p-5">
                        <Bone class="h-3 w-2/3" />
                        <Bone class="h-5 w-1/3" />
                        <Bone class="h-2 w-full rounded-full" />
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="variant === 'hero'">
            <div class="flex justify-end gap-2">
                <Bone class="h-9 w-36 rounded-sm" />
                <Bone class="h-9 w-32 rounded-sm" />
            </div>
            <div class="space-y-4 rounded-3xl bg-slate-900 p-6 sm:p-10">
                <Bone class="h-6 w-48 rounded-full bg-muted" />
                <Bone class="h-11 w-2/3 bg-muted" />
                <Bone class="h-4 w-1/2 bg-muted" />
                <div class="flex gap-3 pt-2">
                    <Bone
                        v-for="i in 3"
                        :key="i"
                        class="h-8 w-36 rounded-sm bg-muted"
                    />
                </div>
            </div>
            <SkeletonStatCards />
            <SkeletonPanel variant="table" :rows="6" />
        </template>

        <template v-else-if="variant === 'detail'">
            <SkeletonStatCards />
            <div class="grid gap-6 xl:grid-cols-3">
                <SkeletonPanel variant="chart" class="xl:col-span-2" />
                <SkeletonPanel variant="list" :rows="4" />
            </div>
        </template>

        <template v-else>
            <div class="mx-auto w-full max-w-4xl space-y-6">
                <div
                    v-for="section in 2"
                    :key="section"
                    class="grid gap-5 rounded-sm border bg-card p-6 shadow-xs md:grid-cols-2"
                >
                    <div
                        v-for="i in section === 1 ? 6 : 3"
                        :key="i"
                        class="space-y-2"
                    >
                        <Bone class="h-3 w-28" />
                        <Bone class="h-9 w-full rounded-md" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <Bone class="h-9 w-24 rounded-md" />
                    <Bone class="h-9 w-36 rounded-md" />
                </div>
            </div>
        </template>
    </div>
</template>
