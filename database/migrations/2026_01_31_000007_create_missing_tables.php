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
        // Penjualan Tables
        Schema::create('penawaran_juals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('transaction_number')->nullable();
            $table->date('transaction_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pesanan_juals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('transaction_number')->nullable();
            $table->date('transaction_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pengiriman_juals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('transaction_number')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        // Pembelian Tables
        Schema::create('penawaran_belis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('transaction_number')->nullable();
            $table->date('transaction_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pesanan_belis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('transaction_number')->nullable();
            $table->date('transaction_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pengiriman_belis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('transaction_number')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        // Settings Tables
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('days')->default(0);
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->timestamps();
        });

        Schema::create('expeditions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('expeditions');
        Schema::dropIfExists('units');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('pengiriman_belis');
        Schema::dropIfExists('pesanan_belis');
        Schema::dropIfExists('penawaran_belis');
        Schema::dropIfExists('pengiriman_juals');
        Schema::dropIfExists('pesanan_juals');
        Schema::dropIfExists('penawaran_juals');
    }
};
