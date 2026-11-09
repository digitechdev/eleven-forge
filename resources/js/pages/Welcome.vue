<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Boxes, Code, Globe, Lightbulb } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/header/ThemeToggle.vue';
import { dashboard, login } from '@/routes';
import showcase from '@/routes/showcase';

const page = usePage();

const modules = [
    {
        icon: Lightbulb,
        title: 'Innovation registry',
        text: 'Software, hardware, and hybrid innovations with their status, problem, and solution.',
    },
    {
        icon: Boxes,
        title: 'Bill of materials',
        text: 'The physical parts and quantities each innovation is built from.',
    },
    {
        icon: Code,
        title: 'Technologies',
        text: 'The software stack behind each innovation, with version and purpose.',
    },
    {
        icon: Globe,
        title: 'Showcase',
        text: 'A public page for the innovations the company chooses to share.',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6"
            >
                <div class="flex items-center gap-2.5">
                    <AppLogo />
                </div>
                <nav class="flex items-center gap-1">
                    <ThemeToggle />
                    <Link
                        :href="showcase.index()"
                        class="rounded-sm px-3 py-2 label-mono text-muted-foreground transition-colors hover:text-foreground"
                        >Showcase</Link
                    >
                    <Link
                        :href="page.props.auth.user ? dashboard() : login()"
                        class="rounded-sm bg-foreground px-3 py-2 label-mono text-background transition-opacity hover:opacity-85"
                    >
                        {{ page.props.auth.user ? 'Dashboard' : 'Sign in' }}
                    </Link>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <section
                class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.15fr_1fr] lg:items-center lg:py-20"
            >
                <div class="page-enter space-y-6">
                    <p class="flex items-center gap-2 label-mono text-primary">
                        <span
                            class="size-1.5 rotate-45 bg-primary"
                            aria-hidden="true"
                        />
                        Innovation registry &amp; inventory knowledge base
                    </p>
                    <h1
                        class="text-4xl leading-[1.05] font-bold text-balance sm:text-6xl"
                    >
                        Every idea the company builds,
                        <span class="text-primary"
                            >and what it is made of.</span
                        >
                    </h1>
                    <p class="max-w-xl text-base text-muted-foreground">
                        Eleven Forge keeps one record per innovation: the
                        problem it solves, the parts that make it, and the
                        technology behind it, so the knowledge stays with the
                        company.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            :href="page.props.auth.user ? dashboard() : login()"
                            class="group inline-flex h-11 items-center gap-2 rounded-sm bg-primary px-5 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90"
                        >
                            {{
                                page.props.auth.user
                                    ? 'Open dashboard'
                                    : 'Sign in to Eleven Forge'
                            }}
                            <ArrowRight
                                class="size-4 transition-transform group-hover:translate-x-1"
                            />
                        </Link>
                        <Link
                            :href="showcase.index()"
                            class="inline-flex h-11 items-center rounded-sm border px-5 text-sm font-semibold transition-colors hover:bg-accent"
                            >View showcase</Link
                        >
                    </div>
                </div>

                <!-- Spec sheet: the core concept of the registry. -->
                <figure
                    class="page-enter relative overflow-hidden rounded-sm border bg-[oklch(0.2_0.01_60)] p-6 text-[oklch(0.88_0.008_70)]"
                    aria-label="How an innovation is recorded"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-blueprint text-[oklch(0.72_0.17_48)] opacity-50"
                        aria-hidden="true"
                    />
                    <div class="relative space-y-5">
                        <div
                            class="flex items-center justify-between border-b border-white/10 pb-3"
                        >
                            <span class="label-mono text-white/50"
                                >Spec sheet · EF-001</span
                            >
                            <span class="label-mono text-[oklch(0.72_0.17_48)]"
                                >Core concept</span
                            >
                        </div>

                        <div
                            class="mx-auto w-fit rounded-sm border border-[oklch(0.72_0.17_48)] px-4 py-2 font-display font-bold text-white"
                        >
                            Innovation
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="space-y-2">
                                <p class="label-mono text-white/50">
                                    Physical side
                                </p>
                                <div
                                    class="rounded-sm border border-white/15 px-3 py-2"
                                >
                                    Inventory items
                                </div>
                                <div
                                    class="rounded-sm border border-dashed border-white/25 px-3 py-2 text-white/70"
                                >
                                    Bill of materials
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="label-mono text-white/50">
                                    Software side
                                </p>
                                <div
                                    class="rounded-sm border border-white/15 px-3 py-2"
                                >
                                    Technologies
                                </div>
                                <div
                                    class="rounded-sm border border-dashed border-white/25 px-3 py-2 text-white/70"
                                >
                                    Version &amp; purpose
                                </div>
                            </div>
                        </div>

                        <p
                            class="border-t border-white/10 pt-3 font-mono text-xs text-white/50"
                        >
                            + images, videos, and a public showcase
                        </p>
                    </div>
                </figure>
            </section>

            <section class="border-t">
                <div
                    class="mx-auto grid w-full max-w-6xl sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div
                        v-for="(module, index) in modules"
                        :key="module.title"
                        class="space-y-3 border-b px-4 py-8 sm:px-6 sm:odd:border-r lg:border-r lg:border-b-0 lg:last:border-r-0"
                    >
                        <div class="flex items-center justify-between">
                            <component
                                :is="module.icon"
                                class="size-5 text-primary"
                            />
                            <span
                                class="font-mono text-xs text-muted-foreground"
                                >{{ String(index + 1).padStart(2, '0') }}</span
                            >
                        </div>
                        <h2 class="text-lg font-bold">{{ module.title }}</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ module.text }}
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-5 sm:px-6"
            >
                <span class="label-mono text-muted-foreground"
                    >Eleven Forge © {{ new Date().getFullYear() }}</span
                >
                <span class="label-mono text-muted-foreground"
                    >Innovation registry</span
                >
            </div>
        </footer>
    </div>
</template>
