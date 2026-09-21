<?php

use App\AccessPermission;
use App\Models\User;
use App\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
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
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertForbidden();
    }

    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)->get(route('administration.roles.index'))->assertOk()->assertSee('Roles');
    $this->actingAs($superAdmin)->get(route('administration.permissions.index'))->assertOk()->assertSee('Permissions');
    $this->actingAs($superAdmin)->get(route('dashboard'))
        ->assertSee(route('administration.roles.index'))
        ->assertSee(route('administration.permissions.index'));
});

test('super admin can create a permission and grant it to a new role', function () {
    $this->seed(RolePermissionSeeder::class);
    $superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($superAdmin);

    Livewire::test('pages::administration.permissions.index')
        ->set('permissionName', 'reports.view')
        ->call('createPermission')
        ->assertHasNoErrors();

    expect($superAdmin->fresh()->can('reports.view'))->toBeTrue();

    $component = Livewire::test('pages::administration.roles.index')
        ->set('roleName', 'Project Reviewer')
        ->call('createRole')
        ->assertHasNoErrors();

    $role = Role::findByName('PROJECT_REVIEWER', 'web');
    $permission = Permission::findByName('reports.view', 'web');

    $component->assertSet('editingRoleId', $role->id)
        ->set('selectedPermissions', [$permission->id])
        ->call('savePermissions')
        ->assertHasNoErrors();

    expect($role->fresh()->hasPermissionTo('reports.view'))->toBeTrue();
});

test('role grants reject unknown permissions and reserve role management for super admin', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->superAdmin()->create());
    $role = Role::findByName(UserRole::Viewer->value, 'web');
    $roleManage = Permission::findByName(AccessPermission::RoleManage->value, 'web');

    Livewire::test('pages::administration.roles.index')
        ->call('editRole', $role->id)
        ->set('selectedPermissions', [999999])
        ->call('savePermissions')
        ->assertHasErrors(['selectedPermissions.0']);

    Livewire::test('pages::administration.roles.index')
        ->call('editRole', $role->id)
        ->set('selectedPermissions', [$roleManage->id])
        ->call('savePermissions')
        ->assertHasErrors(['selectedPermissions']);

    expect($role->fresh()->hasPermissionTo(AccessPermission::RoleManage->value))->toBeFalse();
});

test('super admin role grants cannot be edited', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->superAdmin()->create());
    $superAdminRole = Role::findByName(UserRole::SuperAdmin->value, 'web');

    Livewire::test('pages::administration.roles.index')
        ->call('editRole', $superAdminRole->id)
        ->assertForbidden();

    Livewire::test('pages::administration.roles.index')
        ->set('editingRoleId', $superAdminRole->id)
        ->set('selectedPermissions', [])
        ->call('savePermissions')
        ->assertForbidden();

    expect($superAdminRole->fresh()->hasPermissionTo(AccessPermission::RoleManage->value))->toBeTrue();
});

test('duplicate and invalid permission names are rejected', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('pages::administration.permissions.index')
        ->set('permissionName', AccessPermission::CatalogView->value)
        ->call('createPermission')
        ->assertHasErrors(['permissionName']);

    Livewire::test('pages::administration.permissions.index')
        ->set('permissionName', 'Reports View')
        ->call('createPermission')
        ->assertHasErrors(['permissionName']);
});
