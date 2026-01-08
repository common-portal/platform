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
        // Create enum types if not exists
        DB::statement("DO $$ BEGIN CREATE TYPE account_membership_role_enum AS ENUM ('account_owner', 'account_administrator', 'account_team_member'); EXCEPTION WHEN duplicate_object THEN null; END $$;");
        DB::statement("DO $$ BEGIN CREATE TYPE membership_status_enum AS ENUM ('awaiting_acceptance', 'membership_active', 'membership_revoked'); EXCEPTION WHEN duplicate_object THEN null; END $$;");

        Schema::create('tenant_account_memberships', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            $table->foreignId('tenant_account_id')->constrained('tenant_accounts')->onDelete('cascade');
            $table->foreignId('platform_member_id')->constrained('platform_members')->onDelete('cascade');
            $table->jsonb('granted_permission_slugs')->default('[]');
            $table->timestamp('membership_accepted_at_timestamp')->nullable();
            $table->timestamp('membership_revoked_at_timestamp')->nullable();
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent();

            $table->unique(['tenant_account_id', 'platform_member_id']);
            $table->index('platform_member_id', 'idx_memberships_by_member');
            $table->index('tenant_account_id', 'idx_memberships_by_account');
        });

        // Add enum columns separately for PostgreSQL
        DB::statement("ALTER TABLE tenant_account_memberships ADD COLUMN account_membership_role account_membership_role_enum NOT NULL DEFAULT 'account_team_member'");
        DB::statement("ALTER TABLE tenant_account_memberships ADD COLUMN membership_status membership_status_enum NOT NULL DEFAULT 'awaiting_acceptance'");

        // Partial index for active memberships
        DB::statement("CREATE INDEX idx_memberships_active ON tenant_account_memberships(membership_status) WHERE membership_status = 'membership_active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_account_memberships');
        DB::statement('DROP TYPE IF EXISTS account_membership_role_enum');
        DB::statement('DROP TYPE IF EXISTS membership_status_enum');
    }
};
