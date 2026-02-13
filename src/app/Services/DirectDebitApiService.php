<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DirectDebitApiService
{
    /**
     * SDD Sequence Type mapping.
     */
    const SDD_SEQ_FIRST = 'FRST(First)';
    const SDD_SEQ_RECURRING = 'RCUR(Recurring)';
    const SDD_SEQ_FINAL = 'FNAL(Final)';
    const SDD_SEQ_ONE_OFF = 'OOFF(OneOff)';

    protected ?string $accessToken = null;
    protected ?int $tokenExpiresAt = null;

    /**
     * Get the SH Financial API base URL from config.
     */
    protected function apiBaseUrl(): string
    {
        return rtrim(config('services.shfinancial.api_url', 'https://api.sh-payments.com'), '/') . '/api/v1';
    }

    /**
     * Obtain an OAuth2 access token via client_credentials grant.
     * Caches token in memory for the duration of the process.
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $tokenUrl = rtrim(config('services.shfinancial.api_url', 'https://api.sh-payments.com'), '/') . '/connect/token';

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.shfinancial.client_id'),
            'client_secret' => config('services.shfinancial.client_secret'),
            'scope' => config('services.shfinancial.scope', 'apiv1.programme'),
        ]);

        if (!$response->successful()) {
            Log::channel('directdebit')->error('DD API: Token request failed', [
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to obtain SH Financial access token: HTTP ' . $response->status());
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);

        Log::channel('directdebit')->info('DD API: Access token obtained', [
            'expires_in' => $data['expires_in'] ?? 3600,
        ]);

        return $this->accessToken;
    }

    /**
     * Submit a SEPA Direct Debit collection.
     * Endpoint: POST /api/v1/payment/sdd/create
     *
     * @param array $instruction SDD collection instruction
     * @return array ['success' => bool, 'transaction_uid' => string|null, 'error' => string|null]
     */
    public function submitPayment(array $instruction): array
    {
        try {
            $sddSequenceType = ($instruction['sequenceType'] ?? 'FRST') === 'FRST'
                ? self::SDD_SEQ_FIRST
                : self::SDD_SEQ_RECURRING;

            $payload = [
                'correlationId' => $instruction['correlationId'] ?? null,
                'destinationLedgerUid' => $instruction['destinationLedgerUid'],
                'createDirectDebitRequests' => [
                    [
                        'sourceLedgerUid' => $instruction['sourceLedgerUid'],
                        'amount' => $instruction['amount'], // minor units (cents)
                        'reference' => $instruction['reference'],
                        'paymentReason' => $instruction['paymentReason'] ?? 'SEPA Direct Debit Collection',
                        'mandateInfo' => [
                            'id' => $instruction['mandateId'] ?? $instruction['correlationId'],
                            'eSignature' => $instruction['mandateESignature'] ?? '',
                            'dateOfSignature' => $instruction['mandateDateOfSignature'] ?? now()->toDateString(),
                            'creditorId' => $instruction['creditorId'] ?? config('services.shfinancial.creditor_id', ''),
                            'amendmentInfo' => null,
                        ],
                        'sddSequenceType' => $sddSequenceType,
                        'settlementDate' => $instruction['settlementDate'] ?? now()->toDateString(),
                    ],
                ],
            ];

            Log::channel('directdebit')->info('DD API: Submitting SDD collection', [
                'correlation_id' => $instruction['correlationId'],
                'reference' => $instruction['reference'],
                'amount' => $instruction['amount'],
                'sequence_type' => $sddSequenceType,
                'endpoint' => $this->apiBaseUrl() . '/payment/sdd/create',
            ]);

            $token = $this->getAccessToken();

            $response = Http::timeout(30)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiBaseUrl() . '/payment/sdd/create', $payload);

            if ($response->successful()) {
                $data = $response->json();

                // SH Financial wraps response in GenericResponse:
                // { isSuccess, errorMessage, item: { correlationId, destinationLedgerUid, createDirectDebitResponses: [...] } }
                $isSuccess = $data['isSuccess'] ?? false;
                $item = $data['item'] ?? null;
                $ddResponses = $item['createDirectDebitResponses'] ?? [];
                $firstResponse = $ddResponses[0] ?? null;

                Log::channel('directdebit')->info('DD API: SDD collection response', [
                    'correlation_id' => $instruction['correlationId'],
                    'reference' => $instruction['reference'],
                    'is_success' => $isSuccess,
                    'response' => $data,
                ]);

                if ($isSuccess && $firstResponse && ($firstResponse['isSuccess'] ?? false)) {
                    return [
                        'success' => true,
                        'transaction_uid' => $firstResponse['transactionUid'] ?? null,
                        'unique_key' => $firstResponse['uniqueKey'] ?? null,
                        'response' => $data,
                    ];
                }

                // API returned 200 but isSuccess=false or individual request failed
                $errorMsg = $data['errorMessage']
                    ?? ($firstResponse['errors'][0] ?? null)
                    ?? 'SDD create returned unsuccessful';

                return [
                    'success' => false,
                    'transaction_uid' => $firstResponse['transactionUid'] ?? null,
                    'error' => $errorMsg,
                    'response' => $data,
                ];
            }

            $errorBody = $response->body();
            Log::channel('directdebit')->error('DD API: SDD submission failed', [
                'correlation_id' => $instruction['correlationId'],
                'http_status' => $response->status(),
                'body' => $errorBody,
            ]);

            return [
                'success' => false,
                'transaction_uid' => null,
                'error' => "HTTP {$response->status()}: {$errorBody}",
            ];
        } catch (\Exception $e) {
            Log::channel('directdebit')->error('DD API: SDD exception', [
                'correlation_id' => $instruction['correlationId'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'transaction_uid' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a SEPA Direct Debit payment.
     * Endpoint: POST /api/v1/payment/sdd/cancel
     *
     * @param string $transactionUid
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function cancelPayment(string $transactionUid): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::timeout(30)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiBaseUrl() . '/payment/sdd/cancel', [
                    'transactionUid' => $transactionUid,
                ]);

            if ($response->successful()) {
                Log::channel('directdebit')->info('DD API: SDD cancelled', [
                    'transaction_uid' => $transactionUid,
                ]);
                return ['success' => true, 'error' => null];
            }

            $errorBody = $response->body();
            Log::channel('directdebit')->error('DD API: SDD cancel failed', [
                'transaction_uid' => $transactionUid,
                'http_status' => $response->status(),
                'body' => $errorBody,
            ]);

            return ['success' => false, 'error' => "HTTP {$response->status()}: {$errorBody}"];
        } catch (\Exception $e) {
            Log::channel('directdebit')->error('DD API: SDD cancel exception', [
                'transaction_uid' => $transactionUid,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Look up a ledgerUid for an IBAN via SH Financial bank accounts API.
     * Endpoint: GET /api/v1/bankaccounts?Filters[iban]={iban}
     *
     * @param string $iban The IBAN to look up
     * @return string|null The ledgerUid or null if not found
     */
    public function lookupLedgerUidByIban(string $iban): ?string
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::timeout(15)
                ->withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($this->apiBaseUrl() . '/bankaccounts', [
                    'Filters[iban]' => $iban,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $accounts = $data['data'] ?? [];

                if (!empty($accounts)) {
                    $ledgerUid = $accounts[0]['ledgerUid'] ?? null;

                    Log::channel('directdebit')->info('DD API: IBAN lookup success', [
                        'iban' => $iban,
                        'ledger_uid' => $ledgerUid,
                    ]);

                    return $ledgerUid;
                }
            }

            Log::channel('directdebit')->warning('DD API: IBAN lookup failed', [
                'iban' => $iban,
                'http_status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::channel('directdebit')->error('DD API: IBAN lookup exception', [
                'iban' => $iban,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Fetch transaction status from SH Financial API.
     * Uses direct API with OAuth2 auth.
     *
     * @param string $transactionUid
     * @return array|null
     */
    public function getTransactionStatus(string $transactionUid): ?array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::timeout(10)
                ->withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($this->apiBaseUrl() . '/transactions/' . $transactionUid);

            if ($response->successful()) {
                return $response->json();
            }

            Log::channel('directdebit')->warning('DD API: Transaction status fetch failed', [
                'transaction_uid' => $transactionUid,
                'http_status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::channel('directdebit')->error('DD API: Transaction status exception', [
                'transaction_uid' => $transactionUid,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
