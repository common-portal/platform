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
            // Incoming fees
            $table->decimal('incoming_fixed_fee', 20, 5)->default(0)->after('settlement_amount');
            $table->decimal('incoming_percentage_fee', 10, 2)->default(0)->after('incoming_fixed_fee');
            $table->decimal('incoming_minimum_fee', 20, 5)->default(0)->after('incoming_percentage_fee');
            $table->decimal('incoming_total_fee', 20, 5)->default(0)->after('incoming_minimum_fee');
            
            // Outgoing fees
            $table->decimal('outgoing_fixed_fee', 20, 5)->default(0)->after('incoming_total_fee');
            $table->decimal('outgoing_percentage_fee', 10, 2)->default(0)->after('outgoing_fixed_fee');
            $table->decimal('outgoing_minimum_fee', 20, 5)->default(0)->after('outgoing_percentage_fee');
            $table->decimal('outgoing_total_fee', 20, 5)->default(0)->after('outgoing_minimum_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'incoming_fixed_fee',
                'incoming_percentage_fee',
                'incoming_minimum_fee',
                'incoming_total_fee',
                'outgoing_fixed_fee',
                'outgoing_percentage_fee',
                'outgoing_minimum_fee',
                'outgoing_total_fee',
            ]);
        });
    }
};
