<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('one_time_password_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_member_id')->constrained('platform_members')->onDelete('cascade');
            $table->string('hashed_verification_code', 255);
            $table->timestamp('token_expires_at_timestamp');
            $table->timestamp('token_used_at_timestamp')->nullable();
            $table->timestamp('created_at_timestamp')->useCurrent();

            $table->index('platform_member_id', 'idx_otp_by_member');
        });

        // Partial index for valid (unused) tokens
        DB::statement('CREATE INDEX idx_otp_valid ON one_time_password_tokens(platform_member_id, token_expires_at_timestamp) WHERE token_used_at_timestamp IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('one_time_password_tokens');
    }
};
