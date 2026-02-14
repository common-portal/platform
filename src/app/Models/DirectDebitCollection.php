<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DirectDebitCollection extends Model
{
    protected $table = 'direct_debit_collections';

    public $timestamps = false;

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_CLEARED = 'cleared';
    const STATUS_FAILED = 'failed';
    const STATUS_REJECTED = 'rejected';

    // Sequence types
    const SEQ_FIRST = 'FRST';
    const SEQ_RECURRING = 'RCUR';

    protected $fillable = [
        'record_unique_identifier',
        'tenant_account_id',
        'customer_id',
        'correlation_id',
        'reference',
        'amount',
        'currency',
        'amount_minor_units',
        'source_iban',
        'destination_iban',
        'destination_ledger_uid',
        'sh_transaction_uid',
        'sh_batch_id',
        'status',
        'failure_reason',
        'retry_count',
        'billing_date',
        'sequence_type',
        'submitted_at',
        'cleared_at',
        'failed_at',
        'created_at_timestamp',
        'updated_at_timestamp',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_minor_units' => 'integer',
        'retry_count' => 'integer',
        'billing_date' => 'date',
        'submitted_at' => 'datetime',
        'cleared_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($collection) {
            if (empty($collection->record_unique_identifier)) {
                $collection->record_unique_identifier = (string) Str::uuid();
            }
        });
    }

    public function tenantAccount(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('billing_date', $date);
    }

    public function markSubmitted(string $shTransactionUid = null, string $shBatchId = null): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'sh_transaction_uid' => $shTransactionUid,
            'sh_batch_id' => $shBatchId,
            'submitted_at' => now(),
            'updated_at_timestamp' => now(),
        ]);
    }

    public function markCleared(): void
    {
        $this->update([
            'status' => self::STATUS_CLEARED,
            'cleared_at' => now(),
            'updated_at_timestamp' => now(),
        ]);
    }

    public function markFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'failed_at' => now(),
            'retry_count' => $this->retry_count + 1,
            'updated_at_timestamp' => now(),
        ]);
    }

    public function markRejected(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'failure_reason' => $reason,
            'failed_at' => now(),
            'updated_at_timestamp' => now(),
        ]);
    }
}
