<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasRecordUniqueIdentifier;

class Transaction extends Model
{
    use HasRecordUniqueIdentifier;

    protected $table = 'transactions';
    
    public $timestamps = false;

    const CREATED_AT = 'datetime_created';
    const UPDATED_AT = 'datetime_updated';

    protected $fillable = [
        'record_unique_identifier',
        'tenant_account_id',
        'currency_code',
        'amount',
        'settlement_currency_code',
        'exchange_ratio',
        'settlement_amount',
        'exchange_fixed_fee',
        'exchange_percentage_fee',
        'exchange_minimum_fee',
        'exchange_total_fee',
        'incoming_fixed_fee',
        'incoming_percentage_fee',
        'incoming_minimum_fee',
        'incoming_total_fee',
        'outgoing_fixed_fee',
        'outgoing_percentage_fee',
        'outgoing_minimum_fee',
        'outgoing_total_fee',
        'final_settlement_currency_code',
        'final_settlement_amount',
        'settlement_account_type',
        'crypto_wallet_address',
        'crypto_network',
        'fiat_payment_method',
        'fiat_bank_account_number',
        'fiat_bank_routing_number',
        'fiat_bank_swift_code',
        'fiat_account_holder_name',
        'fiat_bank_address',
        'fiat_bank_country',
        'transaction_status',
        'datetime_received',
        'datetime_exchanged',
        'datetime_settled',
        'solana_inbound_tx_signature',
        'solana_outbound_tx_signature',
        'master_wallet_id',
        'client_wallet_id',
        'datetime_created',
        'datetime_updated',
    ];

    protected $casts = [
        'amount' => 'decimal:5',
        'exchange_ratio' => 'decimal:8',
        'settlement_amount' => 'decimal:5',
        'exchange_fixed_fee' => 'decimal:5',
        'exchange_percentage_fee' => 'decimal:2',
        'exchange_minimum_fee' => 'decimal:5',
        'exchange_total_fee' => 'decimal:5',
        'incoming_fixed_fee' => 'decimal:5',
        'incoming_percentage_fee' => 'decimal:2',
        'incoming_minimum_fee' => 'decimal:5',
        'incoming_total_fee' => 'decimal:5',
        'outgoing_fixed_fee' => 'decimal:5',
        'outgoing_percentage_fee' => 'decimal:2',
        'outgoing_minimum_fee' => 'decimal:5',
        'outgoing_total_fee' => 'decimal:5',
        'final_settlement_amount' => 'decimal:5',
        'datetime_received' => 'datetime',
        'datetime_exchanged' => 'datetime',
        'datetime_settled' => 'datetime',
        'datetime_created' => 'datetime',
        'datetime_updated' => 'datetime',
    ];

    public function tenantAccount(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }
}
