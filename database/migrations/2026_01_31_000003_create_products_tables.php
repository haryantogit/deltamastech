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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('type'); // standard, service, manufacturing, bundle
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->decimal('stock', 15, 2)->default(0);
            $table->decimal('min_stock', 15, 2)->default(0);
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        Schema::create('product_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // The manufacturing product
            $table->foreignId('material_id')->constrained('products')->cascadeOnDelete(); // The raw material
            $table->decimal('quantity_needed', 15, 4);
            $table->timestamps();
        });

        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // The bundle product
            $table->foreignId('item_id')->constrained('products')->cascadeOnDelete(); // The item in the bundle
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('product_materials');
        Schema::dropIfExists('products');
    }
};
