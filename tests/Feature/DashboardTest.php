<?php

use App\Enums\AccessPermission;
use App\Enums\BuildOrderStatus;
use App\Enums\InnovationStatus;
use App\Enums\InnovationVisibility;
use App\Enums\InventoryItemStatus;
use App\Enums\StockTransactionType;
use App\Models\BuildOrder;
use App\Models\Innovation;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\User;
use App\Services\StockLedgerService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create(['name' => 'Alex Rivera']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.name', 'Alex Rivera'),
        );
});

test('the dashboard does not render the user name as raw html', function () {
    $user = User::factory()->create(['name' => 'Alex <script>alert(1)</script>']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('the dashboard summarises innovations and lists only low-stock items', function () {
    $this->seed(RolePermissionSeeder::class);

    Innovation::factory()->create(['status' => InnovationStatus::Deployed, 'visibility' => InnovationVisibility::Public, 'is_featured' => true]);
    Innovation::factory()->count(2)->create(['status' => InnovationStatus::Concept]);
    Innovation::factory()->create(['status' => InnovationStatus::Concept])->delete();

    $low = InventoryItem::factory()->create(['name' => 'Low', 'quantity_on_hand' => 2, 'minimum_stock' => 5]);
    InventoryItem::factory()->create(['name' => 'Healthy', 'quantity_on_hand' => 10, 'minimum_stock' => 5]);
    InventoryItem::factory()->create(['name' => 'No threshold', 'quantity_on_hand' => 0, 'minimum_stock' => 0]);
    InventoryItem::factory()->create(['name' => 'Discontinued', 'quantity_on_hand' => 0, 'minimum_stock' => 5, 'status' => InventoryItemStatus::Discontinued]);

    $this->actingAs(User::factory()->manager()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('innovationStats.total', 3)
            ->where('innovationStats.public', 1)
            ->where('innovationStats.internal', 2)
            ->where('innovationStats.featured', 1)
            ->where('innovationStats.byStatus', fn ($statuses) => collect($statuses)->firstWhere('value', InnovationStatus::Concept->value)['count'] === 2)
            ->missing('lowStockItems')
            ->loadDeferredProps('activity', fn (Assert $reload) => $reload
                ->has('lowStockItems', 1)
                ->where('lowStockItems.0.id', $low->id)
                ->has('recentInnovations', 3),
            )
            ->where('shortcuts', fn ($shortcuts) => collect($shortcuts)->pluck('key')->all() === ['create-innovation', 'manage-items', 'post-stock', 'build-orders']),
        );
});

test('viewers only get data and shortcuts their role allows', function () {
    $this->seed(RolePermissionSeeder::class);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('innovationStats')
            ->where('shortcuts', []),
        );

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('innovationStats', null)
            ->where('lowStockItems', null)
            ->where('recentInnovations', null)
            ->where('shortcuts', []),
        );
});

test('the database seeder loads demo content that the dashboard can summarise', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Innovation::query()->count())->toBe(12)
        ->and(Innovation::query()->where('visibility', InnovationVisibility::Public)->where('is_featured', true)->count())->toBeGreaterThan(0)
        ->and(InventoryItem::query()->count())->toBe(19);

    $this->actingAs(User::query()->where('email', 'super_admin@elevenforge.test')->firstOrFail())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps('activity', fn (Assert $reload) => $reload
                ->where('lowStockItems', fn ($items) => count($items) > 0),
            ),
        );
});

test('sample innovations never overwrite real records with registry codes', function () {
    $this->seed(DatabaseSeeder::class);
    Innovation::query()->where('innovation_code', 'like', 'DEMO-%')->forceDelete();
    $real = Innovation::factory()->create(['innovation_code' => 'INV-2026-0001', 'name' => 'EcoTrack']);

    $this->seed(DatabaseSeeder::class);

    expect($real->fresh()->name)->toBe('EcoTrack')
        ->and(Innovation::query()->count())->toBe(13);
});

test('stock and production figures can be narrowed to one location', function () {
    $this->seed(RolePermissionSeeder::class);
    $ledger = app(StockLedgerService::class);
    $warehouse = Location::factory()->create();
    $workshop = Location::factory()->create();
    $bolts = InventoryItem::factory()->create(['unit_cost' => 10, 'minimum_stock' => 5]);
    $ledger->post($bolts, $warehouse, StockTransactionType::Receive, 20, 0);
    $ledger->post($bolts, $workshop, StockTransactionType::Receive, 3, 0);
    BuildOrder::factory()->create(['location_id' => $warehouse->id, 'status' => BuildOrderStatus::Planned]);
    BuildOrder::factory()->create(['location_id' => $workshop->id, 'status' => BuildOrderStatus::Completed]);

    $this->actingAs(User::factory()->manager()->create());

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stockStats.value', 230)
            ->where('stockStats.low', 0)
            ->has('locations', 3)
            ->loadDeferredProps('activity', fn (Assert $reload) => $reload
                ->where('activeBuildOrders.count', 1)
                ->has('recentMovements', 2),
            ),
        );

    $this->get(route('dashboard', ['location' => $workshop->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.location', $workshop->id)
            ->where('stockStats.value', 30)
            ->where('stockStats.low', 1)
            ->loadDeferredProps('activity', fn (Assert $reload) => $reload
                ->where('activeBuildOrders.count', 0)
                ->has('recentMovements', 1)
                ->where('lowStockItems.0.id', $bolts->id),
            ),
        );
});

test('users without stock access get no stock figures or locations', function () {
    $this->seed(RolePermissionSeeder::class);
    $role = Role::create(['name' => 'INNOVATOR_ONLY', 'guard_name' => 'web']);
    $role->givePermissionTo(AccessPermission::InnovationView->value);
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stockStats', null)
            ->where('locations', null)
            ->where('recentMovements', null),
        );
});
