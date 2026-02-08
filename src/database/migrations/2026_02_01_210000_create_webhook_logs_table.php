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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 36)->unique();
            $table->string('provider', 50)->index();
            $table->string('webhook_id', 100)->nullable()->index();
            $table->string('transaction_uid', 100)->nullable()->index();
            $table->integer('webhook_type')->nullable();
            $table->integer('webhook_status')->nullable();
            $table->json('payload')->nullable();
            $table->string('processing_status', 20)->default('received');
            $table->text('processing_notes')->nullable();
            $table->unsignedBigInteger('created_transaction_id')->nullable();
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('processed_at_timestamp')->nullable();
            
            // Composite unique to prevent duplicate processing
            $table->unique(['provider', 'webhook_id'], 'unique_provider_webhook');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
