<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Solana RPC Integration Service
 * 
 * Handles direct interaction with the Solana blockchain via JSON-RPC.
 * Used for: sending SPL token transfers, fetching transaction details,
 * checking balances, and verifying confirmations.
 * 
 * Solana RPC Docs: https://docs.solana.com/api
 */
class SolanaRpcService
{
    protected string $rpcUrl;

    public function __construct()
    {
        $this->rpcUrl = config('services.solana.rpc_url', 'https://api.mainnet-beta.solana.com');
    }

    /**
     * Get full transaction details by signature.
     * Used for expandable row tracking detail.
     *
     * @param string $signature  Solana transaction signature
     * @return array|null
     */
    public function getTransaction(string $signature): ?array
    {
        return $this->rpcCall('getTransaction', [
            $signature,
            [
                'encoding' => 'jsonParsed',
                'maxSupportedTransactionVersion' => 0,
            ],
        ]);
    }

    /**
     * Get confirmation status for one or more transaction signatures.
     *
     * @param array $signatures  Array of signature strings
     * @return array|null
     */
    public function getSignatureStatuses(array $signatures): ?array
    {
        return $this->rpcCall('getSignatureStatuses', [
            $signatures,
            ['searchTransactionHistory' => true],
        ]);
    }

    /**
     * Get SOL balance for a public key (in lamports).
     *
     * @param string $pubkey  Solana public key
     * @return int|null  Balance in lamports
     */
    public function getBalance(string $pubkey): ?int
    {
        $result = $this->rpcCall('getBalance', [$pubkey]);

        if ($result && isset($result['value'])) {
            return (int) $result['value'];
        }

        return null;
    }

    /**
     * Get SPL token account balance (USDT/USDC).
     *
     * @param string $tokenAccountAddress  The Associated Token Account address
     * @return array|null  ['amount' => string, 'decimals' => int, 'uiAmount' => float]
     */
    public function getTokenAccountBalance(string $tokenAccountAddress): ?array
    {
        $result = $this->rpcCall('getTokenAccountBalance', [$tokenAccountAddress]);

        if ($result && isset($result['value'])) {
            return $result['value'];
        }

        return null;
    }

    /**
     * Get all SPL token accounts for a wallet owner, filtered by mint.
     *
     * @param string $ownerPubkey  Wallet public key
     * @param string $mintAddress  Token mint address (e.g. USDT or USDC mint)
     * @return array|null
     */
    public function getTokenAccountsByOwner(string $ownerPubkey, string $mintAddress): ?array
    {
        return $this->rpcCall('getTokenAccountsByOwner', [
            $ownerPubkey,
            ['mint' => $mintAddress],
            ['encoding' => 'jsonParsed'],
        ]);
    }

    /**
     * Get recent transaction signatures for an address.
     * Useful for syncing/backfilling transaction records.
     *
     * @param string $pubkey  Solana public key
     * @param int $limit  Max number of signatures to return
     * @return array|null
     */
    public function getSignaturesForAddress(string $pubkey, int $limit = 20): ?array
    {
        return $this->rpcCall('getSignaturesForAddress', [
            $pubkey,
            ['limit' => $limit],
        ]);
    }

    /**
     * Send a signed transaction to the network.
     *
     * @param string $signedTransaction  Base64-encoded signed transaction
     * @return string|null  Transaction signature on success
     */
    public function sendTransaction(string $signedTransaction): ?string
    {
        $result = $this->rpcCall('sendTransaction', [
            $signedTransaction,
            [
                'encoding' => 'base64',
                'skipPreflight' => false,
                'preflightCommitment' => 'confirmed',
            ],
        ]);

        return $result;
    }

    /**
     * Get the latest blockhash (required for building transactions).
     *
     * @return array|null  ['blockhash' => string, 'lastValidBlockHeight' => int]
     */
    public function getLatestBlockhash(): ?array
    {
        $result = $this->rpcCall('getLatestBlockhash', [
            ['commitment' => 'finalized'],
        ]);

        if ($result && isset($result['value'])) {
            return $result['value'];
        }

        return null;
    }

    /**
     * Get minimum balance required for rent exemption.
     * Needed for creating Associated Token Accounts.
     *
     * @param int $dataLength  Account data length in bytes
     * @return int|null  Minimum balance in lamports
     */
    public function getMinimumBalanceForRentExemption(int $dataLength): ?int
    {
        return $this->rpcCall('getMinimumBalanceForRentExemption', [$dataLength]);
    }

    /**
     * Parse a transaction result into a structured tracking detail object.
     * This is the data shown in the expandable transaction row.
     *
     * @param string $signature
     * @return array|null
     */
    public function getTrackingDetail(string $signature): ?array
    {
        $tx = $this->getTransaction($signature);

        if (!$tx) {
            return null;
        }

        $statuses = $this->getSignatureStatuses([$signature]);
        $statusInfo = $statuses['value'][0] ?? null;

        $meta = $tx['meta'] ?? [];
        $blockTime = $tx['blockTime'] ?? null;
        $slot = $tx['slot'] ?? null;

        $detail = [
            'tx_signature' => $signature,
            'block_slot' => $slot,
            'block_time' => $blockTime ? date('Y-m-d\TH:i:s\Z', $blockTime) : null,
            'fee_lamports' => $meta['fee'] ?? null,
            'fee_sol' => isset($meta['fee']) ? $meta['fee'] / 1_000_000_000 : null,
            'status' => isset($meta['err']) && $meta['err'] !== null ? 'failed' : 'success',
            'confirmations' => $statusInfo['confirmations'] ?? null,
            'confirmation_status' => $statusInfo['confirmationStatus'] ?? null,
            'from_address' => null,
            'to_address' => null,
            'token_amount' => null,
            'token_mint' => null,
            'token_symbol' => null,
            'explorer_url' => 'https://explorer.solana.com/tx/' . $signature,
            'solscan_url' => 'https://solscan.io/tx/' . $signature,
        ];

        // Parse token transfer instructions
        $instructions = $tx['transaction']['message']['instructions'] ?? [];
        $innerInstructions = $meta['innerInstructions'] ?? [];

        // Look for SPL token transfer in parsed instructions
        foreach ($instructions as $ix) {
            if (isset($ix['parsed']['type']) && $ix['parsed']['type'] === 'transferChecked') {
                $info = $ix['parsed']['info'] ?? [];
                $detail['from_address'] = $info['authority'] ?? $info['source'] ?? null;
                $detail['to_address'] = $info['destination'] ?? null;
                $detail['token_amount'] = $info['tokenAmount']['uiAmount'] ?? null;
                $detail['token_mint'] = $info['mint'] ?? null;
                break;
            }
            if (isset($ix['parsed']['type']) && $ix['parsed']['type'] === 'transfer' && isset($ix['program']) && $ix['program'] === 'spl-token') {
                $info = $ix['parsed']['info'] ?? [];
                $detail['from_address'] = $info['authority'] ?? $info['source'] ?? null;
                $detail['to_address'] = $info['destination'] ?? null;
                $detail['token_amount'] = isset($info['amount']) ? (float) $info['amount'] : null;
                break;
            }
        }

        // Also check inner instructions if not found
        if (!$detail['from_address']) {
            foreach ($innerInstructions as $inner) {
                foreach ($inner['instructions'] ?? [] as $ix) {
                    if (isset($ix['parsed']['type']) && in_array($ix['parsed']['type'], ['transfer', 'transferChecked'])) {
                        $info = $ix['parsed']['info'] ?? [];
                        $detail['from_address'] = $info['authority'] ?? $info['source'] ?? null;
                        $detail['to_address'] = $info['destination'] ?? null;
                        $detail['token_amount'] = $info['tokenAmount']['uiAmount'] ?? $info['amount'] ?? null;
                        $detail['token_mint'] = $info['mint'] ?? null;
                        break 2;
                    }
                }
            }
        }

        // Map known mints to symbols
        $detail['token_symbol'] = $this->resolveTokenSymbol($detail['token_mint']);

        return $detail;
    }

    /**
     * Map known SPL token mint addresses to symbols.
     *
     * @param string|null $mint
     * @return string|null
     */
    protected function resolveTokenSymbol(?string $mint): ?string
    {
        if (!$mint) {
            return null;
        }

        $knownMints = [
            'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB' => 'USDT',
            'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v' => 'USDC',
            'HzwqbKZw8HxMN6bF2yFZNrht3c2iXXzpKcFu7uBEDKtr' => 'EURC',
        ];

        return $knownMints[$mint] ?? null;
    }

    // ─── JSON-RPC Helper ─────────────────────────────────────────────────

    protected function rpcCall(string $method, array $params = [], int $maxRetries = 3): mixed
    {
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $response = Http::post($this->rpcUrl, [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => $method,
                    'params' => $params,
                ]);

                if ($response->successful()) {
                    $json = $response->json();

                    if (isset($json['error'])) {
                        // Check for 429 inside JSON-RPC error
                        if (($json['error']['code'] ?? 0) == 429) {
                            $attempt++;
                            if ($attempt <= $maxRetries) {
                                usleep(500000 * $attempt); // 500ms, 1s, 1.5s backoff
                                continue;
                            }
                        }
                        Log::error('Solana RPC error', [
                            'method' => $method,
                            'error' => $json['error'],
                        ]);
                        return null;
                    }

                    return $json['result'] ?? null;
                }

                // HTTP-level 429 rate limit
                if ($response->status() === 429) {
                    $attempt++;
                    if ($attempt <= $maxRetries) {
                        usleep(500000 * $attempt); // 500ms, 1s, 1.5s backoff
                        continue;
                    }
                }

                Log::error('Solana RPC HTTP error', [
                    'method' => $method,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Solana RPC exception', [
                    'method' => $method,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        }

        return null;
    }
}
