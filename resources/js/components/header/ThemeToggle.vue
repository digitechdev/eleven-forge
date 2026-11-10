<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/composables/useAppearance';

/** One-click light/dark switch. "System" remains available in Settings › Appearance. */
const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');
const label = computed(() =>
    isDark.value ? 'Switch to light mode' : 'Switch to dark mode',
);

function toggle(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <!-- Own provider so the toggle also works on pages without the app sidebar. -->
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <button
                    type="button"
                    :aria-label="label"
                    class="relative inline-flex size-9 items-center justify-center overflow-hidden rounded-lg text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    @click="toggle"
                >
                    <Sun
                        :class="[
                            'absolute size-[18px] motion-safe:transition-all motion-safe:duration-500',
                            isDark
                                ? 'scale-50 rotate-90 opacity-0'
                                : 'scale-100 rotate-0 opacity-100',
                        ]"
                    />
                    <Moon
                        :class="[
                            'absolute size-[18px] motion-safe:transition-all motion-safe:duration-500',
                            isDark
                                ? 'scale-100 rotate-0 opacity-100'
                                : 'scale-50 -rotate-90 opacity-0',
                        ]"
                    />
                </button>
            </TooltipTrigger>
            <TooltipContent side="bottom">{{ label }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
