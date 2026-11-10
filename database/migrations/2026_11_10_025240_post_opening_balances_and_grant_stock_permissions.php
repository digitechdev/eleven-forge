<?php

use App\Enums\AccessPermission;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Moves existing stock onto the ledger: each item's current quantity becomes an opening
 * balance at a new "Main" location, so balances and ledger agree from the first day.
 * Also grants the new stock and production permissions to the roles that manage the catalog.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $locationId = DB::table('locations')->insertGetId([
            'code' => 'MAIN',
            'name' => 'Main',
            'description' => 'Default location. Stock that existed before locations were introduced was placed here.',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('inventory_items')
            ->whereNull('deleted_at')
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('id')
            ->get(['id', 'quantity_on_hand', 'unit_cost'])
            ->each(function (object $item) use ($locationId, $now): void {
                $quantity = (float) $item->quantity_on_hand;
                $unitCost = (float) ($item->unit_cost ?? 0);

                DB::table('inventory_balances')->insert([
                    'inventory_item_id' => $item->id,
                    'location_id' => $locationId,
                    'on_hand_quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('stock_ledger_entries')->insert([
                    'entry_number' => 'LED-'.Str::upper((string) Str::ulid()),
                    'posted_at' => $now,
                    'transaction_type' => 'OPENING_BALANCE',
                    'inventory_item_id' => $item->id,
                    'location_id' => $locationId,
                    'quantity_in' => $quantity,
                    'quantity_out' => 0,
                    'unit_cost' => $unitCost,
                    'total_value' => round($quantity * $unitCost, 2),
                    'balance_after' => $quantity,
                    'reference_number' => 'MIGRATION',
                    'notes' => 'Opening balance carried over from the stock snapshot.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        $this->grantStockPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('stock_ledger_entries')->where('reference_number', 'MIGRATION')->delete();
        DB::table('inventory_balances')->delete();
        DB::table('locations')->where('code', 'MAIN')->delete();
    }

    /**
     * Give roles that manage the catalog the new stock and production permissions.
     */
    private function grantStockPermissions(): void
    {
        if (! Schema::hasTable('roles') || DB::table('roles')->doesntExist()) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            Permission::findOrCreate(AccessPermission::StockPost->value, 'web'),
            Permission::findOrCreate(AccessPermission::ProductionManage->value, 'web'),
        ];

        foreach ([UserRole::Manager, UserRole::Admin, UserRole::SuperAdmin] as $role) {
            Role::query()->where('name', $role->value)->where('guard_name', 'web')->first()?->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
