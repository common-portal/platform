<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_ticket_messages')) {
            return;
        }
        
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('author_member_id')->nullable()->constrained('platform_members')->onDelete('set null');
            $table->foreignId('author_admin_id')->nullable()->constrained('platform_members')->onDelete('set null');
            
            $table->enum('message_type', ['member_reply', 'admin_response', 'system_note'])->default('member_reply');
            $table->text('message_body');
            
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['support_ticket_id', 'created_at_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
