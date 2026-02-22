<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('shipping_methods')) {
            Schema::create('shipping_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('taggables')) {
            Schema::create('taggables', function (Blueprint $table) {
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->morphs('taggable');
            });
        }

        if (!Schema::hasTable('payment_terms')) {
            Schema::create('payment_terms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('days');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Careful with dropping shared tables, but standard practice for fresh migration
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('shipping_methods');
        // Schema::dropIfExists('tags'); // Might be used elsewhere
        // Schema::dropIfExists('units'); // Might be used elsewhere
    }
};
