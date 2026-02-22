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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('salutation')->nullable()->after('name');
            $table->string('mobile')->nullable()->after('phone');
            $table->string('fax')->nullable()->after('mobile');

            // Address Info
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('province');
            $table->string('country')->nullable()->after('postal_code');

            // Financial Settings
            $table->foreignId('receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // Bank Info
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_account_holder')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['receivable_account_id']);
            $table->dropForeign(['payable_account_id']);
            $table->dropColumn([
                'salutation',
                'mobile',
                'fax',
                'province',
                'postal_code',
                'country',
                'receivable_account_id',
                'payable_account_id',
                'bank_name',
                'bank_account_no',
                'bank_account_holder',
            ]);
        });
    }
};
