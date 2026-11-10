<?php

use App\Enums\AccessPermission;
use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Enums\StockStatus;
use App\Enums\StockTransactionType;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Models\InventoryItemPriceChange;
use App\Models\ItemCategory;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockLedgerService;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

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
        'minimum_stock' => 2,
        'reorder_level' => 4,
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
        ->and($item->created_by)->toBe($manager->id)
        ->and((float) $item->reorder_level)->toBe(4.0)
        ->and((float) $item->quantity_on_hand)->toBe(0.0)
        ->and($item->ledgerEntries()->exists())->toBeFalse();

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

test('the older low-stock-only filter still works', function () {
    InventoryItem::factory()->lowStock()->create(['name' => 'Low item']);
    InventoryItem::factory()->create(['name' => 'Untracked item', 'quantity_on_hand' => 0, 'minimum_stock' => 0]);
    InventoryItem::factory()->create(['name' => 'Healthy item', 'quantity_on_hand' => 50, 'minimum_stock' => 5]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.index', ['stock' => 'low']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.name', 'Low item')
            ->where('items.data.0.stock_status', StockStatus::Low->value)
            ->where('filters.stock', StockStatus::Low->value)
            ->where('stats.low_stock', 1)
            ->where('stats.out_of_stock', 1),
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

test('creating an item with opening stock posts one opening balance at the chosen location', function () {
    $manager = User::factory()->manager()->create();
    $workshop = Location::factory()->create();

    $this->actingAs($manager)
        ->post(route('inventory.items.store'), inventoryItemPayload([
            'opening_quantity' => 12,
            'opening_location_id' => $workshop->id,
        ]))
        ->assertSessionHasNoErrors();

    $item = InventoryItem::query()->where('name', 'Stepper motor')->firstOrFail();
    $entry = StockLedgerEntry::query()->sole();

    expect((float) $item->quantity_on_hand)->toBe(12.0)
        ->and($entry->inventory_item_id)->toBe($item->id)
        ->and($entry->transaction_type)->toBe(StockTransactionType::OpeningBalance)
        ->and($entry->location_id)->toBe($workshop->id)
        ->and((float) $entry->unit_cost)->toBe(450.5)
        ->and($entry->created_by)->toBe($manager->id);
});

test('opening stock needs an active location', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->post(route('inventory.items.store'), inventoryItemPayload([
            'opening_quantity' => 5,
            'opening_location_id' => Location::factory()->create(['is_active' => false])->id,
        ]))
        ->assertSessionHasErrors('opening_location_id');

    expect(InventoryItem::query()->count())->toBe(0);
});

test('quantity on hand sent with an update is ignored', function () {
    $this->actingAs(User::factory()->manager()->create());
    $item = InventoryItem::factory()->create(['unit_cost' => 450.5]);
    app(StockLedgerService::class)->post($item, Location::factory()->create(), StockTransactionType::Receive, 7, 0);

    $this->put(route('inventory.items.update', $item), inventoryItemPayload([
        'category_id' => $item->category_id,
        'default_unit_id' => $item->default_unit_id,
        'quantity_on_hand' => 999,
    ]))->assertSessionHasNoErrors();

    expect((float) $item->fresh()->quantity_on_hand)->toBe(7.0);
});

test('creating an item records its initial unit cost', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)->post(route('inventory.items.store'), inventoryItemPayload());

    $change = InventoryItemPriceChange::query()->sole();

    expect($change->previous_unit_cost)->toBeNull()
        ->and((float) $change->new_unit_cost)->toBe(450.5)
        ->and($change->changed_by)->toBe($manager->id);
});

test('changing the unit cost records history with the reason and leaves stock alone', function () {
    $manager = User::factory()->manager()->create();
    $item = InventoryItem::factory()->create(['unit_cost' => 450.5]);
    app(StockLedgerService::class)->post($item, Location::factory()->create(), StockTransactionType::Receive, 3, 0);

    $this->actingAs($manager)->put(route('inventory.items.update', $item), inventoryItemPayload([
        'category_id' => $item->category_id,
        'default_unit_id' => $item->default_unit_id,
        'unit_cost' => 500,
        'cost_change_reason' => 'Supplier price increase',
    ]))->assertSessionHasNoErrors();

    $change = InventoryItemPriceChange::query()->sole();

    expect((float) $change->previous_unit_cost)->toBe(450.5)
        ->and((float) $change->new_unit_cost)->toBe(500.0)
        ->and($change->reason)->toBe('Supplier price increase')
        ->and($change->changed_by)->toBe($manager->id)
        ->and((float) $item->fresh()->unit_cost)->toBe(500.0)
        ->and((float) $item->fresh()->quantity_on_hand)->toBe(3.0);
});

test('an update that keeps the unit cost records no history', function () {
    $item = InventoryItem::factory()->create(['unit_cost' => 450.5]);

    $this->actingAs(User::factory()->manager()->create())
        ->put(route('inventory.items.update', $item), inventoryItemPayload([
            'category_id' => $item->category_id,
            'default_unit_id' => $item->default_unit_id,
            'unit_cost' => '450.50',
            'name' => 'Renamed',
        ]))->assertSessionHasNoErrors();

    expect(InventoryItemPriceChange::query()->count())->toBe(0);
});

test('the show page includes stock by location, recent ledger entries, and price history', function () {
    $item = InventoryItem::factory()->create(['minimum_stock' => 5, 'unit_cost' => 10]);
    $ledger = app(StockLedgerService::class);
    $empty = Location::factory()->create(['name' => 'A empty']);
    $low = Location::factory()->create(['name' => 'B low']);
    $stocked = Location::factory()->create(['name' => 'C stocked']);
    $ledger->post($item, $empty, StockTransactionType::Receive, 2, 0);
    $ledger->post($item, $empty, StockTransactionType::Issue, 0, 2);
    $ledger->post($item, $low, StockTransactionType::Receive, 4, 0);
    $ledger->post($item, $stocked, StockTransactionType::Receive, 20, 0);
    InventoryItemPriceChange::factory()->create(['inventory_item_id' => $item->id, 'reason' => 'Older', 'effective_at' => now()->subDay()]);
    InventoryItemPriceChange::factory()->create(['inventory_item_id' => $item->id, 'reason' => 'Newer', 'effective_at' => now()]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.show', $item))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tab', 'overview')
            ->has('balances', 3)
            ->where('balances.0.status', StockStatus::Out->value)
            ->where('balances.1.status', StockStatus::Low->value)
            ->where('balances.2.status', StockStatus::In->value)
            ->where('balances.2.available', 20)
            ->where('item.available', 24)
            ->where('item.stock_status', StockStatus::In->value)
            ->has('movements', 4)
            ->where('movements.0.location_name', 'C stocked')
            ->where('movements.0.balance_after', 20)
            ->has('priceHistory', 2)
            ->where('priceHistory.0.reason', 'Newer')
            ->where('can.update', false)
            ->where('can.post', false)
            ->where('posting', null),
        );
});

test('the show page lists only the fifty most recent ledger entries', function () {
    $item = InventoryItem::factory()->create();
    $location = Location::factory()->create();

    foreach (range(1, 52) as $quantity) {
        app(StockLedgerService::class)->post($item, $location, StockTransactionType::Receive, $quantity, 0);
    }

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.show', $item))
        ->assertInertia(fn (Assert $page) => $page
            ->has('movements', 50)
            ->where('movements.0.quantity_in', 52),
        );
});

test('the show page lists each innovation once, from its active formula, ignoring archived versions', function () {
    $item = InventoryItem::factory()->create();
    $innovation = Innovation::factory()->create();
    $archived = InnovationFormula::factory()->archived()->create(['innovation_id' => $innovation->id]);
    $active = InnovationFormula::factory()->active()->create(['innovation_id' => $innovation->id]);
    $draft = InnovationFormula::factory()->create(['innovation_id' => $innovation->id]);

    foreach ([$archived, $active, $draft] as $formula) {
        InnovationItem::factory()->forFormula($formula)->create(['inventory_item_id' => $item->id]);
    }

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.items.show', $item))
        ->assertInertia(fn (Assert $page) => $page
            ->has('usages', 1)
            ->where('usages.0.formula_label', "v{$active->version} · Active"),
        );
});

test('opening stock requires the stock posting permission', function () {
    $role = Role::create(['name' => 'CATALOGUER', 'guard_name' => 'web']);
    $role->givePermissionTo([AccessPermission::CatalogView->value, AccessPermission::CatalogManage->value]);
    $user = User::factory()->create();
    $user->assignRole($role);
    $location = Location::factory()->create();

    $this->actingAs($user)
        ->post(route('inventory.items.store'), inventoryItemPayload(['opening_quantity' => 5, 'opening_location_id' => $location->id]))
        ->assertSessionHasErrors('opening_quantity');

    $this->post(route('inventory.items.store'), inventoryItemPayload(['name' => 'No stock yet']))
        ->assertSessionHasNoErrors();

    expect(StockLedgerEntry::query()->count())->toBe(0);
});
