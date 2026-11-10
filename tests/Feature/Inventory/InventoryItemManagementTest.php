<?php

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function inventoryItemPayload(array $overrides = []): array
{
    return [
        'item_code' => null,
        'name' => 'Stepper motor',
        'item_type' => InventoryItemType::Electronic->value,
        'category_id' => ItemCategory::factory()->create(['is_active' => true])->id,
        'default_unit_id' => Unit::factory()->create(['is_active' => true])->id,
        'brand' => 'Nema',
        'quantity_on_hand' => 10,
        'minimum_stock' => 2,
        'unit_cost' => 450.5,
        'status' => InventoryItemStatus::Active->value,
        ...$overrides,
    ];
}

test('viewers can list and view items but not manage them', function () {
    $item = InventoryItem::factory()->create(['name' => 'Aluminium sheet']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/items/Index')
            ->has('items.data', 1)
            ->where('items.data.0.name', 'Aluminium sheet')
            ->where('can.manage', false),
        );

    $this->get(route('inventory.items.show', $item))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inventory/items/Show')->where('item.id', $item->id));

    $this->post(route('inventory.items.store'), inventoryItemPayload())->assertForbidden();
    $this->put(route('inventory.items.update', $item), inventoryItemPayload())->assertForbidden();
    $this->delete(route('inventory.items.destroy', $item))->assertForbidden();
});

test('managers create items with a generated code and creator, then update them', function () {
    $manager = User::factory()->manager()->create();
    InventoryItem::factory()->create(['item_code' => 'ITM-00007']);

    $this->actingAs($manager)
        ->post(route('inventory.items.store'), inventoryItemPayload())
        ->assertSessionHasNoErrors();

    $item = InventoryItem::query()->where('name', 'Stepper motor')->firstOrFail();

    expect($item->item_code)->toBe('ITM-00008')
        ->and($item->created_by)->toBe($manager->id);

    $this->put(route('inventory.items.update', $item), inventoryItemPayload([
        'item_code' => 'MTR-001',
        'name' => 'Stepper motor NEMA 17',
        'category_id' => $item->category_id,
        'default_unit_id' => $item->default_unit_id,
        'status' => InventoryItemStatus::Discontinued->value,
    ]))->assertSessionHasNoErrors();

    expect($item->fresh())
        ->item_code->toBe('MTR-001')
        ->name->toBe('Stepper motor NEMA 17')
        ->status->toBe(InventoryItemStatus::Discontinued);
});

test('item codes must be unique and category and unit must exist and be active', function () {
    $this->actingAs(User::factory()->manager()->create());
    InventoryItem::factory()->create(['item_code' => 'ITM-00001']);

    $this->post(route('inventory.items.store'), inventoryItemPayload([
        'item_code' => 'ITM-00001',
        'category_id' => 999999,
        'default_unit_id' => Unit::factory()->create(['is_active' => false])->id,
    ]))->assertSessionHasErrors(['item_code', 'category_id', 'default_unit_id']);
});

test('an item keeps its inactive category and unit when edited', function () {
    $this->actingAs(User::factory()->manager()->create());
    $item = InventoryItem::factory()->create();
    $item->category->update(['is_active' => false]);
    $item->defaultUnit->update(['is_active' => false]);

    $this->put(route('inventory.items.update', $item), inventoryItemPayload([
        'item_code' => $item->item_code,
        'category_id' => $item->category_id,
        'default_unit_id' => $item->default_unit_id,
    ]))->assertSessionHasNoErrors();
});

test('items can be filtered to low stock only', function () {
    InventoryItem::factory()->lowStock()->create(['name' => 'Low item']);
    InventoryItem::factory()->create(['name' => 'Untracked item', 'quantity_on_hand' => 0, 'minimum_stock' => 0]);
    InventoryItem::factory()->create(['name' => 'Healthy item', 'quantity_on_hand' => 50, 'minimum_stock' => 5]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.index', ['stock' => 'low']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.name', 'Low item')
            ->where('items.data.0.is_low_stock', true)
            ->where('stats.total', 3)
            ->where('stats.low_stock', 1),
        );
});

test('category filter includes direct child categories', function () {
    $parent = ItemCategory::factory()->create();
    $child = ItemCategory::factory()->create(['parent_id' => $parent->id]);
    InventoryItem::factory()->create(['name' => 'Parent item', 'category_id' => $parent->id]);
    InventoryItem::factory()->create(['name' => 'Child item', 'category_id' => $child->id]);
    InventoryItem::factory()->create(['name' => 'Other item']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.index', ['category' => $parent->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 2)
            ->where('items.data.0.name', 'Child item')
            ->where('items.data.1.name', 'Parent item'),
        );
});

test('items used in a bill of materials cannot be deleted, others are soft deleted', function () {
    $this->actingAs(User::factory()->manager()->create());
    $usedItem = InnovationItem::factory()->create()->inventoryItem;
    $unusedItem = InventoryItem::factory()->create();

    $this->delete(route('inventory.items.destroy', $usedItem));
    $this->delete(route('inventory.items.destroy', $unusedItem));

    expect($usedItem->fresh()->trashed())->toBeFalse()
        ->and($unusedItem->fresh()->trashed())->toBeTrue();
});

test('the show page lists innovations that use the item', function () {
    $line = InnovationItem::factory()->create(['quantity_required' => 4, 'is_optional' => true]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.show', $line->inventory_item_id))
        ->assertInertia(fn (Assert $page) => $page
            ->has('usages', 1)
            ->where('usages.0.innovation_name', $line->innovation->name)
            ->where('usages.0.quantity_required', 4)
            ->where('usages.0.is_optional', true),
        );
});

test('the show page omits archived innovations that still reference the item', function () {
    $line = InnovationItem::factory()->create();
    $line->innovation->delete();

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.show', $line->inventoryItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('usages', 0));
});
