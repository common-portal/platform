<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Models\IbanAccount;
use App\Models\Transaction;
use App\Services\PlatformMailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShFinancialController extends Controller
{
    const PROVIDER = 'sh-financial';
    const SECRET_KEY = 'AKmjCcK)sFA50yyhyJ2xkEN*dXe4fesD';
    const TRANSACTION_API_URL = 'https://utilities.getmondo.co/gateway/sh-financial/get_transaction_update_v2.php';

    // Transaction Types
    const TYPE_TRANSFER = 0;
    const TYPE_INCOMING_PAYMENT = 1;
    const TYPE_OUTGOING_PAYMENT = 2;
    const TYPE_FX_TRANSFER = 3;
    const TYPE_INTERNAL_PAYMENT = 4;

    // Transaction Statuses
    const STATUS_PENDING = 0;
    const STATUS_CLEARED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_CLEARING = 3;
    const STATUS_FAILED_TO_CLEAR = 4;
    const STATUS_REJECT_PENDING = 5;
    const STATUS_REJECT_FAILED = 6;

    /**
     * Handle incoming SH Financial webhook.
     */
    public function handle(Request $request)
    {
        // 1. Validate secret key
        $secretKey = $request->header('Shf-Secret-Key');
        if ($secretKey !== self::SECRET_KEY) {
            Log::warning('SH Financial webhook: Invalid secret key', [
                'ip' => $request->ip(),
                'provided_key' => substr($secretKey ?? '', 0, 10) . '...',
            ]);
            return response()->json(['error' => 'Invalid secret key'], 401);
        }

        // 2. Parse payload
        $payload = $request->all();
        $webhookId = $payload['Id'] ?? null;
        $transactionUid = $payload['TransactionUID'] ?? null;
        $type = $payload['Type'] ?? null;
        $status = $payload['Status'] ?? null;
        $amount = $payload['Amount'] ?? null;

        // 3. Validate required fields
        if (!$webhookId || $type === null || $status === null) {
            Log::warning('SH Financial webhook: Missing required fields', $payload);
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        // 4. Check for duplicate
        if (WebhookLog::isDuplicate(self::PROVIDER, $webhookId)) {
            Log::info('SH Financial webhook: Duplicate ignored', ['webhook_id' => $webhookId]);
            return response()->json(['status' => 'duplicate', 'id' => $webhookId], 200);
        }

        // 5. Log the webhook
        $webhookLog = WebhookLog::create([
            'provider' => self::PROVIDER,
            'webhook_id' => $webhookId,
            'transaction_uid' => $transactionUid,
            'webhook_type' => $type,
            'webhook_status' => $status,
            'payload' => $payload,
            'processing_status' => WebhookLog::STATUS_RECEIVED,
            'created_at_timestamp' => now(),
        ]);

        // 6. Process based on type
        try {
            switch ($type) {
                case self::TYPE_INCOMING_PAYMENT:
                    $this->processIncomingPayment($webhookLog, $payload);
                    break;

                case self::TYPE_OUTGOING_PAYMENT:
                    $this->processOutgoingPayment($webhookLog, $payload);
                    break;

                case self::TYPE_TRANSFER:
                case self::TYPE_FX_TRANSFER:
                case self::TYPE_INTERNAL_PAYMENT:
                    $webhookLog->markIgnored("Type {$type} not actionable");
                    break;

                default:
                    $webhookLog->markIgnored("Unknown type: {$type}");
            }
        } catch (\Exception $e) {
            Log::error('SH Financial webhook processing error', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);
            $webhookLog->markFailed($e->getMessage());
        }

        return response()->json(['status' => 'received', 'id' => $webhookId], 200);
    }

    /**
     * Process incoming payment (Type 1).
     */
    protected function processIncomingPayment(WebhookLog $webhookLog, array $payload): void
    {
        // Only process cleared payments
        if ($payload['Status'] !== self::STATUS_CLEARED) {
            $webhookLog->markIgnored("Incoming payment not cleared (status: {$payload['Status']})");
            return;
        }

        $transactionUid = $payload['TransactionUID'];

        // Fetch full transaction details from SH Financial API
        $transactionDetails = $this->fetchTransactionDetails($transactionUid);
        if (!$transactionDetails) {
            $webhookLog->markFailed("Failed to fetch transaction details for {$transactionUid}");
            return;
        }

        // Extract destination IBAN
        $destinationIban = $this->extractDestinationIban($transactionDetails);
        if (!$destinationIban) {
            $webhookLog->markFailed("No destination IBAN found in transaction {$transactionUid}");
            return;
        }

        // Lookup IBAN to find tenant account
        $ibanAccount = IbanAccount::where('iban_number', $destinationIban)
            ->active()
            ->first();

        if (!$ibanAccount) {
            $webhookLog->markFailed("IBAN {$destinationIban} not found in system");
            return;
        }

        // Get tenant account ID
        $tenantAccount = $ibanAccount->tenant_account;
        if (!$tenantAccount) {
            $webhookLog->markFailed("No tenant account linked to IBAN {$destinationIban}");
            return;
        }

        // Check if transaction already exists (by transaction_uid in external reference)
        $existingTransaction = Transaction::where('tenant_account_id', $tenantAccount->id)
            ->where('fiat_bank_account_number', $transactionUid) // Using this field to store external ref
            ->first();

        if ($existingTransaction) {
            $webhookLog->markDuplicate();
            return;
        }

        // Extract currency, amount, and payment details
        $currencyCode = $this->extractCurrencyCode($transactionDetails);
        $amountDecimal = ($payload['Amount'] ?? 0) / 100; // Convert from cents
        $senderName = $this->extractSenderName($transactionDetails);
        
        // Extract payment source and destination details from transaction_data
        $transactionData = $transactionDetails['transaction_data'] ?? [];
        $paymentSourceName = $transactionData['paymentSourceName'] ?? null;
        $paymentSourceIban = $transactionData['paymentSourceIBAN'] ?? null;
        $paymentSourceBic = $transactionData['paymentSourceBIC'] ?? null;
        $paymentDestinationIban = $transactionData['paymentDestinationIBAN'] ?? null;
        $paymentDestinationBic = $transactionData['paymentDestinationBIC'] ?? null;
        $paymentDestinationName = $transactionData['paymentDestinationName'] ?? null;

        // Create received transaction
        $transaction = Transaction::create([
            'tenant_account_id' => $tenantAccount->id,
            'currency_code' => $currencyCode,
            'amount' => $amountDecimal,
            'transaction_status' => 'received',
            'datetime_received' => now(),
            'datetime_created' => now(),
            'fiat_bank_account_number' => $transactionUid, // Store external reference
            'payment_source_name' => $paymentSourceName,
            'payment_source_iban' => $paymentSourceIban,
            'payment_source_bic' => $paymentSourceBic,
            'payment_destination_iban' => $paymentDestinationIban,
            'payment_destination_bic' => $paymentDestinationBic,
            'payment_destination_name' => $paymentDestinationName,
        ]);

        $webhookLog->markProcessed(
            $transaction->id,
            "Created transaction #{$transaction->id} for {$amountDecimal} {$currencyCode}"
        );

        Log::info('SH Financial: Created Phase 1 transaction', [
            'transaction_id' => $transaction->id,
            'tenant_account_id' => $tenantAccount->id,
            'amount' => $amountDecimal,
            'currency' => $currencyCode,
            'iban' => $destinationIban,
            'sender' => $senderName,
        ]);

        // Send email notification to primary contact
        $this->sendPaymentReceivedNotification(
            $tenantAccount,
            $amountDecimal,
            $currencyCode,
            $destinationIban,
            $senderName,
            $payload['TransactionDateTime'] ?? now()->toIso8601String()
        );
    }

    /**
     * Send payment received email notification to account's primary contact.
     */
    protected function sendPaymentReceivedNotification(
        $tenantAccount,
        float $amount,
        string $currencyCode,
        string $ibanNumber,
        string $senderName,
        string $transactionDateTime
    ): void {
        $primaryEmail = $tenantAccount->primary_contact_email_address;
        
        if (!$primaryEmail) {
            Log::warning('SH Financial: No primary contact email for notification', [
                'tenant_account_id' => $tenantAccount->id,
            ]);
            return;
        }

        try {
            $mailer = new PlatformMailerService();
            $result = $mailer->sendPaymentReceivedEmail(
                recipientEmail: $primaryEmail,
                recipientName: $tenantAccount->primary_contact_full_name ?? '',
                accountName: $tenantAccount->account_display_name,
                amount: number_format($amount, 2),
                currencyCode: $currencyCode,
                ibanNumber: $ibanNumber,
                senderName: $senderName,
                transactionDateTime: $transactionDateTime
            );

            if ($result['success']) {
                Log::info('SH Financial: Payment notification email sent', [
                    'tenant_account_id' => $tenantAccount->id,
                    'email' => $primaryEmail,
                ]);
            } else {
                Log::warning('SH Financial: Payment notification email failed', [
                    'tenant_account_id' => $tenantAccount->id,
                    'email' => $primaryEmail,
                    'error' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SH Financial: Payment notification email exception', [
                'tenant_account_id' => $tenantAccount->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process outgoing payment status update (Type 2).
     */
    protected function processOutgoingPayment(WebhookLog $webhookLog, array $payload): void
    {
        // Map status to our internal status
        $status = $payload['Status'];
        $mappedStatus = match ($status) {
            self::STATUS_CLEARED => 'completed',
            self::STATUS_REJECTED, self::STATUS_FAILED_TO_CLEAR, self::STATUS_REJECT_FAILED => 'failed',
            default => null,
        };

        if (!$mappedStatus) {
            $webhookLog->markIgnored("Outgoing payment status {$status} not actionable");
            return;
        }

        // TODO: Find and update related outgoing transaction
        $webhookLog->markIgnored("Outgoing payment processing not yet implemented");
    }

    /**
     * Fetch full transaction details from SH Financial API.
     */
    protected function fetchTransactionDetails(string $transactionUid): ?array
    {
        try {
            $response = Http::timeout(10)->get(self::TRANSACTION_API_URL, [
                'transaction_uid' => $transactionUid,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('SH Financial API response', [
                    'transaction_uid' => $transactionUid,
                    'status' => $data['transaction_status'] ?? null,
                    'iban' => $data['transaction_data']['paymentDestinationIBAN'] ?? null,
                    'amount' => $data['amount'] ?? null,
                ]);
                
                return $data;
            }

            Log::warning('SH Financial API error', [
                'transaction_uid' => $transactionUid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('SH Financial API request failed', [
                'transaction_uid' => $transactionUid,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Extract destination IBAN from transaction details.
     */
    protected function extractDestinationIban(array $details): ?string
    {
        // Try common field paths for destination IBAN
        return $details['transaction_data']['paymentDestinationIBAN']
            ?? $details['paymentDestinationIBAN']
            ?? $details['DestinationIBAN']
            ?? $details['destination_iban']
            ?? $details['ToAccountIBAN']
            ?? $details['to_account_iban']
            ?? $details['RecipientIBAN']
            ?? $details['recipient_iban']
            ?? $details['data']['DestinationIBAN']
            ?? $details['data']['destination_iban']
            ?? $details['transaction']['DestinationIBAN']
            ?? null;
    }

    /**
     * Extract currency code from transaction details.
     */
    protected function extractCurrencyCode(array $details): string
    {
        $currencyCode = $details['CurrencyCode']
            ?? $details['currency_code']
            ?? $details['Currency']
            ?? $details['currency']
            ?? $details['data']['CurrencyCode']
            ?? $details['data']['currency_code']
            ?? 'EUR'; // Default to EUR for SEPA

        // Handle numeric currency codes
        if (is_numeric($currencyCode)) {
            $currencyCode = $this->numericToCurrencyCode((int)$currencyCode);
        }

        return strtoupper($currencyCode);
    }

    /**
     * Convert ISO 4217 numeric code to alpha code.
     */
    protected function numericToCurrencyCode(int $numeric): string
    {
        $map = [
            978 => 'EUR',
            840 => 'USD',
            826 => 'GBP',
            756 => 'CHF',
            392 => 'JPY',
            124 => 'CAD',
            36 => 'AUD',
        ];

        return $map[$numeric] ?? 'EUR';
    }

    /**
     * Extract sender name from transaction details.
     */
    protected function extractSenderName(array $details): string
    {
        return $details['transaction_data']['paymentSourceName']
            ?? $details['paymentSourceName']
            ?? $details['reference']
            ?? $details['Reference']
            ?? $details['transaction_data']['reference']
            ?? 'Unknown Sender';
    }
}
