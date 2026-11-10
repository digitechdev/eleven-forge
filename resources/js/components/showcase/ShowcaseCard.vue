<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Lightbulb, Star } from '@lucide/vue';
import showcase from '@/routes/showcase';

export type ShowcaseInnovation = {
    slug: string;
    name: string;
    tagline: string | null;
    short_description: string | null;
    type: string;
    status: string;
    category: string | null;
    is_featured: boolean;
    cover: { url: string; alt: string } | null;
};

defineProps<{ innovation: ShowcaseInnovation }>();
</script>

<template>
    <Link
        :href="showcase.show(innovation.slug)"
        class="group flex hover-lift flex-col overflow-hidden rounded-xl border border-border/70 bg-card shadow-soft"
    >
        <div class="relative aspect-[16/10] overflow-hidden bg-muted">
            <img
                v-if="innovation.cover"
                :src="innovation.cover.url"
                :alt="innovation.cover.alt"
                loading="lazy"
                class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
            />
            <div
                v-else
                class="flex size-full items-center justify-center bg-[oklch(0.2_0.01_60)]"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-blueprint text-[oklch(0.72_0.17_48)] opacity-40"
                    aria-hidden="true"
                />
                <Lightbulb
                    class="relative size-10 text-[oklch(0.72_0.17_48)]"
                    aria-hidden="true"
                />
            </div>
            <span
                v-if="innovation.is_featured"
                class="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full bg-primary/90 px-2.5 py-1 label-mono text-primary-foreground shadow-glow backdrop-blur"
            >
                <Star class="size-3" /> Featured
            </span>
        </div>
        <div class="flex flex-1 flex-col gap-2 p-5">
            <p class="label-mono text-muted-foreground">
                {{ innovation.category ?? 'Innovation' }} ·
                {{ innovation.type }}
            </p>
            <h2
                class="flex items-start justify-between gap-2 text-lg leading-tight font-bold"
            >
                {{ innovation.name }}
                <ArrowUpRight
                    class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-primary"
                />
            </h2>
            <p
                v-if="innovation.tagline || innovation.short_description"
                class="line-clamp-3 text-sm text-muted-foreground"
            >
                {{ innovation.tagline ?? innovation.short_description }}
            </p>
            <p class="mt-auto pt-2 font-mono text-xs text-muted-foreground">
                {{ innovation.status }}
            </p>
        </div>
    </Link>
</template>
