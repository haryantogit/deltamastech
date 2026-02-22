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
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoice_items', 'account_id')) {
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_invoice_items', 'description')) {
                $table->string('description')->nullable();
            }
            $table->foreignId('product_id')->nullable()->change();
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            // sales_invoice_items already has account_id and description, just verify product_id
            $table->foreignId('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_items', 'account_id')) {
                $table->dropForeign(['account_id']);
                $table->dropColumn(['account_id']);
            }
            if (Schema::hasColumn('purchase_invoice_items', 'description')) {
                $table->dropColumn(['description']);
            }
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
