<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasRecordUniqueIdentifier;

class AccountFeeConfig extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'account_fee_configs';

    public $timestamps = false;

    const CREATED_AT = 'datetime_created';
    const UPDATED_AT = 'datetime_updated';

    protected $fillable = [
        'record_unique_identifier',
        'account_hash',
        'currency_code',
        'fixed_fee',
        'percentage_fee',
        'minimum_fee',
        'creator_member_hash',
        'is_active',
        'is_deleted',
        'datetime_created',
        'datetime_updated',
    ];

    protected $casts = [
        'fixed_fee' => 'decimal:5',
        'percentage_fee' => 'decimal:2',
        'minimum_fee' => 'decimal:5',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'datetime_created' => 'datetime',
        'datetime_updated' => 'datetime',
    ];

    public function tenant_account(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'account_hash', 'record_unique_identifier');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PlatformMember::class, 'creator_member_hash', 'record_unique_identifier');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_deleted', false);
    }

    public function softDelete(): void
    {
        $this->update([
            'is_deleted' => true,
            'datetime_updated' => now(),
        ]);
    }
}
