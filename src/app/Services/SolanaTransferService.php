<?php

namespace App\Services;

use App\Models\CryptoWallet;
use App\Models\CryptoWalletTransaction;
use Illuminate\Support\Facades\Log;

/**
 * Solana SPL Token Transfer Service (Pure PHP)
 * 
 * Handles the full lifecycle of an SPL token transfer on Solana:
 * 1. Recover private key from WalletIDs.net
 * 2. Resolve Associated Token Accounts (ATAs) for sender/receiver
 * 3. Build the transaction binary (compact Solana wire format)
 * 4. Sign with Ed25519 via PHP sodium extension
 * 5. Broadcast via Solana RPC sendTransaction
 * 6. Update the CryptoWalletTransaction record with tx signature
 * 
 * Known SPL Token Mint Addresses (Solana Mainnet):
 * - USDT: Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB
 * - USDC: EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v
 * - EURC: HzwqbKZw8HxMN6bF2yFZNrht3c2iXXzpKcFu7uBEDKtr
 */
class SolanaTransferService
{
    // SPL Token Program ID
    const TOKEN_PROGRAM_ID = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';

    // Associated Token Account Program ID
    const ATA_PROGRAM_ID = 'ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL';

    // System Program ID
    const SYSTEM_PROGRAM_ID = '11111111111111111111111111111111';

    // Sysvar Rent
    const SYSVAR_RENT = 'SysvarRent111111111111111111111111111111111';

    // Known mint addresses
    const MINTS = [
        'USDT' => 'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB',
        'USDC' => 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v',
        'EURC' => 'HzwqbKZw8HxMN6bF2yFZNrht3c2iXXzpKcFu7uBEDKtr',
    ];

    // Both USDT and USDC on Solana use 6 decimals
    const TOKEN_DECIMALS = 6;

    protected WalletIdsService $walletIdsService;
    protected SolanaRpcService $solanaRpcService;

    public function __construct(WalletIdsService $walletIdsService, SolanaRpcService $solanaRpcService)
    {
        $this->walletIdsService = $walletIdsService;
        $this->solanaRpcService = $solanaRpcService;
    }

    /**
     * Execute a full SPL token transfer.
     *
     * @param CryptoWallet $wallet  The source wallet
     * @param string $toAddress  Destination wallet public key (base58)
     * @param float $amount  Human-readable amount (e.g. 100.50 USDT)
     * @param CryptoWalletTransaction $txRecord  The transaction record to update
     * @return array  ['success' => bool, 'signature' => string|null, 'error' => string|null]
     */
    public function transfer(CryptoWallet $wallet, string $toAddress, float $amount, CryptoWalletTransaction $txRecord): array
    {
        try {
            // 1. Resolve the token mint
            $mintAddress = self::MINTS[$wallet->wallet_currency] ?? null;
            if (!$mintAddress) {
                return $this->fail($txRecord, "Unsupported currency: {$wallet->wallet_currency}");
            }

            // 2. Look up the central GAS wallet for this network (fee payer)
            $gasWallet = CryptoWallet::where('wallet_type', 'gas')
                ->where('wallet_network', $wallet->wallet_network)
                ->notDeleted()->active()->first();
            if (!$gasWallet) {
                return $this->fail($txRecord, 'No active GAS wallet found for ' . $wallet->wallet_network . '. Create a GAS wallet to pay transaction fees.');
            }

            // 3. Recover private keys from WalletIDs.net (gas wallet + source wallet)
            Log::info('SolanaTransfer: recovering gas wallet key', ['wallet_hash' => $gasWallet->walletids_wallet_hash]);
            $gasKeyData = $this->walletIdsService->recoverKey($gasWallet->walletids_wallet_hash);
            if (!$gasKeyData || empty($gasKeyData['private_key'])) {
                return $this->fail($txRecord, 'Failed to recover GAS wallet private key from WalletIDs.net.');
            }

            Log::info('SolanaTransfer: recovering source wallet key', ['wallet_hash' => $wallet->walletids_wallet_hash]);
            $srcKeyData = $this->walletIdsService->recoverKey($wallet->walletids_wallet_hash);
            if (!$srcKeyData || empty($srcKeyData['private_key'])) {
                return $this->fail($txRecord, 'Failed to recover source wallet private key from WalletIDs.net.');
            }

            // 4. Decode and derive keypairs for both wallets
            $gasKeypair = $this->resolveKeypair($gasKeyData['private_key'], $gasWallet->wallet_address, 'GAS');
            if (isset($gasKeypair['error'])) {
                return $this->fail($txRecord, $gasKeypair['error']);
            }

            $srcKeypair = $this->resolveKeypair($srcKeyData['private_key'], $wallet->wallet_address, 'source');
            if (isset($srcKeypair['error'])) {
                return $this->fail($txRecord, $srcKeypair['error']);
            }

            $feePayerPubkey = $gasKeypair['publicKey'];
            $feePayerSecretKey = $gasKeypair['secretKey'];
            $fromPubkey = $srcKeypair['publicKey'];
            $fromSecretKey = $srcKeypair['secretKey'];

            $toPubkey = $this->base58Decode($toAddress);
            $mintPubkey = $this->base58Decode($mintAddress);

            if (!$toPubkey || strlen($toPubkey) !== 32) {
                return $this->fail($txRecord, 'Invalid destination address.');
            }

            // 5. Derive Associated Token Accounts
            $fromAta = $this->deriveAta($fromPubkey, $mintPubkey);
            $toAta = $this->deriveAta($toPubkey, $mintPubkey);

            if (!$fromAta || !$toAta) {
                return $this->fail($txRecord, 'Failed to derive Associated Token Accounts.');
            }

            // 6. Check if destination ATA exists — if not, we need to create it
            $needsCreateAta = false;
            $toAtaInfo = $this->solanaRpcService->getTokenAccountBalance($this->base58Encode($toAta));
            if ($toAtaInfo === null) {
                $needsCreateAta = true;
            }

            // 7. Convert amount to token base units (6 decimals for USDT/USDC)
            $rawAmount = (int) round($amount * pow(10, self::TOKEN_DECIMALS));

            // 8. Get recent blockhash
            $blockhashData = $this->solanaRpcService->getLatestBlockhash();
            if (!$blockhashData || empty($blockhashData['blockhash'])) {
                return $this->fail($txRecord, 'Failed to get recent blockhash from Solana RPC.');
            }
            $recentBlockhash = $this->base58Decode($blockhashData['blockhash']);

            // 9. Build instructions (fee payer = GAS wallet pays for ATA creation + tx fees)
            $instructions = [];

            if ($needsCreateAta) {
                // GAS wallet pays for ATA creation
                $instructions[] = $this->buildCreateAtaInstruction($feePayerPubkey, $toPubkey, $toAta, $mintPubkey);
            }

            // SPL Token transferChecked instruction (authority = source wallet)
            $instructions[] = $this->buildTransferCheckedInstruction(
                $fromAta,
                $mintPubkey,
                $toAta,
                $fromPubkey, // authority (signer)
                $rawAmount,
                self::TOKEN_DECIMALS
            );

            // 10. Build transaction message (fee payer = GAS wallet)
            $message = $this->buildTransactionMessage(
                $feePayerPubkey,
                $recentBlockhash,
                $instructions,
                $needsCreateAta,
                $toPubkey,
                $fromAta,
                $toAta,
                $mintPubkey
            );

            // 11. Sign with both keys: fee payer first, then source wallet
            //     Signatures must be in the same order as signers in the account list
            $feePayerSignature = sodium_crypto_sign_detached($message, $feePayerSecretKey);
            $srcSignature = sodium_crypto_sign_detached($message, $fromSecretKey);

            // 12. Build the full signed transaction (2 signatures)
            $signedTx = $this->buildSignedTransaction([$feePayerSignature, $srcSignature], $message);

            // 13. Broadcast
            $txSignatureBase58 = $this->base58Encode($feePayerSignature);
            Log::info('SolanaTransfer: broadcasting', ['tx_signature' => $txSignatureBase58]);

            $broadcastResult = $this->solanaRpcService->sendTransaction(base64_encode($signedTx));

            if ($broadcastResult === null) {
                return $this->fail($txRecord, 'Failed to broadcast transaction. It may have been rejected by the network.');
            }

            // 14. Update the transaction record
            $txRecord->update([
                'solana_tx_signature' => $broadcastResult,
                'transaction_status' => 'confirmed',
                'datetime_confirmed' => now(),
                'datetime_updated' => now(),
            ]);

            // Wipe key material from memory
            sodium_memzero($feePayerSecretKey);
            sodium_memzero($fromSecretKey);

            Log::info('SolanaTransfer: success', ['tx_signature' => $broadcastResult]);

            return [
                'success' => true,
                'signature' => $broadcastResult,
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('SolanaTransfer: exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->fail($txRecord, 'Transfer error: ' . $e->getMessage());
        }
    }

    /**
     * Resolve a base58-encoded private key into a keypair (secretKey + publicKey).
     * Returns ['secretKey' => ..., 'publicKey' => ...] or ['error' => '...'].
     */
    protected function resolveKeypair(string $base58PrivateKey, string $expectedAddress, string $label): array
    {
        $privateKeyBytes = $this->base58Decode($base58PrivateKey);
        if (!$privateKeyBytes || strlen($privateKeyBytes) < 32) {
            return ['error' => "Invalid {$label} private key format from WalletIDs.net."];
        }

        if (strlen($privateKeyBytes) === 32) {
            $keypair = sodium_crypto_sign_seed_keypair($privateKeyBytes);
            $secretKey = sodium_crypto_sign_secretkey($keypair);
            $publicKey = sodium_crypto_sign_publickey($keypair);
        } elseif (strlen($privateKeyBytes) === 64) {
            $secretKey = $privateKeyBytes;
            $publicKey = substr($privateKeyBytes, 32, 32);
        } else {
            return ['error' => "Unexpected {$label} private key length: " . strlen($privateKeyBytes)];
        }

        $derivedAddress = $this->base58Encode($publicKey);
        if ($derivedAddress !== $expectedAddress) {
            return ['error' => "{$label} key mismatch: derived {$derivedAddress} != expected {$expectedAddress}"];
        }

        return ['secretKey' => $secretKey, 'publicKey' => $publicKey];
    }

    /**
     * Derive the Associated Token Account address (PDA).
     * ATA = PDA([owner, TOKEN_PROGRAM_ID, mint], ATA_PROGRAM_ID)
     */
    protected function deriveAta(string $ownerPubkey, string $mintPubkey): ?string
    {
        $tokenProgramId = $this->base58Decode(self::TOKEN_PROGRAM_ID);
        $ataProgramId = $this->base58Decode(self::ATA_PROGRAM_ID);

        // Seeds: [owner, token_program_id, mint]
        $seeds = [$ownerPubkey, $tokenProgramId, $mintPubkey];

        return $this->findProgramAddress($seeds, $ataProgramId);
    }

    /**
     * Find a Program Derived Address (PDA).
     * Iterates bump seed from 255 down to 0 until a valid off-curve point is found.
     */
    protected function findProgramAddress(array $seeds, string $programId): ?string
    {
        for ($bump = 255; $bump >= 0; $bump--) {
            $seedsWithBump = array_merge($seeds, [chr($bump)]);
            $hash = $this->createProgramAddressHash($seedsWithBump, $programId);

            if ($hash !== null && !$this->isOnCurve($hash)) {
                return $hash;
            }
        }

        return null;
    }

    /**
     * Create the SHA-256 hash for a program address.
     */
    protected function createProgramAddressHash(array $seeds, string $programId): ?string
    {
        $buffer = '';
        foreach ($seeds as $seed) {
            if (strlen($seed) > 32) {
                return null;
            }
            $buffer .= $seed;
        }
        $buffer .= $programId;
        $buffer .= 'ProgramDerivedAddress';

        return hash('sha256', $buffer, true);
    }

    /**
     * Check if a 32-byte value is on the Ed25519 curve.
     * If it IS on the curve, it's not a valid PDA.
     */
    protected function isOnCurve(string $point): bool
    {
        // Try to use sodium to check if the point is a valid Ed25519 public key
        // by attempting a signature verification with a dummy signature
        // A valid PDA should NOT be on the curve
        try {
            // sodium_crypto_sign_verify_detached will fail if the public key is not on curve
            // But we can use a simpler check: try to convert to an Ed25519 point
            // The Ed25519 library will reject points not on the curve
            $dummyMessage = 'test';
            $dummySignature = str_repeat("\0", 64);
            @sodium_crypto_sign_verify_detached($dummySignature, $dummyMessage, $point);
            // If it didn't throw, the point is on the curve
            return true;
        } catch (\SodiumException $e) {
            // Point is not on the curve — this is what we want for a PDA
            return false;
        }
    }

    /**
     * Build a Create Associated Token Account instruction.
     */
    protected function buildCreateAtaInstruction(
        string $payer,
        string $owner,
        string $ata,
        string $mint
    ): array {
        // The Create ATA instruction has no data — it's inferred from accounts
        return [
            'program_id' => self::ATA_PROGRAM_ID,
            'accounts' => [
                ['pubkey' => $payer, 'is_signer' => true, 'is_writable' => true],
                ['pubkey' => $ata, 'is_signer' => false, 'is_writable' => true],
                ['pubkey' => $owner, 'is_signer' => false, 'is_writable' => false],
                ['pubkey' => $mint, 'is_signer' => false, 'is_writable' => false],
                ['pubkey' => $this->base58Decode(self::SYSTEM_PROGRAM_ID), 'is_signer' => false, 'is_writable' => false],
                ['pubkey' => $this->base58Decode(self::TOKEN_PROGRAM_ID), 'is_signer' => false, 'is_writable' => false],
            ],
            'data' => '', // No instruction data for create ATA
        ];
    }

    /**
     * Build a SPL Token transferChecked instruction.
     * Instruction index 12 in the SPL Token program.
     */
    protected function buildTransferCheckedInstruction(
        string $source,
        string $mint,
        string $destination,
        string $authority,
        int $amount,
        int $decimals
    ): array {
        // transferChecked instruction data:
        // [1 byte: instruction index (12)] [8 bytes: amount LE] [1 byte: decimals]
        $data = chr(12); // instruction index for transferChecked
        $data .= pack('P', $amount); // uint64 little-endian
        $data .= chr($decimals);

        return [
            'program_id' => self::TOKEN_PROGRAM_ID,
            'accounts' => [
                ['pubkey' => $source, 'is_signer' => false, 'is_writable' => true],
                ['pubkey' => $mint, 'is_signer' => false, 'is_writable' => false],
                ['pubkey' => $destination, 'is_signer' => false, 'is_writable' => true],
                ['pubkey' => $authority, 'is_signer' => true, 'is_writable' => false],
            ],
            'data' => $data,
        ];
    }

    /**
     * Build the Solana transaction message (v0 legacy format).
     */
    protected function buildTransactionMessage(
        string $feePayer,
        string $recentBlockhash,
        array $instructions,
        bool $needsCreateAta,
        string $toPubkey,
        string $fromAta,
        string $toAta,
        string $mintPubkey
    ): string {
        // Collect all unique account keys
        $accountMetas = [];

        // Fee payer is always first (signer + writable)
        $feePayerB58 = $this->base58Encode($feePayer);
        $accountMetas[$feePayerB58] = [
            'pubkey' => $feePayer,
            'is_signer' => true,
            'is_writable' => true,
        ];

        // Collect accounts from all instructions
        foreach ($instructions as $ix) {
            foreach ($ix['accounts'] as $acc) {
                $key = $this->base58Encode($acc['pubkey']);
                if (isset($accountMetas[$key])) {
                    // Merge flags (promote to signer/writable if any instruction requires it)
                    $accountMetas[$key]['is_signer'] = $accountMetas[$key]['is_signer'] || $acc['is_signer'];
                    $accountMetas[$key]['is_writable'] = $accountMetas[$key]['is_writable'] || $acc['is_writable'];
                } else {
                    $accountMetas[$key] = [
                        'pubkey' => $acc['pubkey'],
                        'is_signer' => $acc['is_signer'],
                        'is_writable' => $acc['is_writable'],
                    ];
                }
            }

            // Add program ID as non-signer, non-writable
            $progKey = is_string($ix['program_id']) && strlen($ix['program_id']) > 32
                ? $ix['program_id']
                : $this->base58Encode($ix['program_id']);

            $progPubkey = strlen($ix['program_id']) === 32 ? $ix['program_id'] : $this->base58Decode($ix['program_id']);

            if (!isset($accountMetas[$progKey])) {
                $accountMetas[$progKey] = [
                    'pubkey' => $progPubkey,
                    'is_signer' => false,
                    'is_writable' => false,
                ];
            }
        }

        // Add Sysvar Rent if creating ATA
        if ($needsCreateAta) {
            $rentKey = self::SYSVAR_RENT;
            if (!isset($accountMetas[$rentKey])) {
                $accountMetas[$rentKey] = [
                    'pubkey' => $this->base58Decode($rentKey),
                    'is_signer' => false,
                    'is_writable' => false,
                ];
            }
        }

        // Sort accounts: signers+writable first, then signers+readonly, then non-signer+writable, then non-signer+readonly
        // Fee payer must remain at index 0
        $feePayerEntry = $accountMetas[$feePayerB58];
        unset($accountMetas[$feePayerB58]);

        $sorted = array_values($accountMetas);
        usort($sorted, function ($a, $b) {
            if ($a['is_signer'] !== $b['is_signer']) return $b['is_signer'] <=> $a['is_signer'];
            if ($a['is_writable'] !== $b['is_writable']) return $b['is_writable'] <=> $a['is_writable'];
            return 0;
        });

        // Fee payer at index 0
        array_unshift($sorted, $feePayerEntry);

        // Build account index lookup
        $accountIndex = [];
        $accountKeys = [];
        foreach ($sorted as $i => $meta) {
            $key = $this->base58Encode($meta['pubkey']);
            $accountIndex[$key] = $i;
            $accountKeys[] = $meta['pubkey'];
        }

        // Count signers and read-only accounts
        $numRequiredSignatures = 0;
        $numReadonlySignedAccounts = 0;
        $numReadonlyUnsignedAccounts = 0;

        foreach ($sorted as $meta) {
            if ($meta['is_signer']) {
                $numRequiredSignatures++;
                if (!$meta['is_writable']) {
                    $numReadonlySignedAccounts++;
                }
            } else {
                if (!$meta['is_writable']) {
                    $numReadonlyUnsignedAccounts++;
                }
            }
        }

        // Build message header
        $header = chr($numRequiredSignatures)
                . chr($numReadonlySignedAccounts)
                . chr($numReadonlyUnsignedAccounts);

        // Account addresses (compact array)
        $accountAddressesSection = $this->encodeCompactU16(count($accountKeys));
        foreach ($accountKeys as $key) {
            $accountAddressesSection .= $key; // 32 bytes each
        }

        // Recent blockhash (32 bytes)
        $blockhashSection = $recentBlockhash;

        // Instructions (compact array)
        $instructionsSection = $this->encodeCompactU16(count($instructions));
        foreach ($instructions as $ix) {
            $progPubkey = strlen($ix['program_id']) === 32 ? $ix['program_id'] : $this->base58Decode($ix['program_id']);
            $progB58 = $this->base58Encode($progPubkey);
            $progIdx = $accountIndex[$progB58];

            // Program ID index
            $instructionsSection .= chr($progIdx);

            // Account indexes (compact array)
            $instructionsSection .= $this->encodeCompactU16(count($ix['accounts']));
            foreach ($ix['accounts'] as $acc) {
                $accB58 = $this->base58Encode($acc['pubkey']);
                $instructionsSection .= chr($accountIndex[$accB58]);
            }

            // Data (compact array of bytes)
            $instructionsSection .= $this->encodeCompactU16(strlen($ix['data']));
            $instructionsSection .= $ix['data'];
        }

        return $header . $accountAddressesSection . $blockhashSection . $instructionsSection;
    }

    /**
     * Build the full signed transaction wire format.
     * [compact_u16: num_signatures] [64 bytes per signature] [message bytes]
     */
    protected function buildSignedTransaction(array|string $signatures, string $message): string
    {
        // Support both single signature (string) and multiple signatures (array)
        if (is_string($signatures)) {
            $signatures = [$signatures];
        }

        $tx = $this->encodeCompactU16(count($signatures));
        foreach ($signatures as $sig) {
            $tx .= $sig; // 64 bytes each
        }
        $tx .= $message;

        return $tx;
    }

    /**
     * Encode a u16 value in Solana's compact format.
     */
    protected function encodeCompactU16(int $value): string
    {
        if ($value < 0x80) {
            return chr($value);
        } elseif ($value < 0x4000) {
            return chr(($value & 0x7F) | 0x80) . chr($value >> 7);
        } else {
            return chr(($value & 0x7F) | 0x80)
                 . chr((($value >> 7) & 0x7F) | 0x80)
                 . chr($value >> 14);
        }
    }

    /**
     * Mark transaction as failed.
     */
    protected function fail(CryptoWalletTransaction $txRecord, string $error): array
    {
        Log::error('SolanaTransfer: failed', ['error' => $error, 'tx_id' => $txRecord->id]);

        $txRecord->update([
            'transaction_status' => 'failed',
            'memo_note' => ($txRecord->memo_note ? $txRecord->memo_note . ' | ' : '') . 'Error: ' . $error,
            'datetime_updated' => now(),
        ]);

        return [
            'success' => false,
            'signature' => null,
            'error' => $error,
        ];
    }

    // ─── Base58 Encoding/Decoding (pure PHP, no GMP required) ──────────

    protected static string $base58Alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /**
     * Encode bytes to base58 (pure PHP big-integer arithmetic).
     */
    public function base58Encode(string $bytes): string
    {
        $alphabet = self::$base58Alphabet;

        // Count leading zero bytes
        $leadingZeros = 0;
        $len = strlen($bytes);
        for ($i = 0; $i < $len; $i++) {
            if (ord($bytes[$i]) === 0) {
                $leadingZeros++;
            } else {
                break;
            }
        }

        // If all bytes are zero, just return leading 1s
        if ($leadingZeros === $len) {
            return str_repeat('1', $leadingZeros);
        }

        // Convert byte string to array of integers for base conversion
        // Work with an array of base-256 digits
        $digits = array_values(unpack('C*', $bytes));

        // Convert base-256 to base-58
        $base58Digits = [];
        while (count($digits) > 0) {
            $remainder = 0;
            $newDigits = [];
            foreach ($digits as $digit) {
                $accumulator = $digit + $remainder * 256;
                $newDigit = intdiv($accumulator, 58);
                $remainder = $accumulator % 58;

                if (count($newDigits) > 0 || $newDigit > 0) {
                    $newDigits[] = $newDigit;
                }
            }
            $base58Digits[] = $remainder;
            $digits = $newDigits;
        }

        // Build string from base58 digits (they are in reverse order)
        $encoded = '';
        foreach (array_reverse($base58Digits) as $d) {
            $encoded .= $alphabet[$d];
        }

        return str_repeat('1', $leadingZeros) . $encoded;
    }

    /**
     * Decode base58 string to bytes (pure PHP big-integer arithmetic).
     */
    public function base58Decode(string $base58): ?string
    {
        $alphabet = self::$base58Alphabet;

        if ($base58 === '') {
            return '';
        }

        // Count leading '1's (zero bytes)
        $leadingOnes = 0;
        $len = strlen($base58);
        for ($i = 0; $i < $len; $i++) {
            if ($base58[$i] === '1') {
                $leadingOnes++;
            } else {
                break;
            }
        }

        // Convert base58 characters to an array of indices
        $base58Digits = [];
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($alphabet, $base58[$i]);
            if ($pos === false) {
                return null; // Invalid character
            }
            $base58Digits[] = $pos;
        }

        // Convert base-58 to base-256
        $byteDigits = [];
        foreach ($base58Digits as $b58digit) {
            $carry = $b58digit;
            for ($j = count($byteDigits) - 1; $j >= 0; $j--) {
                $carry += $byteDigits[$j] * 58;
                $byteDigits[$j] = $carry % 256;
                $carry = intdiv($carry, 256);
            }
            while ($carry > 0) {
                array_unshift($byteDigits, $carry % 256);
                $carry = intdiv($carry, 256);
            }
        }

        $bytes = str_repeat("\0", $leadingOnes);
        foreach ($byteDigits as $b) {
            $bytes .= chr($b);
        }

        return $bytes;
    }
}
