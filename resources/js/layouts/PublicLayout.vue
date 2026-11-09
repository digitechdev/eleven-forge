<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/header/ThemeToggle.vue';
import { dashboard, home, login } from '@/routes';

const page = usePage();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <header class="border-b">
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6"
            >
                <Link :href="home()" class="flex items-center gap-2.5">
                    <AppLogo />
                </Link>
                <nav class="flex items-center gap-1">
                    <ThemeToggle />
                    <Link
                        :href="page.props.auth.user ? dashboard() : login()"
                        class="rounded-sm bg-foreground px-3 py-2 label-mono text-background transition-opacity hover:opacity-85"
                    >
                        {{ page.props.auth.user ? 'Dashboard' : 'Sign in' }}
                    </Link>
                </nav>
            </div>
        </header>
        <main
            class="page-enter mx-auto w-full max-w-6xl flex-1 px-4 py-10 sm:px-6"
        >
            <slot />
        </main>
    </div>
</template>
