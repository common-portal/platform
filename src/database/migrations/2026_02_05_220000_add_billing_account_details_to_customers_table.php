<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('billing_name_on_account', 255)->nullable()->after('customer_bic');
            $table->string('billing_bank_name', 255)->nullable()->after('billing_name_on_account');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['billing_name_on_account', 'billing_bank_name']);
        });
    }
};
