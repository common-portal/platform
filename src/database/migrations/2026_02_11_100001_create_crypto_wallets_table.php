<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            $table->string('account_hash', 32)->index();
            $table->string('wallet_friendly_name', 255);
            $table->string('wallet_currency', 10);
            $table->string('wallet_network', 30)->default('solana');
            $table->string('wallet_type', 20)->default('dynamic');
            $table->string('wallet_address', 255);
            $table->string('walletids_wallet_hash', 64);
            $table->string('walletids_external_id', 255)->nullable();
            $table->string('creator_member_hash', 32)->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('datetime_created')->useCurrent();
            $table->timestamp('datetime_updated')->useCurrent()->useCurrentOnUpdate();
        });

        DB::statement('CREATE INDEX idx_crypto_wallets_wallet_address ON crypto_wallets(wallet_address)');
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_wallets');
    }
};
