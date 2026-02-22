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
            $table->foreignId('credit_account_id')->nullable()->after('depreciation_expense_account_id')->constrained('accounts')->nullOnDelete();
            $table->string('reference')->nullable()->after('credit_account_id');
            $table->boolean('has_depreciation')->default(false)->after('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['credit_account_id']);
            $table->dropColumn(['credit_account_id', 'reference', 'has_depreciation']);
        });
    }
};
