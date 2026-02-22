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
        Schema::table('taxes', function (Blueprint $table) {
            $table->string('type')->default('single')->after('rate');
            $table->boolean('is_deduction')->default(false)->after('type');
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('is_deduction');
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('sales_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropForeign(['sales_account_id']);
            $table->dropForeign(['purchase_account_id']);
            $table->dropColumn(['type', 'is_deduction', 'sales_account_id', 'purchase_account_id']);
        });
    }
};
