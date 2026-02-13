<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_last_active_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_member_id')->unique()->constrained('platform_members')->onDelete('cascade');
            $table->foreignId('tenant_account_id')->constrained('tenant_accounts')->onDelete('cascade');
            $table->timestamp('updated_at_timestamp')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_last_active_accounts');
    }
};
