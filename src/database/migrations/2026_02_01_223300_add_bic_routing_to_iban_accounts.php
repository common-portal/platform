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
        Schema::table('iban_accounts', function (Blueprint $table) {
            $table->string('bic_routing', 20)->nullable()->after('iban_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iban_accounts', function (Blueprint $table) {
            $table->dropColumn('bic_routing');
        });
    }
};
