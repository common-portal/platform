<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasRecordUniqueIdentifier;

class IbanHostBank extends Model
{
    use HasFactory, HasRecordUniqueIdentifier;

    protected $table = 'iban_host_banks';

    public $timestamps = false;

    const CREATED_AT = 'datetime_created';
    const UPDATED_AT = 'datetime_updated';

    protected $fillable = [
        'record_unique_identifier',
        'host_bank_name',
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
     * Get the IBAN accounts using this host bank.
     */
    public function iban_accounts(): HasMany
    {
        return $this->hasMany(IbanAccount::class, 'iban_host_bank_hash', 'record_unique_identifier');
    }

    /**
     * Scope to get non-deleted host banks.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to get active host banks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_deleted', false);
    }

    /**
     * Soft delete the host bank.
     */
    public function softDelete(): void
    {
        $this->update([
            'is_deleted' => true,
            'datetime_updated' => now(),
        ]);
    }
}
