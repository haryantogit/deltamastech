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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('depreciation_rate', 5, 2)->nullable()->after('useful_life_years');
            $table->integer('useful_life_months')->nullable()->after('depreciation_rate');
            $table->date('depreciation_start_date')->nullable()->after('useful_life_months');
            $table->decimal('accumulated_depreciation_value', 15, 2)->default(0)->after('depreciation_start_date');
            $table->decimal('cost_limit', 15, 2)->default(0)->after('accumulated_depreciation_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'depreciation_rate',
                'useful_life_months',
                'depreciation_start_date',
                'accumulated_depreciation_value',
                'cost_limit',
            ]);
        });
    }
};
