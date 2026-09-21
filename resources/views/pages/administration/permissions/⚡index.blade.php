<?php

use App\AccessPermission;
use App\UserRole;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.app'), Title('Permissions')] class extends Component
{
    public string $permissionName = '';

    public function mount(): void
    {
        Gate::authorize(AccessPermission::RoleManage->value);
    }

    public function createPermission(): void
    {
        Gate::authorize(AccessPermission::RoleManage->value);

        $validated = $this->validate([
            'permissionName' => [
                'required',
                'string',
                'max:125',
                'regex:/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web'),
            ],
        ]);

        $permission = Permission::create([
            'name' => $validated['permissionName'],
            'guard_name' => 'web',
        ]);

        Role::findByName(UserRole::SuperAdmin->value, 'web')->givePermissionTo($permission);

        $this->permissionName = '';
        unset($this->permissions);
        Flux::toast(__('Permission created.'));
    }

    #[Computed]
    public function permissions(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::query()->with('roles')->where('guard_name', 'web')->orderBy('name')->get();
    }
};
?>

<section data-page-title="Permissions">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Permissions') }}</flux:heading>
                <flux:text>{{ __('Permissions can be granted to roles from the Roles page.') }}</flux:text>
            </div>
            <flux:button icon="identification" :href="route('administration.roles.index')" wire:navigate>{{ __('Roles') }}</flux:button>
        </div>

        <flux:card>
            <form wire:submit="createPermission" class="flex flex-wrap items-end gap-3">
                <div class="min-w-56 flex-1">
                    <flux:input wire:model="permissionName" :label="__('New permission')" :placeholder="__('e.g. reports.view')" />
                    <flux:error name="permissionName" />
                </div>
                <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="createPermission">{{ __('Create permission') }}</flux:button>
            </form>
            <flux:text class="mt-3">{{ __('Use lowercase names such as reports.view. Super Admin receives new permissions automatically.') }}</flux:text>
        </flux:card>

        <div class="space-y-3">
            @foreach ($this->permissions as $permission)
                <flux:card wire:key="permission-{{ $permission->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <flux:heading size="lg">{{ $permission->name }}</flux:heading>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($permission->roles->sortBy('name') as $role)
                                <flux:badge wire:key="permission-{{ $permission->id }}-role-{{ $role->id }}" size="sm">{{ UserRole::tryFrom($role->name)?->label() ?? Str::headline($role->name) }}</flux:badge>
                            @empty
                                <flux:text>{{ __('No roles assigned') }}</flux:text>
                            @endforelse
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>
</section>
