<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TYPE transaction_status_enum AS ENUM ('received', 'exchanged', 'settled')");
        DB::statement("CREATE TYPE settlement_account_type_enum AS ENUM ('crypto', 'fiat')");
        DB::statement("CREATE TYPE currency_code_enum AS ENUM ('EUR', 'GBP', 'USD', 'USDT', 'USDC', 'EURC', 'GBPC', 'BTC', 'ETH', 'XRP', 'SOL', 'ADA', 'MATIC', 'AVAX', 'DOT', 'LINK', 'UNI', 'LTC', 'BCH', 'XLM', 'ALGO', 'ATOM', 'TRX')");
        DB::statement("CREATE TYPE network_type_enum AS ENUM ('solana', 'ethereum', 'polygon', 'bsc', 'avalanche', 'arbitrum', 'optimism', 'base', 'tron', 'stellar')");

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('record_unique_identifier', 64)->unique();
            $table->foreignId('tenant_account_id')->constrained('tenant_accounts')->onDelete('cascade');
            
            $table->string('currency_code', 10);
            $table->decimal('amount', 20, 5);
            $table->decimal('exchange_ratio', 20, 8)->nullable();
            
            $table->string('settlement_account_type', 20);
            
            $table->string('crypto_wallet_address', 255)->nullable();
            $table->string('crypto_network', 50)->nullable();
            
            $table->string('fiat_payment_method', 50)->nullable();
            $table->string('fiat_bank_account_number', 100)->nullable();
            $table->string('fiat_bank_routing_number', 100)->nullable();
            $table->string('fiat_bank_swift_code', 50)->nullable();
            $table->string('fiat_account_holder_name', 255)->nullable();
            $table->text('fiat_bank_address')->nullable();
            $table->string('fiat_bank_country', 100)->nullable();
            
            $table->string('transaction_status', 20)->default('received');
            
            $table->timestamp('datetime_received')->nullable();
            $table->timestamp('datetime_exchanged')->nullable();
            $table->timestamp('datetime_settled')->nullable();
            $table->timestamp('datetime_created')->useCurrent();
            $table->timestamp('datetime_updated')->useCurrent()->useCurrentOnUpdate();
        });

        DB::statement('CREATE INDEX idx_transactions_by_account ON transactions(tenant_account_id)');
        DB::statement('CREATE INDEX idx_transactions_by_status ON transactions(transaction_status)');
        DB::statement('CREATE INDEX idx_transactions_by_updated ON transactions(datetime_updated DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        DB::statement('DROP TYPE IF EXISTS transaction_status_enum');
        DB::statement('DROP TYPE IF EXISTS settlement_account_type_enum');
        DB::statement('DROP TYPE IF EXISTS currency_code_enum');
        DB::statement('DROP TYPE IF EXISTS network_type_enum');
    }
};
