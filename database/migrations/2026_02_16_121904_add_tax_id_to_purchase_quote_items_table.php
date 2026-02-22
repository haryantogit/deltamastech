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
        Schema::table('purchase_quote_items', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete()->after('discount_percent');
            $table->string('tax_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quote_items', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropColumn('tax_id');
            // We cannot easily revert tax_name to not nullable without data, so we leave it as is or handle separately
        });
    }
};
