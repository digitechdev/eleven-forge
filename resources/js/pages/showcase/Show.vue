<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, ExternalLink, Star } from '@lucide/vue';
import { computed } from 'vue';
import showcase from '@/routes/showcase';

type ShowcaseMedia = {
    id: number;
    type: 'image' | 'video';
    title: string | null;
    caption: string | null;
    alt: string;
    url: string;
    embed_url: string | null;
};

type ShowcaseInnovationDetail = {
    slug: string;
    name: string;
    tagline: string | null;
    short_description: string | null;
    description: string | null;
    problem_statement: string | null;
    solution_description: string | null;
    key_features: string[];
    benefits: string[];
    type: string;
    status: string;
    category: { name: string; slug: string } | null;
    is_featured: boolean;
    technologies: string[];
    media: ShowcaseMedia[];
};

const props = defineProps<{ innovation: ShowcaseInnovationDetail }>();

const images = computed(() =>
    props.innovation.media.filter((media) => media.type === 'image'),
);
const videos = computed(() =>
    props.innovation.media.filter((media) => media.type === 'video'),
);
const cover = computed(() => images.value[0] ?? null);
const gallery = computed(() => images.value.slice(1));

const facts = computed(() =>
    [
        { label: 'Category', value: props.innovation.category?.name },
        { label: 'Type', value: props.innovation.type },
        { label: 'Status', value: props.innovation.status },
    ].filter((fact) => fact.value),
);
</script>

<template>
    <Head :title="innovation.name" />

    <article class="space-y-10">
        <div class="space-y-6">
            <Link
                :href="showcase.index()"
                class="inline-flex items-center gap-1.5 label-mono text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-3.5" /> All innovations
            </Link>

            <header class="space-y-4 border-b pb-6">
                <p class="flex items-center gap-2 label-mono text-primary">
                    <span
                        class="size-1.5 rotate-45 bg-primary"
                        aria-hidden="true"
                    />
                    {{ innovation.category?.name ?? 'Innovation' }}
                    <span
                        v-if="innovation.is_featured"
                        class="ml-2 inline-flex items-center gap-1 rounded-sm bg-primary px-2 py-0.5 text-primary-foreground"
                    >
                        <Star class="size-3" /> Featured
                    </span>
                </p>
                <h1
                    class="text-4xl leading-[1.05] font-bold text-balance sm:text-5xl"
                >
                    {{ innovation.name }}
                </h1>
                <p
                    v-if="innovation.tagline"
                    class="max-w-3xl text-lg text-muted-foreground"
                >
                    {{ innovation.tagline }}
                </p>
                <dl class="flex flex-wrap gap-x-8 gap-y-2">
                    <div v-for="fact in facts" :key="fact.label">
                        <dt class="label-mono text-muted-foreground">
                            {{ fact.label }}
                        </dt>
                        <dd class="font-semibold">{{ fact.value }}</dd>
                    </div>
                </dl>
            </header>
        </div>

        <figure v-if="cover" class="overflow-hidden rounded-sm border bg-muted">
            <img
                :src="cover.url"
                :alt="cover.alt"
                class="max-h-[32rem] w-full object-cover"
            />
            <figcaption
                v-if="cover.caption"
                class="border-t px-4 py-2 text-sm text-muted-foreground"
            >
                {{ cover.caption }}
            </figcaption>
        </figure>

        <div class="grid gap-10 lg:grid-cols-[1fr_18rem]">
            <div class="space-y-8">
                <section
                    v-if="
                        innovation.description || innovation.short_description
                    "
                    class="space-y-2"
                >
                    <h2 class="text-xl font-bold">Overview</h2>
                    <p class="whitespace-pre-line text-muted-foreground">
                        {{
                            innovation.description ??
                            innovation.short_description
                        }}
                    </p>
                </section>

                <div
                    v-if="
                        innovation.problem_statement ||
                        innovation.solution_description
                    "
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <section
                        v-if="innovation.problem_statement"
                        class="space-y-2 rounded-xl border border-border/70 bg-card p-5 shadow-soft"
                    >
                        <h2 class="label-mono text-muted-foreground">
                            The problem
                        </h2>
                        <p class="whitespace-pre-line">
                            {{ innovation.problem_statement }}
                        </p>
                    </section>
                    <section
                        v-if="innovation.solution_description"
                        class="space-y-2 rounded-sm border border-primary/40 bg-card p-5"
                    >
                        <h2 class="label-mono text-primary">Our solution</h2>
                        <p class="whitespace-pre-line">
                            {{ innovation.solution_description }}
                        </p>
                    </section>
                </div>

                <section
                    v-if="innovation.key_features.length"
                    class="space-y-3"
                >
                    <h2 class="text-xl font-bold">Key features</h2>
                    <ul class="grid gap-2 sm:grid-cols-2">
                        <li
                            v-for="feature in innovation.key_features"
                            :key="feature"
                            class="flex items-start gap-2"
                        >
                            <Check
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            {{ feature }}
                        </li>
                    </ul>
                </section>

                <section v-if="innovation.benefits.length" class="space-y-3">
                    <h2 class="text-xl font-bold">Benefits</h2>
                    <ul class="grid gap-2 sm:grid-cols-2">
                        <li
                            v-for="benefit in innovation.benefits"
                            :key="benefit"
                            class="flex items-start gap-2"
                        >
                            <Check
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            {{ benefit }}
                        </li>
                    </ul>
                </section>

                <section v-if="videos.length" class="space-y-3">
                    <h2 class="text-xl font-bold">Videos</h2>
                    <div class="grid gap-4">
                        <figure
                            v-for="video in videos"
                            :key="video.id"
                            class="overflow-hidden rounded-xl border border-border/70 bg-card shadow-soft"
                        >
                            <div
                                v-if="video.embed_url"
                                class="aspect-video bg-black"
                            >
                                <iframe
                                    :src="video.embed_url"
                                    :title="video.title ?? video.alt"
                                    class="size-full"
                                    loading="lazy"
                                    allow="
                                        accelerometer;
                                        encrypted-media;
                                        gyroscope;
                                        picture-in-picture;
                                        fullscreen;
                                    "
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                />
                            </div>
                            <figcaption
                                class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                            >
                                <span class="min-w-0">
                                    <span class="block font-medium">{{
                                        video.title ?? 'Video'
                                    }}</span>
                                    <span
                                        v-if="video.caption"
                                        class="block text-muted-foreground"
                                        >{{ video.caption }}</span
                                    >
                                </span>
                                <a
                                    :href="video.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex shrink-0 items-center gap-1 text-primary hover:underline"
                                >
                                    Watch <ExternalLink class="size-3.5" />
                                </a>
                            </figcaption>
                        </figure>
                    </div>
                </section>

                <section v-if="gallery.length" class="space-y-3">
                    <h2 class="text-xl font-bold">Gallery</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <figure
                            v-for="image in gallery"
                            :key="image.id"
                            class="overflow-hidden rounded-xl border border-border/70 bg-card shadow-soft"
                        >
                            <a
                                :href="image.url"
                                target="_blank"
                                rel="noopener"
                                class="block aspect-[4/3] bg-muted"
                            >
                                <img
                                    :src="image.url"
                                    :alt="image.alt"
                                    loading="lazy"
                                    class="size-full object-cover"
                                />
                            </a>
                            <figcaption
                                v-if="image.title || image.caption"
                                class="px-4 py-2 text-sm"
                            >
                                <span
                                    v-if="image.title"
                                    class="block font-medium"
                                    >{{ image.title }}</span
                                >
                                <span
                                    v-if="image.caption"
                                    class="block text-muted-foreground"
                                    >{{ image.caption }}</span
                                >
                            </figcaption>
                        </figure>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                <div
                    v-if="innovation.technologies.length"
                    class="space-y-3 rounded-xl border border-border/70 bg-card p-5 shadow-soft"
                >
                    <h2 class="label-mono text-muted-foreground">Built with</h2>
                    <ul class="flex flex-wrap gap-2">
                        <li
                            v-for="technology in innovation.technologies"
                            :key="technology"
                            class="rounded-sm border px-2 py-1 font-mono text-xs"
                        >
                            {{ technology }}
                        </li>
                    </ul>
                </div>
                <Link
                    v-if="innovation.category"
                    :href="
                        showcase.index({
                            query: { category: innovation.category.slug },
                        })
                    "
                    class="block hover-lift rounded-xl border border-border/70 bg-card p-5 text-sm shadow-soft"
                >
                    <span class="label-mono text-muted-foreground"
                        >More in</span
                    >
                    <span class="mt-1 block font-semibold">{{
                        innovation.category.name
                    }}</span>
                </Link>
            </aside>
        </div>
    </article>
</template>
