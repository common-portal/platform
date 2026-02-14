<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Payment source details
            $table->string('payment_source_name', 255)->nullable()->after('fiat_bank_country');
            $table->string('payment_source_iban', 34)->nullable()->after('payment_source_name');
            $table->string('payment_source_bic', 20)->nullable()->after('payment_source_iban');
            
            // Payment destination details
            $table->string('payment_destination_iban', 34)->nullable()->after('payment_source_bic');
            $table->string('payment_destination_bic', 20)->nullable()->after('payment_destination_iban');
            $table->string('payment_destination_name', 255)->nullable()->after('payment_destination_bic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_source_name',
                'payment_source_iban',
                'payment_source_bic',
                'payment_destination_iban',
                'payment_destination_bic',
                'payment_destination_name',
            ]);
        });
    }
};
