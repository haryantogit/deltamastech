<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('sales', 'purchase', 'adjustment', 'manufacturing', 'transfer', 'sales_return', 'sales_return_cancel', 'purchase_return', 'purchase_return_cancel') NOT NULL");
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('sales', 'purchase', 'adjustment', 'manufacturing', 'transfer') NOT NULL");
    }
};
