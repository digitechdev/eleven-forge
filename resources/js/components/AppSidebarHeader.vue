<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import ThemeToggle from '@/components/header/ThemeToggle.vue';
import HeaderUserMenu from '@/components/HeaderUserMenu.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// Lift the header off the page with a shadow only once content scrolls beneath it.
const { y } = useWindowScroll();
const scrolled = computed(() => y.value > 4);

// Re-run the breadcrumb transition when the trail changes, not on every re-render.
const trailKey = computed(() =>
    props.breadcrumbs.map((b) => b.title).join('›'),
);
</script>

<template>
    <header
        :class="[
            'sticky top-0 z-30 flex h-14 shrink-0 items-center gap-2 border-b bg-background/95 px-4 transition-shadow duration-200',
            scrolled ? 'shadow-[0_1px_0_0_var(--border)]' : '',
        ]"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1 rounded-sm" />
            <template v-if="breadcrumbs.length > 0">
                <span
                    class="mx-1 h-4 w-px shrink-0 bg-border"
                    aria-hidden="true"
                />
                <Breadcrumbs :key="trailKey" :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1">
            <ThemeToggle />
            <span
                class="mx-1.5 hidden h-5 w-px bg-border sm:block"
                aria-hidden="true"
            />
            <HeaderUserMenu />
        </div>
    </header>
</template>
