<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    protected $fillable = [
        'tenant_account_id',
        'record_unique_identifier',
        'customer_full_name',
        'customer_primary_contact_name',
        'customer_primary_contact_email',
        'customer_iban',
        'customer_bic',
        'billing_name_on_account',
        'billing_bank_name',
        'mandate_status',
        'mandate_active_or_paused',
        'recurring_frequency',
        'billing_dates',
        'billing_start_date',
        'billing_amount',
        'billing_currency',
        'settlement_iban_hash',
        'invitation_sent_at',
        'mandate_confirmed_at',
    ];

    protected $casts = [
        'billing_dates' => 'array',
        'billing_start_date' => 'date',
        'billing_amount' => 'decimal:2',
        'invitation_sent_at' => 'datetime',
        'mandate_confirmed_at' => 'datetime',
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($customer) {
            if (empty($customer->record_unique_identifier)) {
                $customer->record_unique_identifier = (string) Str::uuid();
            }
        });
    }

    public function tenantAccount()
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }

    public function getMandateVerificationUrlAttribute(): string
    {
        return url("/public/customer/{$this->record_unique_identifier}");
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('tenant_account_id', $accountId);
    }

    public function scopePendingInvitation($query)
    {
        return $query->where('mandate_status', 'invitation_pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->whereIn('mandate_status', ['mandate_confirmed', 'mandate_active']);
    }

    public function settlementIban()
    {
        return $this->belongsTo(IbanAccount::class, 'settlement_iban_hash', 'record_unique_identifier');
    }
}
