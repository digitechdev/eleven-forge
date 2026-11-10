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
        Schema::create('production_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_number', 30)->unique();
            $table->foreignId('build_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('innovation_id')->constrained()->restrictOnDelete();
            $table->foreignId('innovation_formula_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->decimal('output_quantity', 14, 3);
            $table->decimal('total_input_cost', 14, 2);
            $table->decimal('unit_production_cost', 14, 2);
            $table->decimal('markup_percentage', 5, 2)->nullable();
            $table->decimal('suggested_selling_price', 14, 2)->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_runs');
    }
};
