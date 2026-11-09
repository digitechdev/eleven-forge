<?php

namespace App\Http\Controllers\Administration;

use App\Enums\AccessPermission;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreRoleRequest;
use App\Http\Requests\Administration\UpdateRolePermissionsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Show every role with its granted permissions.
     */
    public function index(): Response
    {
        return Inertia::render('administration/roles/Index', [
            'roles' => Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => UserRole::tryFrom($role->name)?->label() ?? Str::headline($role->name),
                    'isProtected' => $role->name === UserRole::SuperAdmin->value,
                    'usersCount' => $role->users_count,
                    'permissionIds' => $role->permissions->modelKeys(),
                    'permissionNames' => $role->permissions->pluck('name')->sort()->values()->all(),
                ]),
            'permissions' => Permission::query()
                ->where('guard_name', 'web')
                ->where('name', '!=', AccessPermission::RoleManage->value)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Create a new role without any permissions.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::create(['name' => $request->roleKey(), 'guard_name' => 'web']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('administration.roles.index');
    }

    /**
     * Replace the permissions granted to a role.
     */
    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === UserRole::SuperAdmin->value, 403);

        $role->syncPermissions(
            Permission::query()
                ->whereIn('id', $request->validated('permissions', []))
                ->where('guard_name', 'web')
                ->get(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role permissions saved.')]);

        return to_route('administration.roles.index');
    }
}
