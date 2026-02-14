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
            $table->decimal('exchange_fixed_fee', 20, 5)->default(0)->after('settlement_amount');
            $table->decimal('exchange_percentage_fee', 10, 2)->default(0)->after('exchange_fixed_fee');
            $table->decimal('exchange_minimum_fee', 20, 5)->default(0)->after('exchange_percentage_fee');
            $table->decimal('exchange_total_fee', 20, 5)->default(0)->after('exchange_minimum_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'exchange_fixed_fee',
                'exchange_percentage_fee',
                'exchange_minimum_fee',
                'exchange_total_fee',
            ]);
        });
    }
};
