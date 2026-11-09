<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowRight, CornerDownRight } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Sign in',
        description:
            'Use your company account to open the innovation registry.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    /** One-click sign-in per role; only sent by the server in local and testing environments. */
    demoAccounts: { label: string; url: string }[];
}>();
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-5 border-l-2 border-primary bg-accent px-3 py-2 text-sm font-medium text-accent-foreground"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-2">
            <Label for="email" class="label-mono text-muted-foreground"
                >Email address</Label
            >
            <Input
                id="email"
                type="email"
                name="email"
                required
                v-focus
                :tabindex="1"
                autocomplete="email"
                placeholder="you@company.com"
                class="h-11 rounded-sm"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <Label for="password" class="label-mono text-muted-foreground"
                    >Password</Label
                >
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-xs"
                    :tabindex="5"
                    >Forgot password?</TextLink
                >
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="Your password"
                class="h-11 rounded-sm"
            />
            <InputError :message="errors.password" />
        </div>

        <Label
            for="remember"
            class="flex w-fit cursor-pointer items-center gap-2.5 text-sm font-normal"
        >
            <Checkbox id="remember" name="remember" :tabindex="3" />
            Keep me signed in
        </Label>

        <Button
            type="submit"
            class="group mt-1 h-11 w-full justify-between rounded-sm px-4 text-sm font-semibold"
            :tabindex="4"
            :disabled="processing"
            data-test="login-button"
        >
            {{ processing ? 'Signing in…' : 'Sign in' }}
            <Spinner v-if="processing" />
            <ArrowRight
                v-else
                class="transition-transform group-hover:translate-x-1"
            />
        </Button>

        <p class="text-xs text-muted-foreground">
            Accounts are created by an administrator.
        </p>
    </Form>

    <section
        v-if="demoAccounts.length"
        class="mt-8 border-t border-dashed pt-5"
        aria-label="Demo accounts"
    >
        <div class="mb-3 flex items-center justify-between">
            <p class="label-mono text-muted-foreground">Demo accounts</p>
            <span class="label-mono text-primary">Local only</span>
        </div>
        <div class="divide-y rounded-sm border">
            <Form
                v-for="account in demoAccounts"
                :key="account.url"
                :action="account.url"
                method="post"
                v-slot="{ processing }"
            >
                <button
                    type="submit"
                    :disabled="processing"
                    :data-test="`demo-login-${account.label}`"
                    class="group flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm transition-colors outline-none hover:bg-accent focus-visible:bg-accent disabled:opacity-60"
                >
                    <span class="flex items-center gap-2.5">
                        <CornerDownRight
                            class="size-3.5 text-muted-foreground group-hover:text-primary"
                        />
                        <span class="font-medium">{{ account.label }}</span>
                    </span>
                    <Spinner v-if="processing" class="size-4" />
                    <span
                        v-else
                        class="label-mono text-muted-foreground group-hover:text-primary"
                        >Enter</span
                    >
                </button>
            </Form>
        </div>
    </section>
</template>
