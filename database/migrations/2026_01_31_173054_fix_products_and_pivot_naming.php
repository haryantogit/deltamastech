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
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'cost_of_goods')) {
                $table->decimal('cost_of_goods', 15, 2)->default(0)->after('sell_price');
            }
        });

        if (Schema::hasTable('product_materials')) {
            Schema::table('product_materials', function (Blueprint $table) {
                if (Schema::hasColumn('product_materials', 'quantity_needed')) {
                    $table->renameColumn('quantity_needed', 'quantity');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['slug', 'cost_of_goods']);
        });

        if (Schema::hasTable('product_materials')) {
            Schema::table('product_materials', function (Blueprint $table) {
                if (Schema::hasColumn('product_materials', 'quantity')) {
                    $table->renameColumn('quantity', 'quantity_needed');
                }
            });
        }
    }
};
