<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50)->unique();
            $table->timestamp('posted_at')->index();
            $table->string('transaction_type', 30)->index();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_in', 14, 3)->default(0);
            $table->decimal('quantity_out', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_value', 14, 2)->default(0);
            $table->decimal('balance_after', 14, 3);
            $table->unsignedBigInteger('production_run_id')->nullable()->index();
            $table->unsignedBigInteger('build_order_id')->nullable()->index();
            $table->foreignId('reversal_of_entry_id')->nullable()->unique()->constrained('stock_ledger_entries')->restrictOnDelete();
            $table->string('reference_number', 100)->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['inventory_item_id', 'location_id', 'posted_at'], 'stock_ledger_item_location_posted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledger_entries');
    }
};
