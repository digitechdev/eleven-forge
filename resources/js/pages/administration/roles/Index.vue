<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { IdCard, KeyRound, Pencil, Plus, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/common/Field.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Panel from '@/components/common/Panel.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import administration from '@/routes/administration';
import { index as rolesIndexCrumb } from '@/routes/administration/roles';
import { index as usersIndexCrumb } from '@/routes/administration/users';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([
    { title: 'Administration', href: usersIndexCrumb() },
    { title: 'Roles', href: rolesIndexCrumb() },
]);

type Role = {
    id: number;
    name: string;
    label: string;
    isProtected: boolean;
    usersCount: number;
    permissionIds: number[];
    permissionNames: string[];
};

type Permission = { id: number; name: string };

const props = defineProps<{ roles: Role[]; permissions: Permission[] }>();

const createForm = useForm({ name: '' });
const permissionsForm = useForm<{ permissions: number[] }>({
    permissions: [],
});
const editingRoleId = ref<number | null>(null);

const editingRole = computed(
    () => props.roles.find((role) => role.id === editingRoleId.value) ?? null,
);

/** First validation message for the permission list, including per-item errors. */
const permissionsError = computed(() => {
    const errors = permissionsForm.errors as Record<string, string | undefined>;

    return (
        errors.permissions ??
        Object.entries(errors).find(([key]) =>
            key.startsWith('permissions.'),
        )?.[1]
    );
});

function createRole(): void {
    createForm.post(administration.roles.store().url, {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function editRole(role: Role): void {
    editingRoleId.value = role.id;
    permissionsForm.clearErrors();
    permissionsForm.permissions = [...role.permissionIds];
}

function togglePermission(
    id: number,
    checked: boolean | 'indeterminate',
): void {
    permissionsForm.permissions =
        checked === true
            ? [...permissionsForm.permissions, id]
            : permissionsForm.permissions.filter((value) => value !== id);
}

function savePermissions(): void {
    if (editingRoleId.value === null) {
        return;
    }

    permissionsForm.put(administration.roles.update(editingRoleId.value).url, {
        preserveScroll: true,
        onSuccess: () => cancelEditing(),
    });
}

function cancelEditing(): void {
    editingRoleId.value = null;
    permissionsForm.reset();
    permissionsForm.clearErrors();
}
</script>

<template>
    <Head title="Roles" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Roles"
            description="Create roles and choose the permissions each role receives."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="administration.permissions.index()">
                        <KeyRound /> Permissions
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <Panel>
            <form
                class="flex flex-wrap items-end gap-3"
                @submit.prevent="createRole"
            >
                <div class="min-w-56 flex-1">
                    <Field
                        label="New role"
                        for="role-name"
                        :error="createForm.errors.name"
                    >
                        <Input
                            id="role-name"
                            v-model="createForm.name"
                            placeholder="e.g. Project Reviewer"
                        />
                    </Field>
                </div>
                <Button type="submit" :disabled="createForm.processing">
                    <Plus /> Create role
                </Button>
            </form>
        </Panel>

        <Panel
            v-if="editingRole"
            :title="`Edit ${editingRole.label} permissions`"
            description="Changes take effect for users assigned to this role when saved."
            :icon="IdCard"
        >
            <form class="space-y-5" @submit.prevent="savePermissions">
                <div class="grid gap-3 sm:grid-cols-2">
                    <Label
                        v-for="permission in permissions"
                        :key="permission.id"
                        class="flex cursor-pointer items-center gap-2.5 font-normal"
                    >
                        <Checkbox
                            :model-value="
                                permissionsForm.permissions.includes(
                                    permission.id,
                                )
                            "
                            @update:model-value="
                                togglePermission(permission.id, $event)
                            "
                        />
                        {{ permission.name }}
                    </Label>
                </div>
                <InputError :message="permissionsError" />
                <div class="flex gap-2">
                    <Button type="submit" :disabled="permissionsForm.processing"
                        >Save permissions</Button
                    >
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelEditing"
                        >Cancel</Button
                    >
                </div>
            </form>
        </Panel>

        <div class="space-y-3">
            <Panel v-for="role in roles" :key="role.id">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold">{{ role.label }}</h2>
                            <Badge v-if="role.isProtected" variant="secondary"
                                >Protected</Badge
                            >
                        </div>
                        <Link
                            :href="
                                administration.users.index({
                                    query: { role: role.name },
                                })
                            "
                            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                        >
                            <Users class="size-3.5" />
                            {{ role.usersCount }}
                            {{ role.usersCount === 1 ? 'user' : 'users' }}
                        </Link>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="name in role.permissionNames"
                                :key="name"
                                variant="outline"
                                class="rounded-sm font-mono font-normal"
                                >{{ name }}</Badge
                            >
                            <p
                                v-if="role.permissionNames.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                No permissions assigned
                            </p>
                        </div>
                    </div>
                    <Button
                        v-if="!role.isProtected"
                        size="sm"
                        variant="outline"
                        @click="editRole(role)"
                    >
                        <Pencil /> Edit permissions
                    </Button>
                </div>
            </Panel>
        </div>
    </div>
</template>
