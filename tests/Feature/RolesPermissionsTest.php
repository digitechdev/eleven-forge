<?php

use App\Enums\AccessPermission;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('only super admin can open role and permission management', function () {
    $this->seed(RolePermissionSeeder::class);

    $routeNames = ['administration.roles.index', 'administration.permissions.index'];

    foreach ($routeNames as $routeName) {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    $admin = User::factory()->admin()->create();

    foreach ($routeNames as $routeName) {
        $this->actingAs($admin)->get(route($routeName))->assertForbidden();
    }

    $this->actingAs($admin)->post(route('administration.permissions.store'), ['name' => 'reports.view'])->assertForbidden();
    $this->actingAs($admin)->post(route('administration.roles.store'), ['name' => 'Reviewer'])->assertForbidden();

    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)->get(route('administration.roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('administration/roles/Index')
            ->has('roles', 5)
            ->where('permissions', fn ($permissions) => collect($permissions)->doesntContain('name', AccessPermission::RoleManage->value)),
        );

    $this->actingAs($superAdmin)->get(route('administration.permissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('administration/permissions/Index')
            ->has('permissions', count(AccessPermission::cases())),
        );
});

test('super admin can create a permission and grant it to a new role', function () {
    $this->seed(RolePermissionSeeder::class);
    $superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($superAdmin);

    $this->post(route('administration.permissions.store'), ['name' => 'reports.view'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('administration.permissions.index'));

    expect($superAdmin->fresh()->can('reports.view'))->toBeTrue();

    $this->post(route('administration.roles.store'), ['name' => 'Project Reviewer'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('administration.roles.index'));

    $role = Role::findByName('PROJECT_REVIEWER', 'web');
    $permission = Permission::findByName('reports.view', 'web');

    $this->put(route('administration.roles.update', $role), ['permissions' => [$permission->id]])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('administration.roles.index'));

    expect($role->fresh()->hasPermissionTo('reports.view'))->toBeTrue();
});

test('role grants reject unknown permissions and reserve role management for super admin', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->superAdmin()->create());
    $role = Role::findByName(UserRole::Viewer->value, 'web');
    $roleManage = Permission::findByName(AccessPermission::RoleManage->value, 'web');

    $this->put(route('administration.roles.update', $role), ['permissions' => [999999]])
        ->assertSessionHasErrors('permissions.0');

    $this->put(route('administration.roles.update', $role), ['permissions' => [$roleManage->id]])
        ->assertSessionHasErrors('permissions');

    expect($role->fresh()->hasPermissionTo(AccessPermission::RoleManage->value))->toBeFalse();
});

test('super admin role grants cannot be edited', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->superAdmin()->create());
    $superAdminRole = Role::findByName(UserRole::SuperAdmin->value, 'web');

    $this->put(route('administration.roles.update', $superAdminRole), ['permissions' => []])
        ->assertForbidden();

    expect($superAdminRole->fresh()->hasPermissionTo(AccessPermission::RoleManage->value))->toBeTrue();
});

test('duplicate and invalid role and permission names are rejected', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->post(route('administration.permissions.store'), ['name' => AccessPermission::CatalogView->value])
        ->assertSessionHasErrors('name');

    $this->post(route('administration.permissions.store'), ['name' => 'Reports View'])
        ->assertSessionHasErrors('name');

    $this->post(route('administration.roles.store'), ['name' => 'viewer'])
        ->assertSessionHasErrors('name');
});
