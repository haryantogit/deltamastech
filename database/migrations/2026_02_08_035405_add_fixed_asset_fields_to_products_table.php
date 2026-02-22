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
            $table->boolean('is_fixed_asset')->default(false)->index()->after('type');
            $table->date('purchase_date')->nullable()->after('is_fixed_asset');
            $table->decimal('purchase_price', 15, 2)->nullable()->after('purchase_date');
            $table->integer('useful_life_years')->nullable()->after('purchase_price');
            $table->decimal('salvage_value', 15, 2)->default(0)->after('useful_life_years');
            $table->string('depreciation_method')->default('straight_line')->after('salvage_value');

            $table->foreignId('asset_account_id')->nullable()->after('depreciation_method')->constrained('accounts')->nullOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->nullable()->after('asset_account_id')->constrained('accounts')->nullOnDelete();
            $table->foreignId('depreciation_expense_account_id')->nullable()->after('accumulated_depreciation_account_id')->constrained('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['asset_account_id']);
            $table->dropForeign(['accumulated_depreciation_account_id']);
            $table->dropForeign(['depreciation_expense_account_id']);
            $table->dropColumn([
                'is_fixed_asset',
                'purchase_date',
                'purchase_price',
                'useful_life_years',
                'salvage_value',
                'depreciation_method',
                'asset_account_id',
                'accumulated_depreciation_account_id',
                'depreciation_expense_account_id',
            ]);
        });
    }
};
