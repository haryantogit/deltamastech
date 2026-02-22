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
            // Booleans
            $table->boolean('can_be_purchased')->default(true);
            $table->boolean('can_be_sold')->default(true);
            $table->boolean('track_inventory')->default(true);

            // Foreign Keys for Accounts (Nullable)
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // Tax placeholders (integer for now, or foreignId if tax table existed)
            $table->integer('purchase_tax_id')->nullable();
            $table->integer('sales_tax_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['purchase_account_id']);
            $table->dropForeign(['sales_account_id']);
            $table->dropForeign(['inventory_account_id']);
            $table->dropColumn([
                'can_be_purchased',
                'can_be_sold',
                'track_inventory',
                'purchase_account_id',
                'sales_account_id',
                'inventory_account_id',
                'purchase_tax_id',
                'sales_tax_id'
            ]);
        });
    }
};
