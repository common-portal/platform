# XRAMP — Crypto Exchange Traceability & Wallet Management Framework (002)

> **Predecessor:** `README-CYRPTO-EXCHANGE-TRACEABILITY-001.md` — established that Option 2 (Tether delivers USDT to xramp master wallet, xramp forwards to client wallet) is the correct service model for full audit trail and dispute protection.

> **Date:** February 11, 2026

---

## 1. Recap: The Chosen Service Model (Option 2)

```
Client → fiat wire → xramp IBAN
                         ↓
                   xramp sends fiat → tether.to (or circle.com)
                         ↓
                   Tether/Circle mints USDT/USDC → xramp MASTER wallet (Solana)
                         ↓
                   xramp forwards USDT/USDC → client's DESIGNATED wallet (Solana)
                         ↓
                   Client can further transfer → any 3rd-party wallet
```

**Why Option 2:** xramp controls the master receiving wallet, creating two provable on-chain transaction hashes:
1. **Inbound tx** — Tether/Circle → xramp master wallet (proof of receipt from issuer)
2. **Outbound tx** — xramp master wallet → client wallet (proof of delivery to client)

Both are permanently recorded on the Solana blockchain and independently verifiable.

---

## 2. Stablecoin Issuers Supported

| Issuer | Token | Website | Minting Model |
|--------|-------|---------|---------------|
| **Tether Ltd** | USDT | tether.to | Wire fiat → Tether mints USDT → designated wallet |
| **Circle** | USDC | circle.com | Wire fiat → Circle mints USDC → designated wallet |

Both support **Solana (SPL tokens)** as a delivery network. xramp should support both USDT and USDC to give clients a choice.

---

## 3. Preferred Network: Solana

| Property | Value |
|----------|-------|
| **Network** | Solana |
| **Token standard** | SPL Token |
| **USDT contract** | Es9vMFrzaCERmKfreVB4bFcGpNRB2Ggz... (Tether's SPL mint) |
| **USDC contract** | EPjFWdd5AufqSSqeM2qN1xzybapC8G4w... (Circle's SPL mint) |
| **Avg tx fee** | ~$0.001 |
| **Confirmation time** | ~400ms (single confirmation) |
| **Tracking tools** | Solana Explorer, Solscan, Solana RPC APIs |

---

## 4. WalletIDs.net as Wallet Creation Service

### 4.1 What WalletIDs.net Does

WalletIDs.net (https://walletids.net/api-documentation) is a **Wallet Factory + Monitoring Service**:

- **Creates** HD (Hierarchical Deterministic) wallets and standalone wallets
- **Derives** unique child addresses from master HD wallets (BIP44: `m/44'/501'/0'/0/{index}` for Solana)
- **Monitors** wallet balances with configurable polling intervals
- **Sends webhooks** on `payment_detected` and `balance_changed` events
- **Key Recovery** — retrieve private keys/mnemonics for wallets (rate-limited, audited)
- **List Networks** and **List Currencies** endpoints to discover supported chains/tokens

### 4.2 Does WalletIDs.net Support USDT and USDC?

**Yes, via Solana.** WalletIDs.net supports Solana as a network (BIP44 path `m/44'/501'/0'/0/{index}`). When creating a wallet, you specify `network` and `currency` parameters. The **List Currencies** endpoint (`GET /networks/{network}/currencies`) returns available currencies per network. Since Solana natively hosts both USDT and USDC as SPL tokens, WalletIDs.net can create and monitor wallets for both.

Additionally, on Solana a single wallet address can hold **multiple SPL tokens** simultaneously (USDT, USDC, SOL, etc.), each via an Associated Token Account (ATA).

### 4.3 What WalletIDs.net Does NOT Do

> **Critical architectural note:** WalletIDs.net is **non-custodial**. It does NOT:
> - Execute/sign transactions
> - Sweep funds
> - Manage gas/SOL fees
> - Custody funds

**Implication for xramp:** The "Send" functionality (transferring USDT/USDC from a client wallet to a 3rd-party wallet) must be implemented by xramp directly using:
1. **WalletIDs.net Key Recovery API** — to retrieve the wallet's private key
2. **Solana Web3.js / @solana/spl-token SDK** — to sign and broadcast the SPL token transfer transaction via Solana RPC

This is a backend service that xramp must build and operate.

### 4.4 Webhook Integration

WalletIDs.net sends webhook events that xramp should consume for real-time tracking:

| Event | Trigger | Useful For |
|-------|---------|------------|
| `payment_detected` | Balance increases (incoming payment) | Auto-detecting when Tether/Circle delivers to master wallet, or when funds arrive in client wallet |
| `balance_changed` | Balance changes in any direction | Tracking outgoing transfers, reconciliation |

**Webhook payload fields:** `wallet_address`, `currency`, `network`, `amount`, `tx_hash`, `confirmations`, `from_address`, `external_id`, `amount_usd_estimate`

---

## 5. Wallet Architecture: Master + Client Wallets

### 5.1 Wallet Types

| Wallet Type | Owner | Purpose | Created By |
|-------------|-------|---------|------------|
| **Master Recipient Wallet** | xramp (platform) | Common receiving wallet for aggregated Tether/Circle exchanges | Admin via `/administrator/wallets` |
| **Client Designated Wallet** | xramp (on behalf of client account) | Per-client settlement wallet for sub-distribution and client-initiated transfers | Admin via `/administrator/wallets`, assigned to a tenant account |

### 5.2 Fund Flow: Master → Client → 3rd Party

```
tether.to / circle.com
        ↓ mints USDT/USDC
  ┌─────────────────────────────────┐
  │  XRAMP MASTER RECIPIENT WALLET  │  ← Aggregated receiving point
  │  (Solana, owned by xramp)       │
  └────────┬───────┬───────┬────────┘
           ↓       ↓       ↓          Sub-distribution (admin-initiated)
     ┌─────────┐ ┌─────────┐ ┌─────────┐
     │Client A │ │Client B │ │Client C │  ← Per-client designated wallets
     │ Wallet  │ │ Wallet  │ │ Wallet  │
     └────┬────┘ └────┬────┘ └────┬────┘
          ↓            ↓           ↓       Client-initiated transfers
     3rd-party    3rd-party    3rd-party
      wallet       wallet       wallet
```

### 5.3 Transaction Recording

**Every movement is recorded** — both incoming and outgoing:

| Direction | From | To | Initiated By | Recorded In |
|-----------|------|----|-------------|-------------|
| **Inbound** | Tether/Circle | Master Wallet | External (webhook-detected) | Wallet transactions |
| **Sub-distribution** | Master Wallet | Client Wallet | Admin | Wallet transactions |
| **Outbound** | Client Wallet | 3rd-party wallet | Client account user | Wallet transactions |
| **Inbound (direct)** | Any external wallet | Client Wallet | External (webhook-detected) | Wallet transactions |

---

## 6. Admin Panel: `/administrator/wallets`

### 6.1 Admin Panel Tile

Add a **"Wallets"** tile to the Administrator Panel (`/administrator`) grid:

```
┌──────────────┐
│   Wallets    │
│ Manage crypto│
│   wallets    │
└──────────────┘
```

Route: `admin.wallets` → `/administrator/wallets`

### 6.2 Admin Wallets Page Features

The admin wallets page allows the administrator to:

1. **Create Master Recipient Wallet** — a platform-level wallet not tied to any client account
   - Network: Solana
   - Currency: USDT or USDC
   - Friendly name (e.g., "XRAMP Master USDT Wallet")
   - Created via WalletIDs.net API (`POST /wallets` with `network=solana`, `currency=usdt`)

2. **Create/Assign Client Wallet** — tied to a specific tenant account
   - Select account (via impersonation or dropdown, same pattern as IBANs page)
   - Network: Solana
   - Currency: USDT or USDC
   - Friendly name
   - Can be an HD-derived child of the master wallet (via `POST /wallets/{hash}/derive`) or a standalone wallet

3. **View All Wallets** — list with filters by account, currency, status
   - Real-time balance display (via WalletIDs.net `GET /wallets/{hash}/balance`)
   - Status toggle: ACTIVE / PAUSED
   - Soft delete

### 6.3 Wallet Form Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `wallet_friendly_name` | VARCHAR(255) | Yes | Human-readable name |
| `wallet_currency` | ENUM | Yes | `USDT`, `USDC` |
| `wallet_network` | ENUM | Yes | `solana` (default, extensible) |
| `wallet_address` | VARCHAR(255) | Yes | Solana public address (from WalletIDs.net) |
| `walletids_wallet_hash` | VARCHAR(64) | Yes | WalletIDs.net wallet reference hash |
| `wallet_type` | ENUM | Yes | `master_recipient`, `client_designated` |
| `account_hash` | VARCHAR(32) | Conditional | Links to `tenant_accounts.record_unique_identifier` (required for `client_designated`) |
| `is_active` | BOOLEAN | Yes | ACTIVE / PAUSED |
| `is_deleted` | BOOLEAN | Yes | Soft delete |

---

## 7. Sidebar: "Wallets" Menu Item

### 7.1 Sidebar Placement

Add **"Wallets"** to the Account Menu Items section in the sidebar, between Transactions and Billing (or after IBANs — your preference).

### 7.2 Permission Slug

| Slug | Controls |
|------|----------|
| `can_view_wallets` | Wallets page visibility and access |

This follows the existing pattern in `ViewComposerServiceProvider.php` → `menuItemEnabled` array and `hasUserPermission()` checks.

### 7.3 Menu Toggle

Add `can_view_wallets` to the admin Menu Items toggle page so platform administrators can enable/disable the Wallets feature globally.

---

## 8. Client Wallet Page: `/modules/wallets`

### 8.1 Page Layout

The client-facing wallet page shows:

1. **Wallet Summary Cards** — one card per assigned wallet showing:
   - Friendly name
   - Currency (USDT / USDC)
   - Network (Solana)
   - Wallet address (truncated, copy button)
   - Real-time balance (via WalletIDs.net API)
   - **[Send]** button

2. **Transaction History Table** — all wallet transactions for this account

### 8.2 Transaction History Table Columns

| Column | Description |
|--------|-------------|
| **Datetime Created** | When the transaction was recorded |
| **Wallet** | Friendly name of the wallet involved |
| **Direction** | `INCOMING` or `OUTGOING` (with arrow icon) |
| **Amount** | Amount of USDT/USDC transferred |
| **Status** | `confirmed`, `pending`, `failed` |

### 8.3 Expandable Transaction Row (Click to Expand)

When a transaction row is clicked, it expands to show a **step-by-step tracking detail panel** sourced from Solana RPC APIs:

```
┌─────────────────────────────────────────────────────────────────────┐
│  Transaction Detail                                                  │
│                                                                      │
│  ● Step 1: Transaction Submitted                                     │
│    Datetime: 2026-02-11 08:15:32 UTC                                │
│    Tx Signature: 5Ht7Kx...mN3qR                                     │
│    From: 7xKp...mN3q (xramp Master Wallet)                         │
│    To: 9bRt...kP2w (Client A Wallet)                                │
│    Amount: 50,000.00 USDT                                           │
│                                                                      │
│  ● Step 2: Transaction Confirmed                                     │
│    Block: #245,892,103                                               │
│    Confirmations: 32 (finalized)                                     │
│    Slot: 245892103                                                   │
│    Fee: 0.000005 SOL                                                │
│                                                                      │
│  ● Step 3: Delivery Verified                                         │
│    Recipient balance updated: +50,000.00 USDT                       │
│    Verification source: Solana RPC getTransaction                   │
│                                                                      │
│  [View on Solana Explorer ↗]  [View on Solscan ↗]                  │
└─────────────────────────────────────────────────────────────────────┘
```

### 8.4 "Send" Button Flow

When a client clicks **[Send]** on a wallet:

1. **Modal/form opens** with fields:
   - Destination wallet address (required)
   - Amount (required, validated against available balance)
   - Currency (pre-filled, read-only — USDT or USDC)
   - Memo/note (optional, stored locally — not on-chain)

2. **Confirmation step** — summary of transfer details with [Confirm Send] button

3. **Backend execution:**
   - xramp retrieves private key via WalletIDs.net Key Recovery API
   - xramp signs the SPL token transfer using Solana Web3.js
   - xramp broadcasts via Solana RPC (`sendTransaction`)
   - Transaction hash stored in xramp database
   - Status tracked via Solana RPC polling or WalletIDs.net webhook (`balance_changed`)

4. **Transaction recorded** with status progression: `submitted` → `confirmed` → `finalized`

---

## 9. Database Schema: New Tables

### 9.1 Table: `crypto_wallets`

```sql
CREATE TABLE crypto_wallets (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    account_hash                    VARCHAR(32) NULL,          -- NULL for master wallets, links to tenant_accounts.record_unique_identifier for client wallets
    wallet_friendly_name            VARCHAR(255) NOT NULL,
    wallet_type                     VARCHAR(30) NOT NULL,      -- 'master_recipient' or 'client_designated'
    wallet_currency                 VARCHAR(10) NOT NULL,      -- 'USDT', 'USDC'
    wallet_network                  VARCHAR(30) NOT NULL DEFAULT 'solana',
    wallet_address                  VARCHAR(255) NOT NULL,     -- Solana public key
    walletids_wallet_hash           VARCHAR(64) NOT NULL,      -- WalletIDs.net reference
    walletids_external_id           VARCHAR(255) NULL,         -- Optional external ID for WalletIDs.net lookup
    creator_member_hash             VARCHAR(32) NOT NULL,      -- Links to platform_members.record_unique_identifier
    is_active                       BOOLEAN NOT NULL DEFAULT TRUE,
    is_deleted                      BOOLEAN NOT NULL DEFAULT FALSE,
    datetime_created                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_updated                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_crypto_wallets_account_hash ON crypto_wallets(account_hash);
CREATE INDEX idx_crypto_wallets_wallet_type ON crypto_wallets(wallet_type);
CREATE INDEX idx_crypto_wallets_wallet_address ON crypto_wallets(wallet_address);
```

### 9.2 Table: `crypto_wallet_transactions`

```sql
CREATE TABLE crypto_wallet_transactions (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    wallet_id                       BIGINT NOT NULL REFERENCES crypto_wallets(id),
    account_hash                    VARCHAR(32) NULL,          -- Links to tenant_accounts.record_unique_identifier
    direction                       VARCHAR(10) NOT NULL,      -- 'incoming' or 'outgoing'
    currency                        VARCHAR(10) NOT NULL,      -- 'USDT', 'USDC'
    network                         VARCHAR(30) NOT NULL DEFAULT 'solana',
    amount                          DECIMAL(20,6) NOT NULL,
    from_wallet_address             VARCHAR(255) NOT NULL,
    to_wallet_address               VARCHAR(255) NOT NULL,
    solana_tx_signature             VARCHAR(128) NULL,         -- Solana transaction hash
    solana_block_slot               BIGINT NULL,
    solana_confirmations            INTEGER NULL,
    solana_fee_lamports             BIGINT NULL,               -- Transaction fee in lamports
    transaction_status              VARCHAR(20) NOT NULL DEFAULT 'submitted',  -- 'submitted', 'confirmed', 'finalized', 'failed'
    memo_note                       TEXT NULL,                 -- Internal note (not on-chain)
    initiated_by_member_hash        VARCHAR(32) NULL,          -- Who triggered the send (NULL for webhook-detected inbound)
    webhook_detected                BOOLEAN NOT NULL DEFAULT FALSE,  -- Was this auto-detected via WalletIDs.net webhook?
    raw_solana_response             JSONB NULL,                -- Full Solana RPC response for audit
    datetime_submitted              TIMESTAMP NULL,
    datetime_confirmed              TIMESTAMP NULL,
    datetime_finalized              TIMESTAMP NULL,
    datetime_created                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_updated                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_cwt_wallet_id ON crypto_wallet_transactions(wallet_id);
CREATE INDEX idx_cwt_account_hash ON crypto_wallet_transactions(account_hash);
CREATE INDEX idx_cwt_direction ON crypto_wallet_transactions(direction);
CREATE INDEX idx_cwt_solana_tx ON crypto_wallet_transactions(solana_tx_signature);
CREATE INDEX idx_cwt_status ON crypto_wallet_transactions(transaction_status);
```

---

## 10. Integration with Existing Transaction Model

The existing 3-phase transaction model in `Transaction.php` (fiat on-ramp flow) connects to the wallet system:

| Existing Phase | What Happens | Wallet System Link |
|---------------|--------------|-------------------|
| **Phase 1 — Received** | Client wires fiat to xramp IBAN | No wallet involvement yet |
| **Phase 2 — Exchanged** | Tether/Circle mints USDT/USDC → xramp master wallet | `crypto_wallet_transactions` records INCOMING to master wallet. Solana tx hash stored in `Transaction.solana_inbound_tx_signature` (new field) |
| **Phase 3 — Settled** | Admin sub-distributes from master wallet → client wallet | `crypto_wallet_transactions` records OUTGOING from master wallet AND INCOMING to client wallet. Solana tx hash stored in `Transaction.solana_outbound_tx_signature` (new field) |

### New Fields for `transactions` Table

```sql
ALTER TABLE transactions ADD COLUMN solana_inbound_tx_signature VARCHAR(128) NULL;
ALTER TABLE transactions ADD COLUMN solana_outbound_tx_signature VARCHAR(128) NULL;
ALTER TABLE transactions ADD COLUMN master_wallet_id BIGINT NULL REFERENCES crypto_wallets(id);
ALTER TABLE transactions ADD COLUMN client_wallet_id BIGINT NULL REFERENCES crypto_wallets(id);
```

---

## 11. Solana RPC Integration for Tracking

### 11.1 Key RPC Methods

| Method | Purpose | When Used |
|--------|---------|-----------|
| `getTransaction(sig)` | Full transaction detail (from, to, amount, status, block, fee) | Expandable row detail view |
| `getSignatureStatuses([sig])` | Confirmation status check | Status polling after send |
| `getBalance(pubkey)` | SOL balance (for fee management) | Admin dashboard |
| `getTokenAccountBalance(ata)` | SPL token balance (USDT/USDC) | Wallet balance display |
| `sendTransaction(signedTx)` | Broadcast a signed transaction | Send button execution |
| `getSignaturesForAddress(pubkey)` | Transaction history for an address | Sync/backfill transaction records |

### 11.2 Tracking Detail (Expandable Row Data)

For each `crypto_wallet_transactions` record, the expandable detail fetches from Solana RPC:

```json
{
    "tx_signature": "5Ht7Kx...mN3qR",
    "block_slot": 245892103,
    "block_time": "2026-02-11T08:15:32Z",
    "fee_lamports": 5000,
    "fee_sol": 0.000005,
    "status": "finalized",
    "confirmations": null,
    "from_address": "7xKp...mN3q",
    "to_address": "9bRt...kP2w",
    "token_amount": 50000.00,
    "token_mint": "Es9vMFrzaCERmKfreVB4bFcGpNRB2Ggz...",
    "token_symbol": "USDT",
    "explorer_url": "https://explorer.solana.com/tx/5Ht7Kx...mN3qR",
    "solscan_url": "https://solscan.io/tx/5Ht7Kx...mN3qR"
}
```

---

## 12. Files to Create (Following Existing xramp Patterns)

### Models
- `app/Models/CryptoWallet.php`
- `app/Models/CryptoWalletTransaction.php`

### Migrations
- `database/migrations/2026_02_XX_create_crypto_wallets_table.php`
- `database/migrations/2026_02_XX_create_crypto_wallet_transactions_table.php`
- `database/migrations/2026_02_XX_add_solana_fields_to_transactions_table.php`

### Views
- `resources/views/pages/administrator/wallets.blade.php` — Admin wallet management
- `resources/views/pages/modules/wallets.blade.php` — Client wallet page with transaction history

### Services
- `app/Services/WalletIdsService.php` — WalletIDs.net API integration (create wallet, derive, balance, key recovery)
- `app/Services/SolanaRpcService.php` — Solana RPC integration (send transaction, get transaction details, get balance)

### Controllers
- Admin wallet methods added to `AdminController.php` (or a new `AdminWalletController.php`)
- Client wallet methods added to `ModuleController.php`

### Routes (in `routes/web.php`)

**Admin routes:**
```
GET    /administrator/wallets                  → Admin wallets management page
GET    /administrator/wallets/list             → List wallets (JSON, filterable by account)
POST   /administrator/wallets                  → Create wallet (via WalletIDs.net)
PUT    /administrator/wallets/{hash}           → Update wallet
DELETE /administrator/wallets/{hash}           → Soft delete wallet
POST   /administrator/wallets/{hash}/send      → Admin-initiated send (sub-distribution)
```

**Client routes:**
```
GET    /modules/wallets                        → Client wallets page with transaction history
GET    /modules/wallets/{hash}/balance         → Get wallet balance (JSON, real-time)
GET    /modules/wallets/{hash}/transactions    → Get wallet transactions (JSON)
GET    /modules/wallets/tx/{hash}/detail       → Get Solana tracking detail for a transaction (JSON)
POST   /modules/wallets/{hash}/send            → Client-initiated send to 3rd party
```

**Webhook route:**
```
POST   /webhooks/walletids                     → WalletIDs.net webhook receiver (payment_detected, balance_changed)
```

---

## 13. Sidebar & Menu Integration Points

### ViewComposerServiceProvider.php

Add to `menuItemEnabled` array:
```php
'wallets' => $menuToggles['can_view_wallets'] ?? true,
```

Add to permission checks:
```php
'canViewWallets' => $this->hasUserPermission('can_view_wallets', $membership, $user),
```

### AdminController.php — menuItems()

Add to `$menuItems` array:
```php
'can_view_wallets' => 'Wallets',
```

Add to `$allMenuItems` array:
```php
'can_view_wallets',
```

### Admin Panel Tile

Add to `/administrator` index grid:
```html
<a href="{{ route('admin.wallets') }}" class="p-4 rounded-lg text-center hover:opacity-80"
   style="background-color: var(--content-background-color);">
    <p class="font-medium">Wallets</p>
    <p class="text-sm opacity-70">Manage crypto wallets</p>
</a>
```

---

## 14. Security Considerations

| Concern | Mitigation |
|---------|-----------|
| **Private key exposure** | Keys retrieved from WalletIDs.net only at transaction signing time, never stored in xramp database. Used in-memory, discarded after broadcast. |
| **Custodial risk** | xramp IS custodial for client wallets (xramp holds the keys via WalletIDs.net). This has regulatory implications — may require MSB/VASP licensing depending on jurisdiction. |
| **Send authorization** | Client sends require: (a) authenticated session, (b) permission slug `can_view_wallets`, (c) wallet belongs to active account, (d) optional: admin approval for amounts above threshold. |
| **Webhook verification** | WalletIDs.net webhooks include HMAC-SHA256 signature — must verify before processing. |
| **SOL for fees** | Each wallet needs a small SOL balance (~0.01 SOL) to pay Solana transaction fees. Admin must fund wallets with SOL. Consider adding a "Fund SOL" admin action. |
| **Associated Token Accounts** | On Solana, each wallet needs an ATA created for each SPL token it will hold. ATAs cost ~0.002 SOL to create. This should be done automatically when the wallet is created or on first token receipt. |

---

## 15. Suggestions for Improvement / Things to Consider

### 15.1 Approval Workflow for Large Transfers
For transfers above a configurable threshold (e.g., $10,000 USDC), require admin approval before execution. Add a `pending_approval` status to the transaction flow.

### 15.2 Whitelisted Destination Addresses
Allow clients (or admins) to maintain an **address book** of trusted/whitelisted destination wallets. This reduces the risk of sending to the wrong address and speeds up repeat transfers.

### 15.3 Minimum Balance Alerts
Send admin notifications when:
- Master wallet SOL balance is low (can't pay fees)
- Master wallet stablecoin balance is low (can't sub-distribute)
- Client wallet SOL balance is low

### 15.4 Batch Sub-Distribution
When tether.to delivers a large aggregated amount to the master wallet, admin may need to sub-distribute to multiple client wallets at once. A **batch send** feature (select multiple client wallets + amounts) would save time.

### 15.5 Automatic Sub-Distribution
Future enhancement: when a fiat transaction reaches "Exchanged" phase and the USDT/USDC arrives in the master wallet (detected via webhook), automatically sub-distribute to the client's designated wallet without manual admin intervention.

### 15.6 Fee Deduction
xramp can deduct its service fee during sub-distribution. Example: client is owed 100,000 USDT, xramp fee is 0.5%, xramp sends 99,500 USDT to client wallet. The fee amount and calculation should be recorded in the transaction.

### 15.7 Multi-Network Support (Future)
While Solana is the preferred/primary network, the schema is designed to be extensible. Adding Ethereum, Polygon, or Tron support later would require:
- Additional `wallet_network` values
- Network-specific signing logic in `SolanaRpcService.php` → generalized to `BlockchainService.php`
- Network-specific ATA/gas management

### 15.8 Reconciliation Dashboard
Admin view showing: total master wallet balance vs. sum of all client wallet balances vs. sum of all pending settlements. This ensures the books balance.

---

## 16. End-to-End Flow Summary

```
1. Client wires $100,000 USD to xramp IBAN
   → Transaction created: Phase 1 (Received)

2. xramp admin wires $100,000 to tether.to
   → Tether mints 100,000 USDT → xramp Master Wallet (Solana)
   → WalletIDs.net webhook fires: payment_detected
   → crypto_wallet_transactions: INCOMING to master wallet
   → Transaction updated: Phase 2 (Exchanged), solana_inbound_tx_signature stored

3. xramp admin sub-distributes from Master Wallet → Client Wallet
   → Admin clicks Send on master wallet, selects client wallet, enters amount (minus fees)
   → Solana SPL transfer executed
   → crypto_wallet_transactions: OUTGOING from master wallet + INCOMING to client wallet
   → Transaction updated: Phase 3 (Settled), solana_outbound_tx_signature stored

4. Client views their wallet at /modules/wallets
   → Sees wallet balance: 99,500 USDT
   → Sees transaction history: incoming 99,500 USDT from xramp
   → Can click to expand and see full Solana tracking details

5. Client sends 50,000 USDT to a 3rd-party wallet
   → Clicks Send, enters destination address and amount
   → crypto_wallet_transactions: OUTGOING from client wallet
   → Full Solana tx tracking recorded
   → Both client and admin can view the transaction in /modules/wallets
```

---

## 17. Document Cross-References

| Document | Purpose |
|----------|---------|
| `README-CYRPTO-EXCHANGE-TRACEABILITY-001.md` | Predecessor: Option 2 analysis and decision |
| `COMMON-PORTAL-DATABASE-SCHEMA-002.md` | Database schema conventions (dual-ID pattern) |
| `XRAMP-IBANS-FRAMEWORK-README.md` | IBAN management (same admin pattern to follow for wallets) |
| `COMMON-PORTAL-DEVELOPMENT-ROADMAP-002.md` | Phase tracking |
| `COMMON-PORTAL-ADMIN-ACCOUNTING-TRANSACTION-MANAGER-001.md` | Existing 3-phase transaction model |
| WalletIDs.net API Docs | https://walletids.net/api-documentation |
| Solana RPC Docs | https://docs.solana.com/api |