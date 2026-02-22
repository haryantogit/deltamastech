<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'sales_quotation_id')) {
                $table->foreignId('sales_quotation_id')->nullable()->constrained('sales_quotations')->nullOnDelete()->after('sales_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_quotation_id']);
            $table->dropColumn('sales_quotation_id');
        });
    }
};
