<?php

use App\Models\InventoryBalance;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('viewers can list locations with their stock but cannot manage them', function () {
    $location = Location::factory()->create(['code' => 'WH-1', 'name' => 'Warehouse']);
    InventoryBalance::factory()->create(['location_id' => $location->id, 'on_hand_quantity' => 7]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.locations.index', ['search' => 'WH-1']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/locations/Index')
            ->has('locations.data', 1)
            ->where('locations.data.0.item_count', 1)
            ->where('locations.data.0.total_on_hand', 7)
            ->where('can.manage', false),
        );

    $this->post(route('inventory.locations.store'), ['code' => 'X', 'name' => 'X', 'is_active' => true])->assertForbidden();
    $this->put(route('inventory.locations.update', $location), ['code' => 'WH-1', 'name' => 'X', 'is_active' => false])->assertForbidden();
    $this->delete(route('inventory.locations.destroy', $location))->assertForbidden();
});

test('managers create locations with unique uppercase codes', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->post(route('inventory.locations.store'), ['code' => ' lab-2 ', 'name' => 'Lab 2', 'is_active' => true])
        ->assertSessionHasNoErrors();

    $location = Location::query()->where('code', 'LAB-2')->firstOrFail();

    $this->post(route('inventory.locations.store'), ['code' => 'LAB-2', 'name' => 'Other', 'is_active' => true])
        ->assertSessionHasErrors('code');

    $this->put(route('inventory.locations.update', $location), ['code' => 'LAB-2', 'name' => 'Lab 2', 'is_active' => false])
        ->assertSessionHasNoErrors();

    expect($location->fresh()->is_active)->toBeFalse();
});

test('the main location cannot be deleted', function () {
    $this->actingAs(User::factory()->manager()->create());
    $main = Location::query()->where('code', Location::MAIN_CODE)->firstOrFail();

    $this->delete(route('inventory.locations.destroy', $main))->assertForbidden();

    expect($main->fresh())->not->toBeNull();
});

test('locations with stock must be deactivated instead of deleted', function () {
    $this->actingAs(User::factory()->manager()->create());
    $used = InventoryBalance::factory()->create()->location;
    $unused = Location::factory()->create();

    $this->delete(route('inventory.locations.destroy', $used))->assertRedirect();
    $this->delete(route('inventory.locations.destroy', $unused))->assertRedirect();

    expect($used->fresh())->not->toBeNull()
        ->and($unused->fresh())->toBeNull();
});
