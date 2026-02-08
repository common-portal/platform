<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasRecordUniqueIdentifier;

class WebhookLog extends Model
{
    use HasRecordUniqueIdentifier;

    protected $table = 'webhook_logs';
    
    public $timestamps = false;

    const CREATED_AT = 'created_at_timestamp';

    protected $fillable = [
        'record_unique_identifier',
        'provider',
        'webhook_id',
        'transaction_uid',
        'webhook_type',
        'webhook_status',
        'payload',
        'processing_status',
        'processing_notes',
        'created_transaction_id',
        'created_at_timestamp',
        'processed_at_timestamp',
    ];

    protected $casts = [
        'payload' => 'array',
        'webhook_type' => 'integer',
        'webhook_status' => 'integer',
        'created_at_timestamp' => 'datetime',
        'processed_at_timestamp' => 'datetime',
    ];

    /**
     * Processing status constants.
     */
    const STATUS_RECEIVED = 'received';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';
    const STATUS_DUPLICATE = 'duplicate';
    const STATUS_IGNORED = 'ignored';

    /**
     * Get the transaction created from this webhook.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'created_transaction_id');
    }

    /**
     * Check if this webhook has already been processed.
     */
    public function isProcessed(): bool
    {
        return $this->processing_status === self::STATUS_PROCESSED;
    }

    /**
     * Check if a webhook with this ID already exists for this provider.
     */
    public static function isDuplicate(string $provider, string $webhookId): bool
    {
        return self::where('provider', $provider)
            ->where('webhook_id', $webhookId)
            ->exists();
    }

    /**
     * Mark as processed.
     */
    public function markProcessed(?int $transactionId = null, ?string $notes = null): void
    {
        $this->update([
            'processing_status' => self::STATUS_PROCESSED,
            'created_transaction_id' => $transactionId,
            'processing_notes' => $notes,
            'processed_at_timestamp' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed(string $reason): void
    {
        $this->update([
            'processing_status' => self::STATUS_FAILED,
            'processing_notes' => $reason,
            'processed_at_timestamp' => now(),
        ]);
    }

    /**
     * Mark as duplicate.
     */
    public function markDuplicate(): void
    {
        $this->update([
            'processing_status' => self::STATUS_DUPLICATE,
            'processed_at_timestamp' => now(),
        ]);
    }

    /**
     * Mark as ignored (e.g., non-actionable webhook type).
     */
    public function markIgnored(string $reason): void
    {
        $this->update([
            'processing_status' => self::STATUS_IGNORED,
            'processing_notes' => $reason,
            'processed_at_timestamp' => now(),
        ]);
    }

    /**
     * Scope: by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: pending processing.
     */
    public function scopePending($query)
    {
        return $query->where('processing_status', self::STATUS_RECEIVED);
    }
}
