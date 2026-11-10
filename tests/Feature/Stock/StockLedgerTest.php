<?php

use App\Enums\StockTransactionType;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use App\Models\User;
use App\Services\StockLedgerService;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->item = InventoryItem::factory()->create(['unit_cost' => 40]);
    $this->location = Location::factory()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function stockPosting(array $overrides = []): array
{
    return [
        'inventory_item_id' => test()->item->id,
        'location_id' => test()->location->id,
        'transaction_type' => StockTransactionType::Receive->value,
        'quantity' => 10,
        'unit_cost' => 40,
        'posted_at' => now()->toDateString(),
        'reference_number' => 'PO-1',
        'notes' => null,
        ...$overrides,
    ];
}

function onHandAt(InventoryItem $item, Location $location): float
{
    return (float) InventoryBalance::query()
        ->where('inventory_item_id', $item->id)
        ->where('location_id', $location->id)
        ->value('on_hand_quantity');
}

test('viewers can browse the ledger but cannot post or reverse', function () {
    $entry = app(StockLedgerService::class)->post($this->item, $this->location, StockTransactionType::Receive, 5, 0);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.ledger.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/ledger/Index')
            ->has('entries.data', 1)
            ->where('entries.data.0.can_reverse', false)
            ->where('can.post', false),
        );

    $this->post(route('inventory.ledger.store'), stockPosting())->assertForbidden();
    $this->post(route('inventory.ledger.reverse', $entry), ['reason' => 'Mistake'])->assertForbidden();

    expect(StockLedgerEntry::query()->count())->toBe(1);
});

test('managers receive and issue stock, updating the location balance and item total', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->post(route('inventory.ledger.store'), stockPosting())->assertSessionHasNoErrors();
    $this->post(route('inventory.ledger.store'), stockPosting([
        'transaction_type' => StockTransactionType::Issue->value,
        'quantity' => 4,
        'unit_cost' => null,
    ]))->assertSessionHasNoErrors();

    $issue = StockLedgerEntry::query()->where('transaction_type', StockTransactionType::Issue)->firstOrFail();

    expect(onHandAt($this->item, $this->location))->toBe(6.0)
        ->and((float) $this->item->fresh()->quantity_on_hand)->toBe(6.0)
        ->and((float) $issue->quantity_out)->toBe(4.0)
        ->and((float) $issue->balance_after)->toBe(6.0);
});

test('issuing more than is available returns a quantity error and posts nothing', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->post(route('inventory.ledger.store'), stockPosting([
        'transaction_type' => StockTransactionType::Issue->value,
        'quantity' => 3,
    ]))->assertSessionHasErrors('quantity');

    expect(StockLedgerEntry::query()->count())->toBe(0)
        ->and((float) $this->item->fresh()->quantity_on_hand)->toBe(0.0);
});

test('adjustments need a direction, reason, and reference number', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->post(route('inventory.ledger.store'), stockPosting([
        'transaction_type' => StockTransactionType::Adjustment->value,
        'reference_number' => null,
        'notes' => null,
    ]))->assertSessionHasErrors(['direction', 'reference_number', 'notes']);

    $this->post(route('inventory.ledger.store'), stockPosting([
        'transaction_type' => StockTransactionType::Adjustment->value,
        'direction' => 'increase',
        'reference_number' => 'CNT-7',
        'notes' => 'Cycle count found extra units',
    ]))->assertSessionHasNoErrors();

    expect(onHandAt($this->item, $this->location))->toBe(10.0);
});

test('postings are refused at inactive locations, for future dates, and below the unit precision', function () {
    $this->actingAs(User::factory()->manager()->create());
    $inactive = Location::factory()->create(['is_active' => false]);

    $this->post(route('inventory.ledger.store'), stockPosting([
        'location_id' => $inactive->id,
        'posted_at' => now()->addDay()->toDateString(),
        'quantity' => 1.5,
    ]))->assertSessionHasErrors(['location_id', 'posted_at']);

    $this->post(route('inventory.ledger.store'), stockPosting(['quantity' => 1.5]))
        ->assertSessionHasErrors('quantity');

    expect(StockLedgerEntry::query()->count())->toBe(0);
});

test('an entry can be reversed once with a reason', function () {
    $this->actingAs(User::factory()->manager()->create());
    $entry = app(StockLedgerService::class)->post($this->item, $this->location, StockTransactionType::Receive, 5, 0);

    $this->post(route('inventory.ledger.reverse', $entry), ['reason' => ''])->assertSessionHasErrors('reason');
    $this->post(route('inventory.ledger.reverse', $entry), ['reason' => 'Wrong item'])->assertSessionHasNoErrors();
    $this->post(route('inventory.ledger.reverse', $entry), ['reason' => 'Again'])->assertForbidden();

    expect($entry->reversal()->count())->toBe(1)
        ->and(onHandAt($this->item, $this->location))->toBe(0.0);
});

test('the ledger filters by item and location', function () {
    $ledger = app(StockLedgerService::class);
    $otherItem = InventoryItem::factory()->create();
    $otherLocation = Location::factory()->create();
    $match = $ledger->post($this->item, $this->location, StockTransactionType::Receive, 2, 0);
    $ledger->post($this->item, $otherLocation, StockTransactionType::Receive, 2, 0);
    $ledger->post($otherItem, $this->location, StockTransactionType::Receive, 2, 0);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('inventory.ledger.index', ['item' => $this->item->id, 'location' => $this->location->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.entry_number', $match->entry_number),
        );
});
