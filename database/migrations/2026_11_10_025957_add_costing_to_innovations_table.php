<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Internal costing for innovations built in-house. Never exposed on the public showcase.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('innovations', function (Blueprint $table) {
            $table->decimal('markup_percentage', 5, 2)->nullable()->after('is_featured');
            $table->decimal('estimated_unit_cost', 14, 2)->nullable()->after('markup_percentage');
            $table->decimal('suggested_selling_price', 14, 2)->nullable()->after('estimated_unit_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('innovations', function (Blueprint $table) {
            $table->dropColumn(['markup_percentage', 'estimated_unit_cost', 'suggested_selling_price']);
        });
    }
};
