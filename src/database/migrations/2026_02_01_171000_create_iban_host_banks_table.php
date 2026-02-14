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
        Schema::create('iban_host_banks', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 32)->unique();
            $table->string('host_bank_name', 255);
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
        Schema::dropIfExists('iban_host_banks');
    }
};
