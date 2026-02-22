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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('status')->change();
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('status')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Enums can't be easily reversed to specific values without potential data loss, 
        // but for safety we'll keep them as string or try to restore.
        // For this project, string is preferred for flexibility.
    }
};
