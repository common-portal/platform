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
        Schema::create('iban_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 32)->unique();
            $table->string('account_hash', 32)->index();
            $table->string('iban_friendly_name', 255);
            $table->string('iban_currency_iso3', 3)->default('EUR');
            $table->string('iban_number', 34);
            $table->string('iban_host_bank_hash', 32)->nullable()->index();
            $table->string('creator_member_hash', 32)->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('datetime_created')->useCurrent();
            $table->timestamp('datetime_updated')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iban_accounts');
    }
};
