<?php

use App\Enums\AccessPermission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Innovation;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('the public showcase placeholder is available to guests', function () {
    $this->get(route('showcase.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('showcase/Index'));
});

test('internal placeholders require authentication', function () {
    $this->get(route('innovations.index'))->assertRedirect(route('login'));
});

test('active viewers can open the internal architecture pages but not administration', function () {
    $this->seed(RolePermissionSeeder::class);
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)->get(route('innovations.index'))->assertOk();
    $this->actingAs($viewer)->get(route('inventory.items.index'))->assertOk();
    $this->actingAs($viewer)->get(route('administration.users.index'))->assertForbidden();
    $this->actingAs($viewer)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.access.can.viewInnovations', true)
            ->where('auth.access.can.manageUsers', false)
            ->where('auth.access.can.manageRoles', false),
        );
});

test('inactive users cannot open internal architecture pages', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->viewer()->inactive()->create();

    $this->actingAs($user)->get(route('innovations.index'))->assertForbidden();
});

test('role policies enforce catalog management and innovation ownership', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->manager()->create();
    $contributor = User::factory()->contributor()->create();
    $viewer = User::factory()->viewer()->create();
    $ownInnovation = Innovation::factory()->for($contributor, 'creator')->create();
    $otherInnovation = Innovation::factory()->create();

    expect($admin->status)->toBe(UserStatus::Active)
        ->and($admin->hasRole(UserRole::Admin->value))->toBeTrue()
        ->and($admin->isActive())->toBeTrue()
        ->and($admin->can('viewAny', User::class))->toBeTrue()
        ->and($manager->can('viewAny', User::class))->toBeFalse()
        ->and($manager->can('create', InventoryItem::class))->toBeTrue()
        ->and($contributor->can('create', InventoryItem::class))->toBeFalse()
        ->and($contributor->can('update', $ownInnovation))->toBeTrue()
        ->and($contributor->can('update', $otherInnovation))->toBeFalse()
        ->and($viewer->can('create', Innovation::class))->toBeFalse();
});

test('super admin can open every internal menu destination', function () {
    $this->seed(RolePermissionSeeder::class);
    $superAdmin = User::factory()->superAdmin()->create();

    foreach (AccessPermission::cases() as $permission) {
        expect($superAdmin->can($permission->value))->toBeTrue();
    }

    foreach ([
        'dashboard',
        'innovations.index',
        'innovation-categories.index',
        'inventory.items.index',
        'inventory.categories.index',
        'inventory.units.index',
        'technologies.index',
        'inventory.ledger.index',
        'inventory.locations.index',
        'production.build-orders.index',
        'administration.users.index',
        'administration.roles.index',
        'administration.permissions.index',
    ] as $routeName) {
        $this->actingAs($superAdmin)->get(route($routeName))->assertOk();
    }

    $this->actingAs($superAdmin)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.access.can', [
            'viewInnovations' => true,
            'viewInnovationCategories' => true,
            'viewInventoryItems' => true,
            'viewItemCategories' => true,
            'viewUnits' => true,
            'viewStock' => true,
            'viewLocations' => true,
            'viewBuildOrders' => true,
            'viewProductionRuns' => true,
            'viewTechnologies' => true,
            'manageUsers' => true,
            'manageRoles' => true,
        ]));
});
