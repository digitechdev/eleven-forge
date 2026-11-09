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
            class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-sidebar-primary text-sidebar-primary-foreground"
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
