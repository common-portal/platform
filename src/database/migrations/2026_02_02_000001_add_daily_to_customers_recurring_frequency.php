<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE customers DROP CONSTRAINT IF EXISTS customers_recurring_frequency_check");
        DB::statement("ALTER TABLE customers ALTER COLUMN recurring_frequency TYPE VARCHAR(20)");
        DB::statement("UPDATE customers SET recurring_frequency = NULL WHERE recurring_frequency NOT IN ('daily', 'weekly', 'twice_monthly', 'monthly')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE customers ALTER COLUMN recurring_frequency TYPE VARCHAR(20)");
    }
};
