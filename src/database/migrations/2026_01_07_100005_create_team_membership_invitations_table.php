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
        // Create enum type if not exists
        DB::statement("DO $$ BEGIN CREATE TYPE invitation_status_enum AS ENUM ('invitation_pending', 'invitation_accepted', 'invitation_expired'); EXCEPTION WHEN duplicate_object THEN null; END $$;");

        Schema::create('team_membership_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            $table->foreignId('tenant_account_id')->constrained('tenant_accounts')->onDelete('cascade');
            $table->string('invited_email_address', 255);
            $table->foreignId('invited_by_member_id')->constrained('platform_members')->onDelete('cascade');
            $table->integer('invitation_resend_count')->default(0);
            $table->timestamp('invitation_last_sent_at_timestamp')->useCurrent();
            $table->timestamp('invitation_accepted_at_timestamp')->nullable();
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent();

            $table->unique(['tenant_account_id', 'invited_email_address']);
            $table->index('invited_email_address', 'idx_invitations_by_email');
        });

        // Add enum column separately for PostgreSQL
        DB::statement("ALTER TABLE team_membership_invitations ADD COLUMN invitation_status invitation_status_enum NOT NULL DEFAULT 'invitation_pending'");

        // Partial index for pending invitations
        DB::statement("CREATE INDEX idx_invitations_pending ON team_membership_invitations(invitation_status) WHERE invitation_status = 'invitation_pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_membership_invitations');
        DB::statement('DROP TYPE IF EXISTS invitation_status_enum');
    }
};
