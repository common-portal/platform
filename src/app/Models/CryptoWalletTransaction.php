<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasRecordUniqueIdentifier;

class CryptoWalletTransaction extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'crypto_wallet_transactions';

    public $timestamps = false;

    const CREATED_AT = 'datetime_created';
    const UPDATED_AT = 'datetime_updated';

    protected $fillable = [
        'record_unique_identifier',
        'wallet_id',
        'account_hash',
        'direction',
        'currency',
        'network',
        'amount',
        'from_wallet_address',
        'to_wallet_address',
        'solana_tx_signature',
        'solana_block_slot',
        'solana_confirmations',
        'solana_fee_lamports',
        'transaction_status',
        'memo_note',
        'initiated_by_member_hash',
        'webhook_detected',
        'raw_solana_response',
        'datetime_submitted',
        'datetime_confirmed',
        'datetime_finalized',
        'datetime_created',
        'datetime_updated',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'solana_block_slot' => 'integer',
        'solana_confirmations' => 'integer',
        'solana_fee_lamports' => 'integer',
        'webhook_detected' => 'boolean',
        'raw_solana_response' => 'array',
        'datetime_submitted' => 'datetime',
        'datetime_confirmed' => 'datetime',
        'datetime_finalized' => 'datetime',
        'datetime_created' => 'datetime',
        'datetime_updated' => 'datetime',
    ];

    /**
     * Get the wallet this transaction belongs to.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CryptoWallet::class, 'wallet_id');
    }

    /**
     * Get the tenant account associated with this transaction.
     */
    public function tenant_account(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'account_hash', 'record_unique_identifier');
    }

    /**
     * Get the member who initiated this transaction.
     */
    public function initiated_by(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'initiated_by_member_hash', 'record_unique_identifier');
    }

    /**
     * Scope to get incoming transactions.
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    /**
     * Scope to get outgoing transactions.
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    /**
     * Get Solana Explorer URL for this transaction.
     */
    public function getSolanaExplorerUrlAttribute(): ?string
    {
        if (!$this->solana_tx_signature) {
            return null;
        }
        return 'https://explorer.solana.com/tx/' . $this->solana_tx_signature;
    }

    /**
     * Get Solscan URL for this transaction.
     */
    public function getSolscanUrlAttribute(): ?string
    {
        if (!$this->solana_tx_signature) {
            return null;
        }
        return 'https://solscan.io/tx/' . $this->solana_tx_signature;
    }
}
