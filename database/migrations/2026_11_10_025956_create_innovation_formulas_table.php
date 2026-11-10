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
        Schema::create('innovation_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('innovation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->decimal('yield_quantity', 14, 3)->default(1);
            $table->string('status', 20)->default('DRAFT')->index();
            $table->text('notes')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->decimal('estimated_unit_cost', 14, 2)->nullable();
            $table->timestamps();
            $table->unique(['innovation_id', 'version'], 'innovation_formulas_innovation_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('innovation_formulas');
    }
};
