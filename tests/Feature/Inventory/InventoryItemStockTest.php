<?php

use App\Enums\InventoryItemStatus;
use App\Enums\StockStatus;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('stock status follows available quantity against the minimum', function (float $onHand, float $reserved, float $minimum, StockStatus $expected) {
    $balance = InventoryBalance::factory()->create([
        'inventory_item_id' => InventoryItem::factory()->create(['minimum_stock' => $minimum, 'quantity_on_hand' => $onHand]),
        'on_hand_quantity' => $onHand,
        'reserved_quantity' => $reserved,
    ]);

    expect($balance->stockStatus())->toBe($expected)
        ->and(InventoryItem::query()->withStockStatus($expected)->pluck('id')->all())->toBe([$balance->inventory_item_id])
        ->and(InventoryItem::query()->withStockStatus($expected, $balance->location_id)->pluck('id')->all())->toBe([$balance->inventory_item_id]);
})->with([
    'nothing available' => [5, 5, 0, StockStatus::Out],
    'at the minimum' => [6, 1, 5, StockStatus::Low],
    'above the minimum' => [10, 0, 5, StockStatus::In],
    'no minimum set' => [1, 0, 0, StockStatus::In],
]);

test('the items list shows stock at one location, filters by stock status, and scopes the stats', function () {
    $warehouse = Location::factory()->create();
    $workshop = Location::factory()->create();
    $motor = InventoryItem::factory()->create(['item_code' => 'ITM-MOTOR', 'name' => 'Motor', 'minimum_stock' => 5, 'unit_cost' => 100, 'quantity_on_hand' => 3]);
    $screw = InventoryItem::factory()->create(['item_code' => 'ITM-SCREW', 'name' => 'Screw', 'unit_cost' => 2, 'quantity_on_hand' => 50]);
    $bracket = InventoryItem::factory()->create(['item_code' => 'ITM-BRACKET', 'name' => 'Bracket', 'unit_cost' => 7, 'quantity_on_hand' => 4]);

    InventoryBalance::factory()->create(['inventory_item_id' => $motor->id, 'location_id' => $warehouse->id, 'on_hand_quantity' => 3]);
    InventoryBalance::factory()->create(['inventory_item_id' => $screw->id, 'location_id' => $warehouse->id, 'on_hand_quantity' => 50]);
    InventoryBalance::factory()->create(['inventory_item_id' => $bracket->id, 'location_id' => $workshop->id, 'on_hand_quantity' => 4]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.index', ['location' => $warehouse->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/items/Index')
            ->has('items.data', 3)
            ->where('items.data.0.item_code', 'ITM-BRACKET')
            ->where('items.data.0.available', 0)
            ->where('items.data.0.stock_status', StockStatus::Out->value)
            ->where('items.data.1.item_code', 'ITM-MOTOR')
            ->where('items.data.1.stock_status', StockStatus::Low->value)
            ->where('items.data.1.stock_value', 300)
            ->where('stats.stock_value', 400)
            ->where('stats.low_stock', 1)
            ->where('stats.out_of_stock', 1),
        );

    $this->get(route('inventory.items.index', ['stock' => StockStatus::Out->value, 'location' => $workshop->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 2)
            ->where('items.data.0.item_code', 'ITM-MOTOR')
            ->where('items.data.1.item_code', 'ITM-SCREW'),
        );

    $this->get(route('inventory.items.index', ['stock' => StockStatus::In->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 2)
            ->where('items.data.0.item_code', 'ITM-BRACKET')
            ->where('items.data.0.available', 4)
            ->where('items.data.1.item_code', 'ITM-SCREW')
            ->where('stats.stock_value', 428)
            ->where('stats.low_stock', 1)
            ->where('stats.out_of_stock', 0),
        );
});

test('the item status filter reads the older status parameter too', function () {
    InventoryItem::factory()->create(['name' => 'Active item']);
    InventoryItem::factory()->create(['name' => 'Retired item', 'status' => InventoryItemStatus::Discontinued]);

    $this->actingAs(User::factory()->viewer()->create());

    foreach (['item_status', 'status'] as $parameter) {
        $this->get(route('inventory.items.index', [$parameter => InventoryItemStatus::Discontinued->value]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Retired item')
                ->where('filters.item_status', InventoryItemStatus::Discontinued->value),
            );
    }
});

test('the item page opens the requested tab and offers posting to users who can post', function () {
    $item = InventoryItem::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('inventory.items.show', ['item' => $item, 'tab' => 'stock']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tab', 'stock')
            ->where('can.post', true)
            ->has('posting.items')
            ->where('ledgerUrl', route('inventory.ledger.index', ['item' => $item->id])),
        );

    $this->get(route('inventory.items.show', ['item' => $item, 'tab' => 'unknown']))
        ->assertInertia(fn (Assert $page) => $page->where('tab', 'overview'));
});

test('the old stock page redirects to the items list with its filters', function () {
    $location = Location::factory()->create();

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.stock.index', ['location' => $location->id, 'status' => StockStatus::Low->value, 'search' => 'motor']))
        ->assertRedirect(route('inventory.items.index', ['search' => 'motor', 'location' => $location->id, 'stock' => StockStatus::Low->value]));
});
