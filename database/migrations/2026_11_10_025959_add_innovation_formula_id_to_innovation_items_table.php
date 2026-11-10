<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bills of materials become versioned formulas. Every innovation that already has BOM lines
 * gets an ACTIVE version 1 formula holding those lines (with its estimated unit cost), and items
 * are unique per formula.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('innovation_items', function (Blueprint $table) {
            $table->foreignId('innovation_formula_id')->nullable()->after('innovation_id')
                ->constrained('innovation_formulas')->cascadeOnDelete();
        });

        $now = now();

        DB::table('innovation_items')
            ->select('innovation_id')
            ->distinct()
            ->orderBy('innovation_id')
            ->pluck('innovation_id')
            ->each(function (int $innovationId) use ($now): void {
                $unitCost = round((float) DB::table('innovation_items')
                    ->join('inventory_items', 'inventory_items.id', '=', 'innovation_items.inventory_item_id')
                    ->where('innovation_items.innovation_id', $innovationId)
                    ->where('innovation_items.is_optional', false)
                    ->sum(DB::raw('innovation_items.quantity_required * coalesce(inventory_items.unit_cost, 0)')), 2);

                $formulaId = DB::table('innovation_formulas')->insertGetId([
                    'innovation_id' => $innovationId,
                    'version' => 1,
                    'yield_quantity' => 1,
                    'status' => 'ACTIVE',
                    'notes' => 'Created from the bill of materials when formulas were introduced.',
                    'activated_at' => $now,
                    'estimated_unit_cost' => $unitCost,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('innovation_items')->where('innovation_id', $innovationId)->update(['innovation_formula_id' => $formulaId]);
                DB::table('innovations')->where('id', $innovationId)->update(['estimated_unit_cost' => $unitCost]);
            });

        Schema::table('innovation_items', function (Blueprint $table) {
            $table->unsignedBigInteger('innovation_formula_id')->nullable(false)->change();
            $table->unique(['innovation_formula_id', 'inventory_item_id'], 'innovation_items_formula_item_unique');
        });

        Schema::table('innovation_items', function (Blueprint $table) {
            $table->dropUnique(['innovation_id', 'inventory_item_id']);
        });
    }

    /**
     * Reverse the migrations. Only the lines of each innovation's latest formula are kept.
     */
    public function down(): void
    {
        $latestFormulaIds = DB::table('innovation_formulas')
            ->selectRaw('max(id) as id')
            ->groupBy('innovation_id')
            ->pluck('id');

        DB::table('innovation_items')->whereNotIn('innovation_formula_id', $latestFormulaIds)->delete();

        Schema::table('innovation_items', function (Blueprint $table) {
            $table->unique(['innovation_id', 'inventory_item_id']);
        });

        Schema::table('innovation_items', function (Blueprint $table) {
            $table->dropForeign(['innovation_formula_id']);
            $table->dropUnique('innovation_items_formula_item_unique');
            $table->dropColumn('innovation_formula_id');
        });
    }
};
