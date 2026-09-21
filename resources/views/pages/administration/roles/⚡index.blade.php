<?php

use App\AccessPermission;
use App\UserRole;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.app'), Title('Roles')] class extends Component
{
    public string $roleName = '';

    public ?int $editingRoleId = null;

    /** @var array<int, int|string> */
    public array $selectedPermissions = [];

    public function mount(): void
    {
        Gate::authorize(AccessPermission::RoleManage->value);
    }

    public function createRole(): void
    {
        Gate::authorize(AccessPermission::RoleManage->value);

        $validated = $this->validate([
            'roleName' => ['required', 'string', 'max:125', 'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/'],
        ]);

        $name = Str::upper(Str::slug($validated['roleName'], '_'));

        if (Role::query()->where('name', $name)->where('guard_name', 'web')->exists()) {
            throw ValidationException::withMessages([
                'roleName' => __('A role with this name already exists.'),
            ]);
        }

        $role = Role::create(['name' => $name, 'guard_name' => 'web']);

        $this->roleName = '';
        unset($this->roles);
        $this->editRole($role->id);
        Flux::toast(__('Role created.'));
    }

    public function editRole(int $roleId): void
    {
        Gate::authorize(AccessPermission::RoleManage->value);

        $role = Role::query()->with('permissions')->findOrFail($roleId);
        abort_if($role->name === UserRole::SuperAdmin->value, 403);

        $this->editingRoleId = $role->id;
        $this->selectedPermissions = $role->permissions->modelKeys();
    }

    public function savePermissions(): void
    {
        Gate::authorize(AccessPermission::RoleManage->value);

        $role = Role::query()->findOrFail($this->editingRoleId);
        abort_if($role->name === UserRole::SuperAdmin->value, 403);

        $validated = $this->validate([
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [
                'integer',
                'distinct',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ]);

        $permissions = Permission::query()
            ->whereIn('id', $validated['selectedPermissions'])
            ->where('guard_name', 'web')
            ->get();

        if ($permissions->contains('name', AccessPermission::RoleManage->value)) {
            throw ValidationException::withMessages([
                'selectedPermissions' => __('Role management is reserved for Super Admin.'),
            ]);
        }

        $role->syncPermissions($permissions);

        $this->editingRoleId = null;
        $this->selectedPermissions = [];
        unset($this->roles);
        Flux::toast(__('Role permissions saved.'));
    }

    public function cancelEditing(): void
    {
        $this->editingRoleId = null;
        $this->selectedPermissions = [];
        $this->resetErrorBag();
    }

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::query()->with('permissions')->withCount('users')->orderBy('name')->get();
    }

    #[Computed]
    public function permissions(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->where('name', '!=', AccessPermission::RoleManage->value)
            ->orderBy('name')
            ->get();
    }
};
?>

<section data-page-title="Roles">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Roles') }}</flux:heading>
                <flux:text>{{ __('Create roles and choose the permissions each role receives.') }}</flux:text>
            </div>
            <flux:button icon="key" :href="route('administration.permissions.index')" wire:navigate>{{ __('Permissions') }}</flux:button>
        </div>

        <flux:card>
            <form wire:submit="createRole" class="flex flex-wrap items-end gap-3">
                <div class="min-w-56 flex-1">
                    <flux:input wire:model="roleName" :label="__('New role')" :placeholder="__('e.g. Project Reviewer')" />
                    <flux:error name="roleName" />
                </div>
                <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="createRole">{{ __('Create role') }}</flux:button>
            </form>
        </flux:card>

        @if ($editingRoleId !== null)
            <flux:card>
                <form wire:submit="savePermissions" class="space-y-5">
                    <div>
                        <flux:heading size="lg">{{ __('Edit role permissions') }}</flux:heading>
                        <flux:text>{{ __('Changes take effect for users assigned to this role when saved.') }}</flux:text>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($this->permissions as $permission)
                            <div wire:key="permission-option-{{ $permission->id }}">
                                <flux:checkbox wire:model="selectedPermissions" :value="$permission->id" :label="$permission->name" />
                            </div>
                        @endforeach
                    </div>
                    <flux:error name="selectedPermissions" />
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="savePermissions">{{ __('Save permissions') }}</flux:button>
                        <flux:button type="button" wire:click="cancelEditing">{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            </flux:card>
        @endif

        <div class="space-y-3">
            @foreach ($this->roles as $role)
                <flux:card wire:key="role-{{ $role->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:heading size="lg">{{ UserRole::tryFrom($role->name)?->label() ?? Str::headline($role->name) }}</flux:heading>
                                @if ($role->name === UserRole::SuperAdmin->value)
                                    <flux:badge color="amber">{{ __('Protected') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text>{{ trans_choice(':count user|:count users', $role->users_count, ['count' => $role->users_count]) }}</flux:text>
                            <div class="flex flex-wrap gap-2">
                                @forelse ($role->permissions->sortBy('name') as $permission)
                                    <flux:badge wire:key="role-{{ $role->id }}-permission-{{ $permission->id }}" size="sm">{{ $permission->name }}</flux:badge>
                                @empty
                                    <flux:text>{{ __('No permissions assigned') }}</flux:text>
                                @endforelse
                            </div>
                        </div>
                        @if ($role->name !== UserRole::SuperAdmin->value)
                            <flux:button size="sm" icon="pencil-square" wire:click="editRole({{ $role->id }})">{{ __('Edit permissions') }}</flux:button>
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>
</section>
