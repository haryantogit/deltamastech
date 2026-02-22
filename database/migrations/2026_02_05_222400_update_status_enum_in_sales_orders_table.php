<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'delivered' to the enum
        DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('draft', 'confirmed', 'processing', 'completed', 'cancelled', 'delivered') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original statuses (careful if data exists)
        // ideally we map delivered back to something else or just leave it if strict rollback needed
        DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('draft', 'confirmed', 'processing', 'completed', 'cancelled') DEFAULT 'draft'");
    }
};
