<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WalletIDs.net API Integration Service
 * 
 * Non-custodial wallet factory + monitoring service.
 * Creates HD wallets, derives addresses, monitors balances, receives webhooks.
 * Does NOT sign or send transactions — use SolanaRpcService for that.
 * 
 * API Docs: https://walletids.net/api-documentation
 */
class WalletIdsService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.walletids.base_url', 'https://walletids.net/api'), '/');
        $this->apiKey = config('services.walletids.api_key', '');
    }

    /**
     * Create a new wallet (master or standalone).
     *
     * @param string $network  e.g. 'solana'
     * @param string $currency e.g. 'usdt', 'usdc'
     * @param string $mode     'master' or 'standalone'
     * @param string $name     Friendly name
     * @param string|null $externalId  Optional external reference ID
     * @param string|null $webhookUrl  Optional webhook URL for this wallet
     * @return array|null  Response from WalletIDs.net (includes wallet_hash, address, mnemonic/private_key)
     */
    public function createWallet(string $network, string $currency, string $mode, string $name, ?string $externalId = null, ?string $webhookUrl = null): ?array
    {
        $payload = [
            'network_key' => $network,
            'currency_symbol' => strtoupper($currency),
            'wallet_name' => $name,
        ];

        if ($externalId) {
            $payload['external_id'] = $externalId;
        }

        if ($webhookUrl) {
            $payload['webhook_url'] = $webhookUrl;
        }

        return $this->post('/wallets', $payload);
    }

    /**
     * Derive a new unique address from an HD master wallet.
     *
     * @param string $walletHash  The master wallet hash
     * @param string|null $sessionId  Optional session/external ID to link the derived address
     * @param array|null $metadata  Optional metadata
     * @return array|null
     */
    public function deriveAddress(string $walletHash, ?string $sessionId = null, ?array $metadata = null): ?array
    {
        $payload = [];

        if ($sessionId) {
            $payload['session_id'] = $sessionId;
        }

        if ($metadata) {
            $payload['metadata'] = $metadata;
        }

        return $this->post("/wallets/{$walletHash}/derive", $payload);
    }

    /**
     * Get wallet details.
     *
     * @param string $walletHash
     * @return array|null
     */
    public function getWallet(string $walletHash): ?array
    {
        return $this->get("/wallets/{$walletHash}");
    }

    /**
     * List all wallets with optional filters.
     *
     * @param string|null $network
     * @param string|null $currency
     * @param int $limit
     * @param int $offset
     * @return array|null
     */
    public function listWallets(?string $network = null, ?string $currency = null, int $limit = 50, int $offset = 0): ?array
    {
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($network) {
            $params['network'] = $network;
        }
        if ($currency) {
            $params['currency'] = $currency;
        }

        return $this->get('/wallets', $params);
    }

    /**
     * Get the current balance of a wallet or derived address.
     *
     * @param string $walletHash
     * @return array|null
     */
    public function getBalance(string $walletHash): ?array
    {
        return $this->get("/wallets/{$walletHash}/balance");
    }

    /**
     * Disable (archive) a wallet. Stops monitoring but retains data.
     *
     * @param string $walletHash
     * @return array|null
     */
    public function disableWallet(string $walletHash): ?array
    {
        return $this->delete("/wallets/{$walletHash}");
    }

    /**
     * Lookup a wallet by external ID.
     *
     * @param string $externalId
     * @param string|null $parentWalletHash
     * @return array|null
     */
    public function lookupByExternalId(string $externalId, ?string $parentWalletHash = null): ?array
    {
        $params = ['external_id' => $externalId];

        if ($parentWalletHash) {
            $params['parent_wallet_hash'] = $parentWalletHash;
        }

        return $this->get('/wallets/lookup', $params);
    }

    /**
     * Get historical balance snapshots for a wallet.
     *
     * @param string $walletHash
     * @param int $limit
     * @return array|null
     */
    public function getBalanceHistory(string $walletHash, int $limit = 50): ?array
    {
        return $this->get("/wallets/{$walletHash}/balance/history", ['limit' => $limit]);
    }

    /**
     * Update monitoring settings for a wallet.
     *
     * @param string $walletHash
     * @param bool $enabled
     * @param int $intervalSeconds
     * @return array|null
     */
    public function updateMonitoring(string $walletHash, bool $enabled, int $intervalSeconds = 60): ?array
    {
        return $this->put("/wallets/{$walletHash}/monitoring", [
            'enabled' => $enabled,
            'interval_seconds' => $intervalSeconds,
        ]);
    }

    /**
     * Recover the private key or mnemonic for a wallet.
     * Rate limited: 1 recovery per hour per account.
     *
     * @param string $walletHash
     * @return array|null  Returns private_key (standalone/derived) or mnemonic (master)
     */
    public function recoverKey(string $walletHash): ?array
    {
        return $this->get("/wallets/{$walletHash}/recover");
    }

    /**
     * List supported blockchain networks.
     *
     * @return array|null
     */
    public function listNetworks(): ?array
    {
        return $this->get('/networks');
    }

    /**
     * List currencies available on a specific network.
     *
     * @param string $network
     * @return array|null
     */
    public function listCurrencies(string $network): ?array
    {
        return $this->get("/networks/{$network}/currencies");
    }

    /**
     * Verify webhook HMAC-SHA256 signature.
     *
     * @param string $payload  Raw request body
     * @param string $signature  Signature from webhook header
     * @param string $secret  Webhook secret
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }

    // ─── HTTP Helpers ────────────────────────────────────────────────────

    protected function get(string $endpoint, array $params = []): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . $endpoint, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('WalletIDs API GET error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WalletIDs API GET exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function post(string $endpoint, array $data = []): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->post($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('WalletIDs API POST error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WalletIDs API POST exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function put(string $endpoint, array $data = []): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->put($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('WalletIDs API PUT error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WalletIDs API PUT exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function delete(string $endpoint): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->delete($this->baseUrl . $endpoint);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('WalletIDs API DELETE error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WalletIDs API DELETE exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
