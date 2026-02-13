<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasRecordUniqueIdentifier;

class CryptoWallet extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'crypto_wallets';

    public $timestamps = false;

    const CREATED_AT = 'datetime_created';
    const UPDATED_AT = 'datetime_updated';

    const WALLET_TYPE_CLIENT = 'client';
    const WALLET_TYPE_ADMIN = 'admin';
    const WALLET_TYPE_GAS = 'gas';

    const WALLET_TYPES = [
        self::WALLET_TYPE_CLIENT => 'Client',
        self::WALLET_TYPE_ADMIN => 'Admin',
        self::WALLET_TYPE_GAS => 'Gas',
    ];

    protected $fillable = [
        'record_unique_identifier',
        'account_hash',
        'wallet_friendly_name',
        'wallet_currency',
        'wallet_network',
        'wallet_type',
        'wallet_address',
        'walletids_wallet_hash',
        'walletids_external_id',
        'creator_member_hash',
        'is_active',
        'is_deleted',
        'datetime_created',
        'datetime_updated',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'datetime_created' => 'datetime',
        'datetime_updated' => 'datetime',
    ];

    /**
     * Get the tenant account associated with this wallet.
     */
    public function tenant_account(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'account_hash', 'record_unique_identifier');
    }

    /**
     * Get the creator member.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'creator_member_hash', 'record_unique_identifier');
    }

    /**
     * Get wallet transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CryptoWalletTransaction::class, 'wallet_id');
    }

    /**
     * Scope to get non-deleted wallets.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to get active wallets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_deleted', false);
    }

    /**
     * Soft delete the wallet.
     */
    public function softDelete(): void
    {
        $this->update([
            'is_deleted' => true,
            'datetime_updated' => now(),
        ]);
    }
}
