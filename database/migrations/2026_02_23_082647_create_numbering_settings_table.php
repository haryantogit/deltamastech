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
        Schema::create('numbering_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('module')->nullable();
            $table->string('format')->default('[NUMBER]'); // e.g. INV/[NUMBER]
            $table->integer('current_number')->default(0);
            $table->integer('pad_length')->default(5);
            $table->string('reset_behavior')->default('never'); // never, monthly, yearly
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numbering_settings');
    }
};
