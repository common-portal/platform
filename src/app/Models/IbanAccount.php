<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasRecordUniqueIdentifier;

class IbanAccount extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'iban_accounts';

    public $timestamps = false;

    const CREATED_AT = 'datetime_created';
    const UPDATED_AT = 'datetime_updated';

    protected $fillable = [
        'record_unique_identifier',
        'account_hash',
        'iban_friendly_name',
        'iban_currency_iso3',
        'iban_number',
        'iban_host_bank_hash',
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
     * Get the tenant account associated with this IBAN.
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
     * Get the host bank for this IBAN.
     */
    public function host_bank(): BelongsTo
    {
        return $this->belongsTo(IbanHostBank::class, 'iban_host_bank_hash', 'record_unique_identifier');
    }

    /**
     * Scope to get non-deleted IBANs.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to get active IBANs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_deleted', false);
    }

    /**
     * Soft delete the IBAN account.
     */
    public function softDelete(): void
    {
        $this->update([
            'is_deleted' => true,
            'datetime_updated' => now(),
        ]);
    }
}
