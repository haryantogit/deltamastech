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
        $tables = [
            'sales_orders',
            'sales_invoices',
            'sales_deliveries',
            'purchase_orders',
            'purchase_invoices',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'attachments')) {
                    $table->json('attachments')->nullable()->after('notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'sales_orders',
            'sales_invoices',
            'sales_deliveries',
            'purchase_orders',
            'purchase_invoices',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'attachments')) {
                    $table->dropColumn('attachments');
                }
            });
        }
    }
};
