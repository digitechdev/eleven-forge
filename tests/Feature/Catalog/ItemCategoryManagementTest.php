<?php

use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('viewers see the category hierarchy in tree order but cannot manage it', function () {
    $electronics = ItemCategory::factory()->create(['name' => 'Electronics', 'sort_order' => 1]);
    ItemCategory::factory()->create(['name' => 'Sensors', 'parent_id' => $electronics->id]);
    ItemCategory::factory()->create(['name' => 'Fasteners', 'sort_order' => 2]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/categories/Index')
            ->where('categories.0.name', 'Electronics')
            ->where('categories.1.name', 'Sensors')
            ->where('categories.1.depth', 1)
            ->where('categories.1.path', 'Electronics / Sensors')
            ->where('categories.2.name', 'Fasteners')
            ->where('can.manage', false),
        );

    $this->post(route('inventory.categories.store'), ['name' => 'Cables', 'is_active' => true])
        ->assertForbidden();
});

test('category names must be unique within the same parent only', function () {
    $this->actingAs(User::factory()->manager()->create());
    $electronics = ItemCategory::factory()->create(['name' => 'Electronics']);
    $mechanical = ItemCategory::factory()->create(['name' => 'Mechanical']);
    ItemCategory::factory()->create(['name' => 'Spare parts', 'parent_id' => $electronics->id]);

    $this->post(route('inventory.categories.store'), ['name' => 'Spare parts', 'parent_id' => $electronics->id, 'is_active' => true])
        ->assertSessionHasErrors('name');

    $this->post(route('inventory.categories.store'), ['name' => 'Spare parts', 'parent_id' => $mechanical->id, 'is_active' => true])
        ->assertSessionHasNoErrors();

    $this->post(route('inventory.categories.store'), ['name' => 'Electronics', 'parent_id' => null, 'is_active' => true])
        ->assertSessionHasErrors('name');
});

test('a category cannot be moved under itself or one of its descendants', function () {
    $this->actingAs(User::factory()->manager()->create());
    $root = ItemCategory::factory()->create(['name' => 'Electronics']);
    $child = ItemCategory::factory()->create(['parent_id' => $root->id]);
    $grandchild = ItemCategory::factory()->create(['parent_id' => $child->id]);

    foreach ([$root, $grandchild] as $invalidParent) {
        $this->put(route('inventory.categories.update', $root), ['name' => 'Electronics', 'parent_id' => $invalidParent->id, 'is_active' => true])
            ->assertSessionHasErrors('parent_id');
    }

    $this->put(route('inventory.categories.update', $grandchild), ['name' => $grandchild->name, 'parent_id' => $root->id, 'is_active' => false])
        ->assertSessionHasNoErrors();

    expect($grandchild->fresh()->parent_id)->toBe($root->id);
});

test('categories with items or subcategories cannot be deleted', function () {
    $this->actingAs(User::factory()->manager()->create());
    $categoryWithItems = InventoryItem::factory()->create()->category;
    $parent = ItemCategory::factory()->create();
    $emptyChild = ItemCategory::factory()->create(['parent_id' => $parent->id]);

    $this->delete(route('inventory.categories.destroy', $categoryWithItems));
    $this->delete(route('inventory.categories.destroy', $parent));
    $this->delete(route('inventory.categories.destroy', $emptyChild));

    expect($categoryWithItems->fresh())->not->toBeNull()
        ->and($parent->fresh())->not->toBeNull()
        ->and($emptyChild->fresh())->toBeNull();
});
