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
        // Create enum type
        DB::statement("CREATE TYPE ticket_status_enum AS ENUM ('ticket_open', 'ticket_in_progress', 'ticket_resolved', 'ticket_closed')");

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            $table->foreignId('tenant_account_id')->constrained('tenant_accounts')->onDelete('cascade');
            $table->foreignId('created_by_member_id')->constrained('platform_members')->onDelete('cascade');
            $table->string('ticket_subject_line', 500);
            $table->text('ticket_description_body');
            $table->foreignId('assigned_to_administrator_id')->nullable()->constrained('platform_members')->onDelete('set null');
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent();

            $table->index('tenant_account_id', 'idx_tickets_by_account');
            $table->index('created_by_member_id', 'idx_tickets_by_creator');
        });

        // Add enum column separately for PostgreSQL
        DB::statement("ALTER TABLE support_tickets ADD COLUMN ticket_status ticket_status_enum NOT NULL DEFAULT 'ticket_open'");

        // Partial index for open tickets
        DB::statement("CREATE INDEX idx_tickets_open ON support_tickets(ticket_status) WHERE ticket_status IN ('ticket_open', 'ticket_in_progress')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
        DB::statement('DROP TYPE IF EXISTS ticket_status_enum');
    }
};
