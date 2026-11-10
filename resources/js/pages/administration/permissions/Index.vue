<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { IdCard, Plus } from '@lucide/vue';
import Field from '@/components/common/Field.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Panel from '@/components/common/Panel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import administration from '@/routes/administration';
import { index as permissionsIndexCrumb } from '@/routes/administration/permissions';
import { index as usersIndexCrumb } from '@/routes/administration/users';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([
    { title: 'Administration', href: usersIndexCrumb() },
    { title: 'Permissions', href: permissionsIndexCrumb() },
]);

type Permission = {
    id: number;
    name: string;
    description: string | null;
    roles: string[];
};

defineProps<{ permissions: Permission[] }>();

const form = useForm({ name: '' });

function createPermission(): void {
    form.post(administration.permissions.store().url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Permissions" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Permissions"
            description="Permissions can be granted to roles from the Roles page."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="administration.roles.index()">
                        <IdCard /> Roles
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <Panel>
            <form
                class="flex flex-wrap items-end gap-3"
                @submit.prevent="createPermission"
            >
                <div class="min-w-56 flex-1">
                    <Field
                        label="New permission"
                        for="permission-name"
                        :error="form.errors.name"
                        hint="Use lowercase names such as reports.view. Super Admin receives new permissions automatically."
                    >
                        <Input
                            id="permission-name"
                            v-model="form.name"
                            placeholder="e.g. reports.view"
                        />
                    </Field>
                </div>
                <Button type="submit" :disabled="form.processing">
                    <Plus /> Create permission
                </Button>
            </form>
        </Panel>

        <div class="space-y-3">
            <Panel v-for="permission in permissions" :key="permission.id">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        <h2 class="font-mono text-base font-bold">
                            {{ permission.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{
                                permission.description ??
                                'Custom permission. Connect it to a policy before granting it.'
                            }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="role in permission.roles"
                            :key="role"
                            variant="outline"
                            class="rounded-sm"
                            >{{ role }}</Badge
                        >
                        <p
                            v-if="permission.roles.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No roles assigned
                        </p>
                    </div>
                </div>
            </Panel>
        </div>
    </div>
</template>
