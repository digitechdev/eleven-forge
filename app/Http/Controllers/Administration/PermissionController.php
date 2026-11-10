<?php

namespace App\Http\Controllers\Administration;

use App\Enums\AccessPermission;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StorePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Show every permission with the roles that hold it.
     */
    public function index(): Response
    {
        return Inertia::render('administration/permissions/Index', [
            'permissions' => Permission::query()
                ->with('roles')
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get()
                ->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => AccessPermission::tryFrom($permission->name)?->description(),
                    'roles' => $permission->roles
                        ->sortBy('name')
                        ->map(fn (Role $role): string => UserRole::tryFrom($role->name)?->label() ?? Str::headline($role->name))
                        ->values()
                        ->all(),
                ]),
        ]);
    }

    /**
     * Create a permission and grant it to Super Admin.
     */
    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $permission = Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        Role::findByName(UserRole::SuperAdmin->value, 'web')->givePermissionTo($permission);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permission created.')]);

        return to_route('administration.permissions.index');
    }
}
