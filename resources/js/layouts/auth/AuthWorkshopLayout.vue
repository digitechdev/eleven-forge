<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

/**
 * Every sign-in page: a graphite workshop wall with a blueprint grid and the form on a single sheet.
 */
defineProps<{
    title?: string;
    description?: string;
}>();

const modules = [
    'Innovations',
    'Bill of materials',
    'Technologies',
    'Showcase',
];
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col bg-[oklch(0.2_0.01_60)] text-[oklch(0.88_0.008_70)]"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-blueprint text-[oklch(0.72_0.17_48)] opacity-60"
            aria-hidden="true"
        />

        <header
            class="relative flex items-center justify-between border-b border-white/10 px-6 py-4"
        >
            <Link :href="home()" class="flex items-center gap-3">
                <span
                    class="flex size-8 items-center justify-center rounded-sm bg-[oklch(0.72_0.17_48)] text-[oklch(0.18_0.02_45)]"
                >
                    <AppLogoIcon class="size-5 fill-current" />
                </span>
                <span class="font-display text-base font-bold text-white"
                    >Eleven Forge</span
                >
            </Link>
            <span class="hidden label-mono text-white/40 sm:block"
                >Innovation registry · Sign-in</span
            >
        </header>

        <main
            class="relative flex flex-1 items-center justify-center px-4 py-10"
        >
            <div
                class="page-enter w-full max-w-md rounded-sm border border-white/10 bg-background text-foreground shadow-[0_24px_60px_-20px_rgb(0_0_0/0.6)]"
            >
                <div class="h-1 bg-primary" aria-hidden="true" />
                <div class="p-7 sm:p-8">
                    <div class="mb-7 space-y-1.5">
                        <h1 class="text-2xl font-bold">{{ title }}</h1>
                        <p
                            v-if="description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>

                    <slot />
                </div>
            </div>
        </main>

        <footer
            class="relative flex flex-wrap items-center justify-center gap-x-6 gap-y-2 border-t border-white/10 px-6 py-4"
        >
            <span
                v-for="(module, index) in modules"
                :key="module"
                class="label-mono text-white/40"
            >
                <span class="text-[oklch(0.72_0.17_48)]">{{
                    String(index + 1).padStart(2, '0')
                }}</span>
                {{ module }}
            </span>
        </footer>
    </div>
</template>
