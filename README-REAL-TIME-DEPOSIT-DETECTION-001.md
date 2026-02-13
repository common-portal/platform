# Real-Time Deposit Detection & Email Notifications

## Objective
Detect when supported stablecoins arrive at any CLIENT or ADMIN wallet on Solana, record the incoming transaction, and email the account's members.

---

## Supported Stablecoins

The system uses a **mint registry** (`SolanaTransferService::MINTS`) so adding a new stablecoin is a single-line change. Detection works for ANY SPL token whose mint address is in the registry.

### Live on Solana Now

| Token | Issuer | Peg | Solana Mint Address | Decimals |
|-------|--------|-----|---------------------|----------|
| **USDT** | Tether | USD | `Es9vMFrzaCERmKfreVB4bFcGpNRB2Ggz7tKxRPVGy3GK` | 6 |
| **USDC** | Circle | USD | `EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v` | 6 |
| **EURC** | Circle | EUR | `HzwqbKZw8HxMN6bF2yFZNrht3c2iXXzpKcFu7uBEDKtr` | 6 |

### Planned (not yet on Solana — add mint address when available)

| Token | Issuer | Peg | Status |
|-------|--------|-----|--------|
| **MXNT** | Tether | MXN | Live on Ethereum + Polygon. Not yet on Solana. |
| **CNHT** | Tether | CNH | Live on Ethereum + Tron. Not yet on Solana. |
| **GBPC** | TBD | GBP | Does not exist yet from Circle or Tether. |
| **AUDC** | TBD | AUD | Does not exist yet from Circle or Tether. |

### Mint Registry Design (in `SolanaTransferService`)
```php
const MINTS = [
    // Tether family
    'USDT' => 'Es9vMFrzaCERmKfreVB4bFcGpNRB2Ggz7tKxRPVGy3GK',
    // 'MXNT' => '...', // Add when Tether launches on Solana
    // 'CNHT' => '...', // Add when Tether launches on Solana

    // Circle family
    'USDC' => 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v',
    'EURC' => 'HzwqbKZw8HxMN6bF2yFZNrht3c2iXXzpKcFu7uBEDKtr',
    // 'GBPC' => '...', // Add when Circle launches on Solana
    // 'AUDC' => '...', // Add when Circle launches on Solana
];

// Reverse lookup: mint address → currency symbol (used by deposit detection)
const MINT_TO_CURRENCY = [
    'Es9vMFrzaCERmKfreVB4bFcGpNRB2Ggz7tKxRPVGy3GK' => 'USDT',
    'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v' => 'USDC',
    'HzwqbKZw8HxMN6bF2yFZNrht3c2iXXzpKcFu7uBEDKtr' => 'EURC',
];

// Decimals per token (all current stablecoins use 6)
const TOKEN_DECIMALS = [
    'USDT' => 6, 'USDC' => 6, 'EURC' => 6,
];
```

**To add a new stablecoin:** Add its mint address to `MINTS`, add the reverse entry to `MINT_TO_CURRENCY`, and add its decimals. That's it — deposit detection, balance fetching, and transfers will all work automatically.

---

## Architecture Overview

```
[Solana Blockchain]
        |
        v
[Laravel Scheduled Command]  <-- runs every 60 seconds
  "php artisan wallets:check-deposits"
        |
        v
[For each CLIENT/ADMIN wallet:]
  1. Call Solana RPC: getSignaturesForAddress (new txs since last check)
  2. For each new signature: getTransaction (get amount, sender, token)
  3. Match mint address against MINT_TO_CURRENCY registry
  4. Compare against existing CryptoWalletTransaction records
  5. If NEW incoming deposit found:
        |
        +---> Create CryptoWalletTransaction (direction: 'incoming')
        +---> Send email to all account members via PlatformMailerService
        +---> Log the detection
```

---

## Components to Build

### 1. Artisan Command: `CheckWalletDeposits`
**File:** `src/app/Console/Commands/CheckWalletDeposits.php`

**What it does:**
- Queries all active CLIENT and ADMIN wallets (skip GAS wallets)
- For each wallet, calls `SolanaRpcService::getSignaturesForAddress()` to get recent transactions
- Filters for transactions newer than the wallet's `last_deposit_check_at` timestamp
- For each new transaction signature, calls `SolanaRpcService::getTransaction()` to get details
- Identifies incoming SPL token transfers by matching the mint against `MINT_TO_CURRENCY`
- Creates a `CryptoWalletTransaction` record with `direction = 'incoming'`
- Sends deposit notification email to all members of the wallet's account
- Updates the wallet's `last_deposit_check_at` timestamp

**Rate limit awareness:**
- 250ms delay between RPC calls (same pattern as wallet list balance fetching)
- Process wallets sequentially, not in parallel
- Skip wallets that were checked less than 30 seconds ago

### 2. Database Changes

**Migration:** Add `last_deposit_check_at` column to `crypto_wallets` table
```php
$table->timestamp('last_deposit_check_at')->nullable();
```

This tracks when we last checked each wallet for new deposits, so we only query for transactions after this point.

### 3. Email Method: `sendCryptoDepositEmail()`
**File:** `src/app/Services/PlatformMailerService.php`

**New method** (follows the existing `sendPaymentReceivedEmail` pattern):
```
sendCryptoDepositEmail(
    recipientEmail,
    recipientName,
    accountName,
    amount,          // e.g. "100.50"
    currency,        // e.g. "USDT", "EURC", etc.
    network,         // e.g. "Solana"
    walletName,      // e.g. "ADMIN USDT"
    walletAddress,   // truncated, e.g. "3q29FY...66RL"
    senderAddress,   // truncated
    txSignature,     // Solana tx signature
    explorerUrl,     // link to Solscan
    transactionDateTime
)
```

**Subject line:** `Crypto Deposit Received: 100.50 USDT`

**Email body:** Same dark theme as existing emails, showing:
- Amount + currency (large, green)
- Wallet name + truncated address
- Sender address (truncated)
- Network (Solana)
- Date/time
- Link to Solscan explorer
- Footer: "Log in to view full details"

### 4. Scheduler Registration
**File:** `src/app/Console/Kernel.php` (or `routes/console.php` depending on Laravel version)

```php
$schedule->command('wallets:check-deposits')->everyMinute()->withoutOverlapping();
```

### 5. SolanaRpcService Additions
**File:** `src/app/Services/SolanaRpcService.php`

Methods already exist:
- `getSignaturesForAddress($pubkey, $limit)` — returns recent tx signatures
- `getTransaction($signature)` — needs to be verified/added

The `getTransaction` call returns full transaction details including:
- Pre/post token balances (to calculate incoming amount)
- The sender address
- Block time (timestamp)
- Confirmation status

---

## Detection Logic (Detailed)

### How to identify an incoming stablecoin deposit:

```
1. getSignaturesForAddress(walletAddress, limit=20)
   → Returns array of {signature, slot, blockTime, err}

2. Filter: skip signatures where err is not null (failed txs)

3. For each valid signature:
   a. Check if CryptoWalletTransaction with this solana_tx_signature already exists
      → If yes, skip (already recorded)
   
   b. getTransaction(signature, {encoding: 'jsonParsed', maxSupportedTransactionVersion: 0})
      → Returns full parsed transaction
   
   c. Look at postTokenBalances vs preTokenBalances for our wallet address
      → Find entries where mint is in MINT_TO_CURRENCY registry
      → Calculate: postAmount - preAmount = depositAmount
      → If depositAmount > 0, this is an incoming deposit
      → Resolve currency symbol via MINT_TO_CURRENCY[mint]
   
   d. Extract sender from the transaction's instructions
      → The 'source' of the SPL transfer instruction
   
   e. Create CryptoWalletTransaction:
      - wallet_id: the wallet's ID
      - account_hash: wallet's account_hash
      - direction: 'incoming'
      - amount: depositAmount (human-readable, e.g. 100.50)
      - currency: resolved from MINT_TO_CURRENCY (e.g. USDT, EURC)
      - from_wallet_address: sender
      - to_wallet_address: our wallet address
      - solana_tx_signature: the signature
      - transaction_status: 'confirmed'
      - datetime_created: Carbon::createFromTimestamp(blockTime)
      - datetime_confirmed: Carbon::createFromTimestamp(blockTime)
```

### Who to email:

```
1. Look up TenantAccount by wallet's account_hash
2. Get all TenantAccountMembership records for that account
3. For each membership, get the PlatformMember
4. Send email to each member's login_email_address
```

---

## Flow Diagram

```
Every 60 seconds:
┌──────────────────────────────────────────────────────┐
│  wallets:check-deposits                              │
│                                                      │
│  1. Get all active CLIENT/ADMIN wallets              │
│  2. For each wallet:                                 │
│     ├─ getSignaturesForAddress (last 20 txs)         │
│     ├─ Filter new signatures (not in DB)             │
│     ├─ For each new signature:                       │
│     │   ├─ getTransaction (parsed)                   │
│     │   ├─ Match mint against MINT_TO_CURRENCY       │
│     │   ├─ Check if incoming (postBal > preBal)      │
│     │   ├─ Record CryptoWalletTransaction            │
│     │   └─ Email all account members                 │
│     └─ Update last_deposit_check_at                  │
│                                                      │
│  Supported: USDT, USDC, EURC (+ future stablecoins) │
│  Rate limits: 250ms between RPC calls                │
│  Skip GAS wallets (SOL only, no stablecoin deposits) │
└──────────────────────────────────────────────────────┘
```

---

## Edge Cases & Considerations

| Scenario | Handling |
|----------|----------|
| Duplicate detection | Check `solana_tx_signature` uniqueness before inserting |
| Failed transactions | Skip signatures where `err` is not null |
| RPC rate limiting (429) | 250ms delays + retry with backoff |
| Wallet receives SOL (not a stablecoin) | Ignore — only track SPL tokens in MINT_TO_CURRENCY |
| Wallet receives unknown SPL token | Ignore — mint not in registry |
| Very large deposits | No special handling — same flow |
| Multiple deposits in same block | Each has a unique signature — processed individually |
| Multi-token deposit (USDT + USDC in one tx) | Detected as separate balance changes, recorded individually |
| Command overlaps (still running) | Use `withoutOverlapping()` on the scheduler |
| First run (no last_deposit_check_at) | Check last 20 transactions on first run |
| Account has no members | Skip email (log warning) |
| RPC node is down | Catch exception, log, retry next cycle |
| New stablecoin added to registry | Immediately detected on next poll cycle |

---

## Files to Create/Modify

| File | Action | Description |
|------|--------|-------------|
| `app/Console/Commands/CheckWalletDeposits.php` | **CREATE** | Artisan command for deposit polling |
| `database/migrations/xxxx_add_last_deposit_check_to_crypto_wallets.php` | **CREATE** | Add `last_deposit_check_at` column |
| `app/Services/PlatformMailerService.php` | **MODIFY** | Add `sendCryptoDepositEmail()` method + HTML template |
| `app/Services/SolanaRpcService.php` | **MODIFY** | Add/verify `getTransaction()` method |
| `app/Services/SolanaTransferService.php` | **MODIFY** | Add `MINT_TO_CURRENCY`, `TOKEN_DECIMALS` per-token, add `EURC` to `MINTS` |
| `app/Console/Kernel.php` or `routes/console.php` | **MODIFY** | Register scheduled command |
| `app/Models/CryptoWallet.php` | **MODIFY** | Add `last_deposit_check_at` to fillable + cast |
| `app/Http/Controllers/AdminController.php` | **MODIFY** | Add EURC (and future tokens) to currency validation |
| `resources/views/pages/administrator/wallets.blade.php` | **MODIFY** | Add EURC button to currency selector |

---

## Estimated Implementation Effort

| Component | Effort |
|-----------|--------|
| Migration + model update | 10 min |
| SolanaTransferService mint registry expansion (EURC + MINT_TO_CURRENCY) | 15 min |
| SolanaRpcService `getTransaction` | 15 min |
| CheckWalletDeposits command (core logic) | 45 min |
| PlatformMailerService crypto deposit email | 20 min |
| Scheduler registration | 5 min |
| Admin UI: add EURC currency button | 10 min |
| Controller validation updates | 5 min |
| Testing + debugging | 30 min |
| **Total** | **~2.5 hours** |

---

## Future Enhancements (Not in Scope Now)

- **WebSocket-based detection** — instant instead of 60-second polling (requires persistent process)
- **Helius/QuickNode webhooks** — third-party push notifications (most reliable for production scale)
- **Deposit auto-settlement** — automatically forward received funds to a settlement wallet
- **SMS notifications** — in addition to email
- **In-app notifications** — real-time UI badge/toast when deposit arrives
- **Configurable notification preferences** — let members opt in/out of deposit emails
- **MXNT, CNHT, GBPC, AUDC support** — add mint addresses when these launch on Solana

---

## Status: PLANNING
**Ready to implement when approved.**
