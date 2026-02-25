<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('product_display_type')->default('all'); // all, category, per_product
            $table->string('price_type')->default('markup'); // markup, discount
            $table->decimal('price_adjustment', 15, 2)->default(0);
            $table->string('price_unit')->default('percentage'); // percentage, amount
        });

        // Pivot table for outlet-user relationship
        Schema::create('outlet_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['outlet_id', 'user_id']);
        });

        // Floors / Lantai table
        Schema::create('outlet_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('total_tables')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_floors');
        Schema::dropIfExists('outlet_user');

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['warehouse_id', 'product_display_type', 'price_type', 'price_adjustment', 'price_unit']);
        });
    }
};
