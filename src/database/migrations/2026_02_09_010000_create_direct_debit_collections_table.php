<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_debit_collections', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            $table->foreignId('tenant_account_id')->constrained('tenant_accounts')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');

            // Correlation / idempotency
            $table->string('correlation_id', 128)->unique();
            $table->string('reference', 255);

            // Amount
            $table->decimal('amount', 20, 2);
            $table->string('currency', 10)->default('EUR');
            $table->bigInteger('amount_minor_units'); // cents for API

            // Ledger references
            $table->string('source_iban', 64)->nullable();        // debtor IBAN
            $table->string('destination_iban', 64)->nullable();    // creditor IBAN
            $table->uuid('destination_ledger_uid')->nullable();    // creditor ledger UUID from iban_accounts.iban_ledger

            // SH Financial API response
            $table->uuid('sh_transaction_uid')->nullable();
            $table->string('sh_batch_id', 128)->nullable();

            // Status tracking
            $table->string('status', 30)->default('pending');
            // pending → submitted → cleared → failed → rejected

            $table->string('failure_reason', 500)->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);

            // Billing context
            $table->date('billing_date');
            $table->string('sequence_type', 10)->default('RCUR'); // FRST or RCUR

            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent()->useCurrentOnUpdate();

            // Indexes
            $table->index(['tenant_account_id', 'billing_date']);
            $table->index(['customer_id', 'billing_date']);
            $table->index('status');
            $table->index('sh_transaction_uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_debit_collections');
    }
};
