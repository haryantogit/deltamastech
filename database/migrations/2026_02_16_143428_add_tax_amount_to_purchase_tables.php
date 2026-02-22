<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // PurchaseOrder already has tax_amount

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('sub_total');
            }
        });

        Schema::table('purchase_quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_quotes', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('sub_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
        });

        Schema::table('purchase_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_quotes', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
        });
    }
};
