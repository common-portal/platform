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
        Schema::table('tenant_accounts', function (Blueprint $table) {
            $table->string('customer_support_email', 255)->nullable()->after('primary_contact_email_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_accounts', function (Blueprint $table) {
            $table->dropColumn('customer_support_email');
        });
    }
};
