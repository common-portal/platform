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
        Schema::create('platform_members', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            $table->string('login_email_address', 255)->unique();
            $table->string('hashed_login_password', 255)->nullable();
            $table->string('member_first_name', 255)->default('');
            $table->string('member_last_name', 255)->default('');
            $table->string('profile_avatar_image_path', 500)->nullable();
            $table->string('preferred_language_code', 10)->default('en');
            $table->boolean('is_platform_administrator')->default(false);
            $table->timestamp('email_verified_at_timestamp')->nullable();
            $table->rememberToken();
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent();
        });

        // Partial index for platform administrators
        DB::statement('CREATE INDEX idx_platform_members_is_admin ON platform_members(is_platform_administrator) WHERE is_platform_administrator = TRUE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_members');
    }
};
