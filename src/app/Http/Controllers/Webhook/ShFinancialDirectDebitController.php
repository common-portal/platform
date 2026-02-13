<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\DirectDebitCollection;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShFinancialDirectDebitController extends Controller
{
    const PROVIDER = 'sh-financial-directdebit';
    const SECRET_KEY = 'AKmjCcK)sFA50yyhyJ2xkEN*dXe4fesD';

    // SH Financial Transaction Statuses
    const STATUS_PENDING = 0;
    const STATUS_CLEARED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_CLEARING = 3;
    const STATUS_FAILED_TO_CLEAR = 4;
    const STATUS_REJECT_PENDING = 5;
    const STATUS_REJECT_FAILED = 6;

    /**
     * Handle incoming SH Financial Direct Debit webhook.
     * Route: POST /webhooks/sh-financial/directdebit/v1/
     */
    public function handle(Request $request)
    {
        // 1. Validate secret key
        $secretKey = $request->header('Shf-Secret-Key');
        if ($secretKey !== self::SECRET_KEY) {
            Log::channel('directdebit')->warning('DD Webhook: Invalid secret key', [
                'ip' => $request->ip(),
                'provided_key' => substr($secretKey ?? '', 0, 10) . '...',
            ]);
            return response()->json(['error' => 'Invalid secret key'], 401);
        }

        // 2. Parse payload
        $payload = $request->all();
        $webhookId = $payload['Id'] ?? null;
        $transactionUid = $payload['TransactionUID'] ?? null;
        $status = $payload['Status'] ?? null;
        $correlationId = $payload['CorrelationId'] ?? $payload['correlationId'] ?? null;
        $amount = $payload['Amount'] ?? null;

        // 3. Validate required fields
        if (!$webhookId || $status === null) {
            Log::channel('directdebit')->warning('DD Webhook: Missing required fields', $payload);
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        // 4. Check for duplicate webhook
        if (WebhookLog::isDuplicate(self::PROVIDER, $webhookId)) {
            Log::channel('directdebit')->info('DD Webhook: Duplicate ignored', ['webhook_id' => $webhookId]);
            return response()->json(['status' => 'duplicate', 'id' => $webhookId], 200);
        }

        // 5. Log the webhook
        $webhookLog = WebhookLog::create([
            'provider' => self::PROVIDER,
            'webhook_id' => $webhookId,
            'transaction_uid' => $transactionUid,
            'webhook_type' => $payload['Type'] ?? null,
            'webhook_status' => $status,
            'payload' => $payload,
            'processing_status' => WebhookLog::STATUS_RECEIVED,
            'created_at_timestamp' => now(),
        ]);

        // 6. Find the matching DD collection
        try {
            $collection = $this->findCollection($transactionUid, $correlationId);

            if (!$collection) {
                $webhookLog->markIgnored("No matching DD collection found (txn: {$transactionUid}, corr: {$correlationId})");
                Log::channel('directdebit')->warning('DD Webhook: No matching collection', [
                    'transaction_uid' => $transactionUid,
                    'correlation_id' => $correlationId,
                    'webhook_id' => $webhookId,
                ]);
                return response()->json(['status' => 'received', 'id' => $webhookId, 'note' => 'no matching collection'], 200);
            }

            // 7. Update collection status based on webhook status
            $this->processStatusUpdate($collection, $status, $payload, $webhookLog);

        } catch (\Exception $e) {
            Log::channel('directdebit')->error('DD Webhook: Processing error', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);
            $webhookLog->markFailed($e->getMessage());
        }

        return response()->json(['status' => 'received', 'id' => $webhookId], 200);
    }

    /**
     * Find the DD collection by transaction UID or correlation ID.
     */
    protected function findCollection(?string $transactionUid, ?string $correlationId): ?DirectDebitCollection
    {
        // Try by SH transaction UID first
        if ($transactionUid) {
            $collection = DirectDebitCollection::where('sh_transaction_uid', $transactionUid)->first();
            if ($collection) return $collection;
        }

        // Fallback to correlation ID
        if ($correlationId) {
            $collection = DirectDebitCollection::where('correlation_id', $correlationId)->first();
            if ($collection) return $collection;
        }

        return null;
    }

    /**
     * Process the status update for a DD collection.
     */
    protected function processStatusUpdate(
        DirectDebitCollection $collection,
        int $status,
        array $payload,
        WebhookLog $webhookLog
    ): void {
        $previousStatus = $collection->status;

        switch ($status) {
            case self::STATUS_CLEARED:
                $collection->markCleared();
                $webhookLog->markProcessed(
                    null,
                    "DD collection #{$collection->id} cleared (was: {$previousStatus})"
                );
                Log::channel('directdebit')->info('DD Webhook: Collection cleared', [
                    'collection_id' => $collection->id,
                    'customer_id' => $collection->customer_id,
                    'amount' => $collection->amount,
                    'currency' => $collection->currency,
                    'reference' => $collection->reference,
                ]);
                break;

            case self::STATUS_CLEARING:
                // In progress — update transaction UID if we didn't have it
                if (!$collection->sh_transaction_uid && ($payload['TransactionUID'] ?? null)) {
                    $collection->update(['sh_transaction_uid' => $payload['TransactionUID']]);
                }
                $webhookLog->markProcessed(
                    null,
                    "DD collection #{$collection->id} clearing in progress"
                );
                Log::channel('directdebit')->info('DD Webhook: Collection clearing', [
                    'collection_id' => $collection->id,
                ]);
                break;

            case self::STATUS_REJECTED:
            case self::STATUS_REJECT_PENDING:
            case self::STATUS_REJECT_FAILED:
                $reason = $payload['RejectReason']
                    ?? $payload['rejectReason']
                    ?? $payload['FailureReason']
                    ?? "Rejected (SH status: {$status})";

                $collection->markRejected($reason);
                $webhookLog->markProcessed(
                    null,
                    "DD collection #{$collection->id} rejected: {$reason}"
                );
                Log::channel('directdebit')->warning('DD Webhook: Collection rejected', [
                    'collection_id' => $collection->id,
                    'customer_id' => $collection->customer_id,
                    'reason' => $reason,
                    'amount' => $collection->amount,
                    'reference' => $collection->reference,
                ]);
                break;

            case self::STATUS_FAILED_TO_CLEAR:
                $reason = $payload['FailureReason']
                    ?? $payload['failureReason']
                    ?? "Failed to clear (SH status: {$status})";

                $collection->markFailed($reason);
                $webhookLog->markProcessed(
                    null,
                    "DD collection #{$collection->id} failed: {$reason}"
                );
                Log::channel('directdebit')->error('DD Webhook: Collection failed', [
                    'collection_id' => $collection->id,
                    'customer_id' => $collection->customer_id,
                    'reason' => $reason,
                    'amount' => $collection->amount,
                    'reference' => $collection->reference,
                ]);
                break;

            case self::STATUS_PENDING:
                $webhookLog->markIgnored("DD collection #{$collection->id} still pending");
                break;

            default:
                $webhookLog->markIgnored("Unknown status {$status} for DD collection #{$collection->id}");
                Log::channel('directdebit')->warning('DD Webhook: Unknown status', [
                    'collection_id' => $collection->id,
                    'status' => $status,
                ]);
        }
    }
}
