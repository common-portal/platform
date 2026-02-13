<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 36)->unique();
            
            $table->unsignedBigInteger('tenant_account_id');
            $table->foreign('tenant_account_id')->references('id')->on('tenant_accounts')->onDelete('cascade');
            
            $table->string('customer_full_name', 255);
            $table->string('customer_primary_contact_name', 255)->nullable();
            $table->string('customer_primary_contact_email', 255);
            
            $table->string('customer_iban', 34)->nullable();
            $table->string('customer_bic', 11)->nullable();
            
            $table->enum('mandate_status', [
                'invitation_pending',
                'mandate_confirmed',
                'mandate_active',
                'mandate_cancelled'
            ])->default('invitation_pending');
            
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly'])->nullable();
            $table->json('billing_dates')->nullable();
            $table->decimal('billing_amount', 12, 2)->nullable();
            $table->string('billing_currency', 3)->default('EUR');
            
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamp('mandate_confirmed_at')->nullable();
            
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('tenant_account_id');
            $table->index('customer_primary_contact_email');
            $table->index('mandate_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
