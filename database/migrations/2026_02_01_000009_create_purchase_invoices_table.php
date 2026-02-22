<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('purchase_invoices')) {
            Schema::create('purchase_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('number')->unique();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->foreignId('supplier_id')->constrained('contacts')->cascadeOnDelete();
                $table->date('date');
                $table->date('due_date')->nullable();
                $table->enum('status', ['draft', 'posted', 'paid', 'void'])->default('posted');
                $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
