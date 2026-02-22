<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_delivery_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_delivery_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_delivery_items', 'description')) {
                $table->text('description')->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_delivery_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_delivery_items', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
            if (Schema::hasColumn('purchase_delivery_items', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
