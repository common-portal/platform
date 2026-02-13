<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('solana_inbound_tx_signature', 128)->nullable()->after('datetime_settled');
            $table->string('solana_outbound_tx_signature', 128)->nullable()->after('solana_inbound_tx_signature');
            $table->foreignId('master_wallet_id')->nullable()->constrained('crypto_wallets')->nullOnDelete()->after('solana_outbound_tx_signature');
            $table->foreignId('client_wallet_id')->nullable()->constrained('crypto_wallets')->nullOnDelete()->after('master_wallet_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['master_wallet_id']);
            $table->dropForeign(['client_wallet_id']);
            $table->dropColumn([
                'solana_inbound_tx_signature',
                'solana_outbound_tx_signature',
                'master_wallet_id',
                'client_wallet_id',
            ]);
        });
    }
};
