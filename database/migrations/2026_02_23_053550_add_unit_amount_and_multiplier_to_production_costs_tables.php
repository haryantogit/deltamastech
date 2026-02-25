<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_costs', function (Blueprint $table) {
            $table->decimal('unit_amount', 15, 2)->default(0)->after('account_id');
            $table->decimal('multiplier', 15, 2)->default(1)->after('unit_amount');
        });

        Schema::table('production_order_costs', function (Blueprint $table) {
            $table->decimal('unit_amount', 15, 2)->default(0)->after('account_id');
            $table->decimal('multiplier', 15, 2)->default(1)->after('unit_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_costs', function (Blueprint $table) {
            $table->dropColumn(['unit_amount', 'multiplier']);
        });

        Schema::table('production_order_costs', function (Blueprint $table) {
            $table->dropColumn(['unit_amount', 'multiplier']);
        });
    }
};
