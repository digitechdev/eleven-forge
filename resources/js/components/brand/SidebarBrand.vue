<script setup lang="ts">
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSidebar } from '@/components/ui/sidebar';

const { state, isMobile } = useSidebar();

const collapsed = computed(
    () => state.value === 'collapsed' && !isMobile.value,
);
</script>

<template>
    <div
        :class="[
            'flex h-12 items-center gap-3',
            collapsed ? 'justify-center' : 'px-1.5',
        ]"
    >
        <span
            class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-sidebar-primary to-[oklch(0.62_0.19_35)] text-sidebar-primary-foreground shadow-[0_4px_14px_-4px_var(--sidebar-primary)]"
        >
            <AppLogoIcon class="size-5 fill-current" />
        </span>
        <span v-if="!collapsed" class="leading-none">
            <span
                class="block font-display text-[15px] font-bold tracking-tight text-sidebar-accent-foreground"
                >Eleven Forge</span
            >
            <span class="mt-1 block label-mono text-sidebar-foreground/45"
                >Innovation registry</span
            >
        </span>
    </div>
</template>
