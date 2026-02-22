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
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->boolean('is_bank_transaction')->default(false)->after('description');
            $table->boolean('is_reconciled')->default(false)->after('is_bank_transaction');
            $table->boolean('is_recurring')->default(false)->after('is_reconciled');
            $table->string('status')->default('approved')->after('is_recurring'); // approved, pending, rejected
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['is_bank_transaction', 'is_reconciled', 'is_recurring', 'status']);
        });
    }
};
