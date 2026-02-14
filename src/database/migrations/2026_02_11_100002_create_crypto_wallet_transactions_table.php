<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            $table->foreignId('wallet_id')->constrained('crypto_wallets')->onDelete('cascade');
            $table->string('account_hash', 32)->nullable()->index();
            $table->string('direction', 10);
            $table->string('currency', 10);
            $table->string('network', 30)->default('solana');
            $table->decimal('amount', 20, 6);
            $table->string('from_wallet_address', 255);
            $table->string('to_wallet_address', 255);
            $table->string('solana_tx_signature', 128)->nullable();
            $table->bigInteger('solana_block_slot')->nullable();
            $table->integer('solana_confirmations')->nullable();
            $table->bigInteger('solana_fee_lamports')->nullable();
            $table->string('transaction_status', 20)->default('submitted');
            $table->text('memo_note')->nullable();
            $table->string('initiated_by_member_hash', 32)->nullable();
            $table->boolean('webhook_detected')->default(false);
            $table->jsonb('raw_solana_response')->nullable();
            $table->timestamp('datetime_submitted')->nullable();
            $table->timestamp('datetime_confirmed')->nullable();
            $table->timestamp('datetime_finalized')->nullable();
            $table->timestamp('datetime_created')->useCurrent();
            $table->timestamp('datetime_updated')->useCurrent()->useCurrentOnUpdate();
        });

        DB::statement('CREATE INDEX idx_cwt_direction ON crypto_wallet_transactions(direction)');
        DB::statement('CREATE INDEX idx_cwt_solana_tx ON crypto_wallet_transactions(solana_tx_signature)');
        DB::statement('CREATE INDEX idx_cwt_status ON crypto_wallet_transactions(transaction_status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_wallet_transactions');
    }
};
