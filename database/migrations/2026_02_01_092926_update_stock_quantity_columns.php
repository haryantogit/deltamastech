<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change(); // Ensure default signed (no unsigned())
        });
        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
        });
        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
        });
    }
};
