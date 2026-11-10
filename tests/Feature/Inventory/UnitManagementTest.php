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
    Unit::factory()->create(['name' => 'Kilogram', 'symbol' => 'kg', 'decimal_places' => 3]);
    Unit::factory()->create(['name' => 'Meter', 'symbol' => 'm']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.units.index', ['search' => 'kilo']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/units/Index')
            ->has('units.data', 1)
            ->where('units.data.0.symbol', 'kg')
            ->where('units.data.0.decimal_places', 3)
            ->where('can.manage', false),
        );

    $this->post(route('inventory.units.store'), ['name' => 'Box', 'symbol' => 'box', 'decimal_places' => 0, 'is_active' => true])
        ->assertForbidden();
});

test('managers can create and update units with unique names and symbols and a precision of up to 3 decimals', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->post(route('inventory.units.store'), ['name' => 'Piece', 'symbol' => 'pc', 'decimal_places' => 0, 'is_active' => true])
        ->assertSessionHasNoErrors();

    $unit = Unit::query()->where('symbol', 'pc')->firstOrFail();

    $this->post(route('inventory.units.store'), ['name' => 'Piece', 'symbol' => 'pc', 'decimal_places' => 4, 'is_active' => true])
        ->assertSessionHasErrors(['name', 'symbol', 'decimal_places']);

    $this->put(route('inventory.units.update', $unit), ['name' => 'Piece', 'symbol' => 'pc', 'decimal_places' => 2, 'is_active' => false])
        ->assertSessionHasNoErrors();

    expect($unit->fresh())
        ->is_active->toBeFalse()
        ->decimal_places->toBe(2);
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

test('lists show 10 entries by default and accept only the offered page sizes', function (mixed $requested, int $expected) {
    Unit::factory()->count(30)->create();

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.units.index', array_filter(['per_page' => $requested])))
        ->assertInertia(fn (Assert $page) => $page->where('units.per_page', $expected));
})->with([
    'default' => [null, 10],
    'allowed size' => [25, 25],
    'largest size' => [200, 200],
    'not an option' => [37, 10],
    'not a number' => ['all', 10],
]);
