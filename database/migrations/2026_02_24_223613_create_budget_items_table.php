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
        Schema::create('budget_items', function (Blueprint $col) {
            $col->id();
            $col->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $col->foreignId('account_id')->constrained()->cascadeOnDelete();
            $col->decimal('amount', 20, 2);
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
