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
        Schema::table('journal_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_entries', 'memo')) {
                $table->string('memo')->nullable();
            }
            if (!Schema::hasColumn('journal_entries', 'attachment')) {
                $table->text('attachment')->nullable(); // Using text for compatibility, or json
            }
        });

        Schema::table('journal_items', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_items', 'memo')) {
                $table->string('memo')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'memo')) {
                $table->dropColumn('memo');
            }
            if (Schema::hasColumn('journal_entries', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });

        Schema::table('journal_items', function (Blueprint $table) {
            if (Schema::hasColumn('journal_items', 'memo')) {
                $table->dropColumn('memo');
            }
        });
    }
};
