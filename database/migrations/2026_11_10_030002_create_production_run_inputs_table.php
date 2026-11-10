<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable snapshots of what each production run consumed and what it cost at the time.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_run_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->decimal('formula_quantity', 14, 3);
            $table->decimal('consumed_quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('line_cost', 14, 2);
            $table->timestamps();
            $table->unique(['production_run_id', 'inventory_item_id'], 'production_run_inputs_run_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_run_inputs');
    }
};
