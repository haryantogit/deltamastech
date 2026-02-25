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
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('reference_number');
            $table->boolean('tax_inclusive')->default(false)->after('is_recurring');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('sub_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['reference', 'tax_inclusive', 'discount_amount']);
        });
    }
};
