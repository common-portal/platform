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
            $table->string('final_settlement_currency_code', 10)->nullable()->after('outgoing_total_fee');
            $table->decimal('final_settlement_amount', 20, 5)->nullable()->after('final_settlement_currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['final_settlement_currency_code', 'final_settlement_amount']);
        });
    }
};
