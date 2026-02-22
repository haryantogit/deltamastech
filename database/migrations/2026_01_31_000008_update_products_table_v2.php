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
        } else {
            Schema::create('product_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('material_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity', 15, 4);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_bundles')) {
            Schema::create('product_bundles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // Parent Bundle
                $table->foreignId('item_id')->constrained('products')->cascadeOnDelete();   // Component
                $table->decimal('quantity', 15, 4);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost_of_goods');
        });

        Schema::table('product_materials', function (Blueprint $table) {
            $table->renameColumn('quantity', 'quantity_needed');
        });

        // product_bundles table is new so we can drop it, but it was also in v1...
        // safer to just leave it or handle it carefully.
    }
};
