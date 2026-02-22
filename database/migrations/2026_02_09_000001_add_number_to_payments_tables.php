<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->string('number')->nullable()->after('debt_id');
        });

        Schema::table('receivable_payments', function (Blueprint $table) {
            $table->string('number')->nullable()->after('receivable_id');
        });
    }

    public function down(): void
    {
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropColumn('number');
        });

        Schema::table('receivable_payments', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
