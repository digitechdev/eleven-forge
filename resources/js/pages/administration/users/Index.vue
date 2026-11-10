<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    KeyRound,
    Pencil,
    Plus,
    Power,
    PowerOff,
    Search,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Field from '@/components/common/Field.vue';
import FormDialog from '@/components/common/FormDialog.vue';
import FormFooter from '@/components/common/FormFooter.vue';
import FormSection from '@/components/common/FormSection.vue';
import PageHeader from '@/components/common/PageHeader.vue';
import Pagination from '@/components/common/Pagination.vue';
import Panel from '@/components/common/Panel.vue';
import SelectInput from '@/components/common/SelectInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFilters } from '@/composables/useFilters';
import { formatDate } from '@/lib/format';
import administration from '@/routes/administration';
import type { Paginated } from '@/types';
import { index as usersIndexCrumb } from '@/routes/administration/users';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

useBreadcrumbs([
    { title: 'Administration', href: usersIndexCrumb() },
    { title: 'Users', href: usersIndexCrumb() },
]);

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    role_label: string | null;
    status: string;
    status_label: string;
    innovations_count: number;
    updated_at: string | null;
    is_self: boolean;
    can: { update: boolean; toggleStatus: boolean; resetPassword: boolean };
};

type RoleOption = {
    value: string;
    label: string;
    assignable: boolean;
    permissions: string[];
};

type Option = { value: string; label: string };

const props = defineProps<{
    users: Paginated<ManagedUser>;
    filters: { search?: string; role?: string; status?: string };
    roles: RoleOption[];
    statuses: Option[];
}>();

const { filters } = useFilters(
    {
        search: props.filters.search ?? null,
        role: props.filters.role ?? null,
        status: props.filters.status ?? null,
    },
    () => administration.users.index().url,
);

const dialogOpen = ref(false);
const editing = ref<ManagedUser | null>(null);

const blankForm = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'VIEWER',
    status: 'ACTIVE',
};

const form = useForm({ ...blankForm });

/** Roles the current user may pick; a user's existing role always stays selectable. */
const roleOptions = computed<Option[]>(() =>
    props.roles
        .filter((role) => role.assignable || role.value === editing.value?.role)
        .map(({ value, label }) => ({ value, label })),
);

const selectedRolePermissions = computed(
    () => props.roles.find((role) => role.value === form.role)?.permissions,
);

const isEditingSelf = computed(() => editing.value?.is_self === true);

const toggling = ref<ManagedUser | null>(null);
const toggleOpen = ref(false);
const toggleProcessing = ref(false);

function openCreate(): void {
    editing.value = null;
    form.defaults({ ...blankForm });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(user: ManagedUser): void {
    editing.value = user;
    form.defaults({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        role: user.role ?? 'VIEWER',
        status: user.status,
    });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
        onFinish: () => form.reset('password', 'password_confirmation'),
    };

    if (editing.value) {
        form.put(administration.users.update(editing.value.id).url, options);
    } else {
        form.post(administration.users.store().url, options);
    }
}

const resetting = ref<ManagedUser | null>(null);
const resetOpen = ref(false);
const resetProcessing = ref(false);
const issued = ref<{ userName: string; password: string } | null>(null);
const issuedOpen = ref(false);
const copied = ref(false);

// The server flashes the temporary password once; it is never stored in plain text.
const offFlash = router.on('flash', (event) => {
    const flash = (event as CustomEvent).detail?.flash as
        | { temporaryPassword?: { userName: string; password: string } }
        | undefined;

    if (flash?.temporaryPassword) {
        issued.value = flash.temporaryPassword;
        copied.value = false;
        issuedOpen.value = true;
    }
});

onBeforeUnmount(() => offFlash());

function confirmReset(user: ManagedUser): void {
    resetting.value = user;
    resetOpen.value = true;
}

function resetPassword(): void {
    if (!resetting.value) {
        return;
    }

    router.post(
        administration.users.temporaryPassword(resetting.value.id).url,
        {},
        {
            preserveScroll: true,
            onStart: () => (resetProcessing.value = true),
            onFinish: () => {
                resetProcessing.value = false;
                resetOpen.value = false;
            },
        },
    );
}

async function copyPassword(): Promise<void> {
    if (!issued.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(issued.value.password);
        copied.value = true;
    } catch {
        copied.value = false;
    }
}

function closeIssued(): void {
    issuedOpen.value = false;
    issued.value = null;
}

function confirmToggle(user: ManagedUser): void {
    toggling.value = user;
    toggleOpen.value = true;
}

function toggleStatus(): void {
    if (!toggling.value) {
        return;
    }

    router.patch(
        administration.users.status(toggling.value.id).url,
        {},
        {
            preserveScroll: true,
            onStart: () => (toggleProcessing.value = true),
            onFinish: () => {
                toggleProcessing.value = false;
                toggleOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Users" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
        <PageHeader
            eyebrow="Administration"
            title="Users"
            description="Control who can sign in and what they can manage. Deactivated users keep their innovation history."
        >
            <template #actions>
                <Button @click="openCreate"><Plus /> New user</Button>
            </template>
        </PageHeader>

        <Panel flush>
            <div class="flex flex-wrap items-center gap-3 border-b px-5 py-3">
                <div class="relative min-w-56 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="filters.search"
                        class="pl-9"
                        placeholder="Search name or email"
                        aria-label="Search users"
                    />
                </div>
                <SelectInput
                    v-model="filters.role"
                    class="w-44"
                    :options="roles"
                    placeholder="All roles"
                    aria-label="Filter by role"
                />
                <SelectInput
                    v-model="filters.status"
                    class="w-40"
                    :options="statuses"
                    placeholder="All statuses"
                    aria-label="Filter by status"
                />
            </div>

            <div v-if="props.users.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr class="label-mono text-muted-foreground">
                            <th class="px-5 py-2.5 font-medium">User</th>
                            <th class="px-5 py-2.5 font-medium">Role</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 text-right font-medium">
                                Innovations
                            </th>
                            <th class="px-5 py-2.5 font-medium">Updated</th>
                            <th class="px-5 py-2.5">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="user in props.users.data"
                            :key="user.id"
                            class="hover:bg-accent/40"
                        >
                            <td class="px-5 py-3">
                                <div class="font-medium">
                                    {{ user.name }}
                                    <span
                                        v-if="user.is_self"
                                        class="ml-1 text-xs font-normal text-muted-foreground"
                                        >(you)</span
                                    >
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ user.email }}
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    v-if="user.role_label"
                                    variant="outline"
                                    class="rounded-sm"
                                    >{{ user.role_label }}</Badge
                                >
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                            <td class="px-5 py-3">
                                <Badge
                                    :variant="
                                        user.status === 'ACTIVE'
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                    >{{ user.status_label }}</Badge
                                >
                            </td>
                            <td
                                class="px-5 py-3 text-right text-muted-foreground tabular-nums"
                            >
                                {{ user.innovations_count }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ formatDate(user.updated_at) }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        v-if="user.can.update"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Edit ${user.name}`"
                                        @click="openEdit(user)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        v-if="user.can.resetPassword"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Set a temporary password for ${user.name}`"
                                        @click="confirmReset(user)"
                                    >
                                        <KeyRound />
                                    </Button>
                                    <Button
                                        v-if="user.can.toggleStatus"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="
                                            user.status === 'ACTIVE'
                                                ? `Deactivate ${user.name}`
                                                : `Activate ${user.name}`
                                        "
                                        @click="confirmToggle(user)"
                                    >
                                        <PowerOff
                                            v-if="user.status === 'ACTIVE'"
                                        />
                                        <Power v-else />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="Users"
                title="No users found"
                description="Try a different search or filter, or add a new user."
            />
            <Pagination :paginator="props.users" />
        </Panel>

        <FormDialog
            v-model:open="dialogOpen"
            :title="editing ? `Edit ${editing.name}` : 'New user'"
            :icon="Users"
        >
            <form @submit.prevent="submit">
                <FormSection title="Account">
                    <Field
                        label="Name"
                        for="user-name"
                        :error="form.errors.name"
                        required
                    >
                        <Input
                            id="user-name"
                            v-model="form.name"
                            autocomplete="off"
                        />
                    </Field>
                    <Field
                        label="Email"
                        for="user-email"
                        :error="form.errors.email"
                        required
                    >
                        <Input
                            id="user-email"
                            v-model="form.email"
                            type="email"
                            autocomplete="off"
                        />
                    </Field>
                    <Field
                        :label="editing ? 'New password' : 'Password'"
                        for="user-password"
                        :error="form.errors.password"
                        :hint="
                            editing
                                ? 'Leave blank to keep the current password.'
                                : undefined
                        "
                        :required="!editing"
                    >
                        <Input
                            id="user-password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                        />
                    </Field>
                    <Field
                        label="Confirm password"
                        for="user-password-confirmation"
                        :error="form.errors.password_confirmation"
                        :required="!editing"
                    >
                        <Input
                            id="user-password-confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                        />
                    </Field>
                </FormSection>

                <FormSection title="Access">
                    <Field
                        label="Role"
                        for="user-role"
                        :error="form.errors.role"
                        :hint="
                            isEditingSelf
                                ? 'You cannot change your own role.'
                                : undefined
                        "
                        required
                    >
                        <SelectInput
                            id="user-role"
                            v-model="form.role"
                            :options="roleOptions"
                            :disabled="isEditingSelf"
                        />
                    </Field>
                    <Field
                        label="Status"
                        for="user-status"
                        :error="form.errors.status"
                        :hint="
                            isEditingSelf
                                ? 'You cannot deactivate your own account.'
                                : 'Inactive users cannot sign in to any internal page.'
                        "
                        required
                    >
                        <SelectInput
                            id="user-status"
                            v-model="form.status"
                            :options="statuses"
                            :disabled="isEditingSelf"
                        />
                    </Field>
                    <div
                        class="rounded-sm border bg-muted/30 p-3 sm:col-span-2"
                    >
                        <p
                            class="mb-2 flex items-center gap-2 label-mono text-muted-foreground"
                        >
                            <ShieldCheck class="size-3.5 text-primary" />
                            This role grants
                        </p>
                        <div
                            v-if="selectedRolePermissions?.length"
                            class="flex flex-wrap gap-1.5"
                        >
                            <Badge
                                v-for="permission in selectedRolePermissions"
                                :key="permission"
                                variant="outline"
                                class="rounded-sm font-mono font-normal"
                                >{{ permission }}</Badge
                            >
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No permissions.
                        </p>
                    </div>
                </FormSection>

                <FormFooter
                    :submit-label="editing ? 'Save user' : 'Create user'"
                    :processing="form.processing"
                    @cancel="dialogOpen = false"
                />
            </form>
        </FormDialog>

        <ConfirmDialog
            v-model:open="toggleOpen"
            :title="
                toggling?.status === 'ACTIVE'
                    ? `Deactivate ${toggling?.name}?`
                    : `Activate ${toggling?.name ?? 'user'}?`
            "
            :description="
                toggling?.status === 'ACTIVE'
                    ? 'They will lose access to every internal page. Their innovations and history are kept, and you can reactivate them later.'
                    : 'They will regain the access their role grants.'
            "
            :confirm-label="
                toggling?.status === 'ACTIVE' ? 'Deactivate' : 'Activate'
            "
            :destructive="toggling?.status === 'ACTIVE'"
            :processing="toggleProcessing"
            @confirm="toggleStatus"
        />

        <ConfirmDialog
            v-model:open="resetOpen"
            :title="`Set a temporary password for ${resetting?.name ?? 'user'}?`"
            description="Their current password stops working immediately. You will see the new password once, so share it with them securely."
            confirm-label="Generate password"
            :processing="resetProcessing"
            @confirm="resetPassword"
        />

        <FormDialog
            :open="issuedOpen"
            title="Temporary password"
            :description="`Give this to ${issued?.userName ?? 'the user'} through a secure channel. It won't be shown again; ask them to change it from their profile after signing in.`"
            :icon="KeyRound"
            size="md"
            @update:open="(value) => (value ? null : closeIssued())"
        >
            <div class="flex items-center gap-2 pb-2">
                <code
                    class="flex-1 rounded-lg border bg-muted/40 px-4 py-3 font-mono text-lg font-semibold tracking-wider select-all"
                    >{{ issued?.password }}</code
                >
                <Button variant="outline" @click="copyPassword">
                    <Check v-if="copied" />
                    <Copy v-else />
                    {{ copied ? 'Copied' : 'Copy' }}
                </Button>
            </div>
            <div
                class="sticky bottom-0 -mx-6 mt-4 flex justify-end border-t border-border/60 bg-background/80 px-6 py-3 backdrop-blur-md"
            >
                <Button @click="closeIssued">Done</Button>
            </div>
        </FormDialog>
    </div>
</template>
