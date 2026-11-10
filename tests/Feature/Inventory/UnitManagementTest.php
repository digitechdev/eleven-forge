<?php

use App\Models\InventoryItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('viewers can list and search units but not manage them', function () {
    Unit::factory()->create(['name' => 'Kilogram', 'symbol' => 'kg']);
    Unit::factory()->create(['name' => 'Meter', 'symbol' => 'm']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.units.index', ['search' => 'kilo']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/units/Index')
            ->has('units.data', 1)
            ->where('units.data.0.symbol', 'kg')
            ->where('can.manage', false),
        );

    $this->post(route('inventory.units.store'), ['name' => 'Box', 'symbol' => 'box', 'is_active' => true])
        ->assertForbidden();
});

test('managers can create and update units with unique names and symbols', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->post(route('inventory.units.store'), ['name' => 'Piece', 'symbol' => 'pc', 'is_active' => true])
        ->assertSessionHasNoErrors();

    $unit = Unit::query()->where('symbol', 'pc')->firstOrFail();

    $this->post(route('inventory.units.store'), ['name' => 'Piece', 'symbol' => 'pc', 'is_active' => true])
        ->assertSessionHasErrors(['name', 'symbol']);

    $this->put(route('inventory.units.update', $unit), ['name' => 'Piece', 'symbol' => 'pc', 'is_active' => false])
        ->assertSessionHasNoErrors();

    expect($unit->fresh()->is_active)->toBeFalse();
});

test('units in use cannot be deleted', function () {
    $this->actingAs(User::factory()->manager()->create());
    $usedUnit = InventoryItem::factory()->create()->defaultUnit;
    $unusedUnit = Unit::factory()->create();

    $this->delete(route('inventory.units.destroy', $usedUnit));
    $this->delete(route('inventory.units.destroy', $unusedUnit));

    expect($usedUnit->fresh())->not->toBeNull()
        ->and($unusedUnit->fresh())->toBeNull();
});
