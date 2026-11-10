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
        Schema::table('stock_ledger_entries', function (Blueprint $table) {
            $table->foreign('production_run_id')->references('id')->on('production_runs')->restrictOnDelete();
            $table->foreign('build_order_id')->references('id')->on('build_orders')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['production_run_id']);
            $table->dropForeign(['build_order_id']);
        });
    }
};
