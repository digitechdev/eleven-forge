<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import SkeletonPage from '@/components/skeletons/SkeletonPage.vue';
import type { SkeletonPageVariant } from '@/components/skeletons/SkeletonPage.vue';
import { Toaster } from '@/components/ui/sonner';
import { pendingPath } from '@/lib/navigation';
import { skeletonVariantFor } from '@/lib/skeletonVariant';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

// Re-run the enter animation when the path changes, but not for filter/query changes on the same page.
const pageKey = computed(() => page.url.split('?')[0]);

// While a visit is in flight (and only once it takes long enough to notice):
// - opening another page shows a skeleton shaped like that page;
// - reloading the same page (filters, pagination, saves) just dims the current content.
const loading = ref(false);
const skeleton = ref<SkeletonPageVariant | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;

const offStart = router.on('start', (event) => {
    const visit = event.detail.visit;

    // Prefetches, deferred-prop loads (async) and partial reloads show their own loading UI.
    if (visit.prefetch || visit.async || visit.only.length > 0) {
        return;
    }

    const target = visit.url.pathname;
    const isNewPage =
        visit.method === 'get' && target !== window.location.pathname;

    pendingPath.value = isNewPage ? target : null;

    clearTimeout(timer);
    timer = setTimeout(() => {
        loading.value = true;
        skeleton.value = isNewPage ? skeletonVariantFor(target) : null;
    }, 150);
});

const offFinish = router.on('finish', () => {
    pendingPath.value = null;
    clearTimeout(timer);
    loading.value = false;
    skeleton.value = null;
});

onBeforeUnmount(() => {
    offStart();
    offFinish();
    clearTimeout(timer);
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="skeleton ? [] : breadcrumbs" />
            <SkeletonPage
                v-if="skeleton"
                :variant="skeleton"
                class="page-enter"
            />
            <div
                v-show="!skeleton"
                :key="pageKey"
                :aria-busy="loading"
                :class="[
                    'page-enter flex flex-1 flex-col transition-opacity duration-200',
                    loading ? 'opacity-60' : 'opacity-100',
                ]"
            >
                <slot />
            </div>
        </AppContent>
        <Toaster rich-colors close-button position="top-right" :offset="72" />
    </AppShell>
</template>
