# Direct Debit Processing Cron — Process Flow

## Overview

This document maps the end-to-end flow for the automated SEPA Direct Debit collection process. The system uses the **SH Financial API** (`https://dev-api.sh-payments.com/api/v1/`) to initiate direct debit collections from customer bank accounts into the merchant's settlement ledger.

---

## 1. Data We Have (Per Customer Mandate)

| Field | Source | Example |
|-------|--------|---------|
| `customer_full_name` | `customers` table | ACME LLC |
| `customer_primary_contact_email` | `customers` table | sage.smith@corp.nsdb.com |
| `customer_iban` | `customers` table (debtor IBAN) | DE89370400440532013000 |
| `customer_bic` | `customers` table (debtor BIC) | COBADEFFXXX |
| `billing_name_on_account` | `customers` table | ACME LLC |
| `billing_amount` | `customers` table (decimal) | 100.01 |
| `billing_currency` | `customers` table (ISO3) | EUR |
| `recurring_frequency` | `customers` table | weekly / monthly / daily |
| `billing_dates` | `customers` table (JSON array) | ["monday","tuesday","friday"] or [1,15] |
| `billing_start_date` | `customers` table (date) | 2026-02-10 |
| `mandate_status` | `customers` table | mandate_confirmed |
| `mandate_active_or_paused` | `customers` table (**NEW**) | active / paused |
| `settlement_iban_hash` | `customers` table → `iban_accounts.record_unique_identifier` | (uuid hash) |
| `iban_ledger` | `iban_accounts` table (SH Financial Ledger UUID) | 3fa85f64-5717-4562-b3fc-2c963f66afa6 |
| `iban_number` | `iban_accounts` table (creditor IBAN) | LT563981120000000432 |
| `tenant_account_id` | `customers` table → tenant account | (int) |

---

## 2. SH Financial API — Key Endpoints

### 2.1 Create Payment (Single Direct Debit Collection)
```
POST /api/v1/payment
```
**Schema: `PaymentInstructionViewModel`**
```json
{
  "correlationId": "string (optional, for idempotency)",
  "sourceLedgerUid": "uuid (debtor/customer ledger — see note below)",
  "destinationLedgerUid": "uuid (creditor/merchant settlement ledger)",
  "amount": 10001,  // int64, minor units (cents). 100.01 EUR = 10001
  "reference": "string (mandate reference / payment description)",
  "paymentReason": "string (optional)"
}
```

### 2.2 Create Batch (Multiple Direct Debit Collections)
```
POST /api/v1/batch
```
**Schema: `InstructionBatchViewModel`**
```json
{
  "paymentInstructions": [
    {
      "sourceLedgerUid": "uuid",
      "destinationLedgerUid": "uuid",
      "amount": 10001,
      "reference": "DD-ACME-20260210-001"
    },
    {
      "sourceLedgerUid": "uuid",
      "destinationLedgerUid": "uuid",
      "amount": 5000,
      "reference": "DD-JONES-20260210-001"
    }
  ],
  "transferInstructions": []
}
```

### 2.3 Get Transaction Status
```
GET /api/v1/transactions/{transactionUid}
```

### 2.4 Get Available Schema (Check SEPA Routing)
```
GET /api/v1/transactions/availableschema/{iban}/{currencyCode}
```

### 2.5 Mandate Info (attached to payment context)
**Schema: `MandateInfo`** (required fields: `id`, `dateOfSignature`, `creditorId`)
```json
{
  "id": "MANDATE-REF-001",
  "eSignature": "string (optional, up to 1025 chars)",
  "dateOfSignature": "2026-02-08",
  "creditorId": "DE98ZZZ09999999999"
}
```

### 2.6 Ledger Types
```
Customer | Settlement | Fees | External | DebitCard | PrepaidCard
```

---

## 3. Direct Debit Collection — Process Flow

### 3.1 Cron Schedule
- **Runs daily at 01:00 UTC** (start of European business day)
- Laravel scheduled command: `php artisan directdebit:process-collections`

### 3.2 Step-by-Step Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  01:00 UTC — Cron fires: directdebit:process-collections       │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 1: Query Eligible Mandates                                │
│                                                                 │
│  SELECT * FROM customers                                        │
│  WHERE mandate_status = 'mandate_confirmed'                     │
│    AND mandate_active_or_paused = 'active'                      │
│    AND settlement_iban_hash IS NOT NULL                          │
│    AND billing_amount > 0                                       │
│                                                                 │
│  EAGER LOAD: settlementIban (for iban_ledger UUID)              │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 2: Filter by Today's Billing Schedule                     │
│                                                                 │
│  For each mandate, check if TODAY is a billing day:             │
│                                                                 │
│  • daily    → always eligible (if start_date <= today)          │
│  • weekly   → check if today's day name is in billing_dates[]   │
│              e.g. ["monday","wednesday","friday"]                │
│  • monthly  → check if today's day-of-month is in billing_dates │
│              e.g. [1, 15] → bill on 1st and 15th               │
│                                                                 │
│  Skip mandates where today is NOT a billing day                 │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 3: Check for Duplicate / Already Processed                │
│                                                                 │
│  For each eligible mandate, check the transactions table:       │
│  - Has this mandate already been billed today?                  │
│  - Use idempotency key: {customer_hash}_{date}_{sequence}      │
│                                                                 │
│  Skip if already processed today                                │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 4: Build Payment Instructions                             │
│                                                                 │
│  For each eligible mandate:                                     │
│  {                                                              │
│    "sourceLedgerUid":      <customer's external ledger/IBAN>,   │
│    "destinationLedgerUid": <iban_accounts.iban_ledger>,         │
│    "amount":               billing_amount * 100 (to cents),     │
│    "reference":            "DD-{mandate_ref}-{YYYYMMDD}-{seq}", │
│    "correlationId":        "{customer_hash}_{YYYYMMDD}"         │
│  }                                                              │
│                                                                 │
│  Group by tenant_account for batch processing                   │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 5: Submit to SH Financial API                             │
│                                                                 │
│  Option A: Single payments                                      │
│    POST /api/v1/payment (one per mandate)                       │
│                                                                 │
│  Option B: Batch payments (recommended)                         │
│    POST /api/v1/batch (grouped by tenant account)               │
│                                                                 │
│  Store API response (transactionUid, status) in local           │
│  transactions table                                             │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 6: Record Transaction Locally                             │
│                                                                 │
│  INSERT INTO transactions:                                      │
│  - tenant_account_id                                            │
│  - customer_id                                                  │
│  - amount                                                       │
│  - currency                                                     │
│  - reference                                                    │
│  - sh_transaction_uid (from API response)                       │
│  - status: 'pending' → updated via webhook/polling              │
│  - billing_date: today                                          │
│  - created_at_timestamp                                         │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 7: Post-Processing                                        │
│                                                                 │
│  - Log summary: X mandates processed, Y succeeded, Z failed    │
│  - Send notification email to tenant account admin (optional)   │
│  - Failed transactions: flag for retry or manual review         │
│  - Webhook listener for status updates from SH Financial        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Database Changes Required

### 4.1 Add `mandate_active_or_paused` to `customers` Table
```php
// Migration
Schema::table('customers', function (Blueprint $table) {
    $table->string('mandate_active_or_paused', 20)
          ->default('active')
          ->after('mandate_status');
});
```

### 4.2 Transactions Table (may need additional columns)
Ensure the `transactions` table has:
- `customer_id` — FK to customers
- `sh_transaction_uid` — UUID from SH Financial API response
- `billing_date` — the date this DD was processed for
- `correlation_id` — idempotency key

---

## 5. Active/Paused Toggle (UI Prerequisite)

Before the cron can run, each confirmed mandate needs an **Active/Paused** toggle:

- **Default:** `active` (green)
- **Paused:** red indicator
- **Location:** Top of the customer mandate details dropdown (expanded row)
- **Confirmation:** Modal in both directions ("Are you sure you want to pause/activate?")
- **Effect:** Paused mandates are **excluded** from the daily cron processing

---

## 6. Cron Registration (Laravel Kernel)

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('directdebit:process-collections')
             ->dailyAt('01:00')
             ->timezone('UTC')
             ->withoutOverlapping()
             ->appendOutputTo(storage_path('logs/directdebit-cron.log'));
}
```

---

## 7. Important Considerations

1. **Idempotency:** Each collection must have a unique correlation ID to prevent double-billing. Format: `{customer_record_unique_identifier}_{YYYY-MM-DD}_{sequence}`

2. **Amount Format:** SH Financial API uses **int64 minor units** (cents). `100.01 EUR` → `10001`

3. **SEPA Pre-Notification:** SEPA rules require 14-day advance notice before first debit (or shorter if agreed). The mandate confirmation date + grace period should be checked.

4. **Error Handling:**
   - API timeout → retry with backoff
   - Insufficient funds (customer) → record failure, notify merchant
   - Invalid IBAN → flag mandate for review
   - Rate limiting → throttle API calls

5. **Timezone:** Cron runs at 01:00 UTC. For billing schedule matching, use UTC date to determine "today."

6. **Weekend/Holiday Handling:** SEPA doesn't process on bank holidays. Consider TARGET2 calendar for settlement dates.

7. **First vs. Recurring:** SEPA DD distinguishes between FRST (first) and RCUR (recurring) sequence types. Track whether this is the customer's first collection.

---

## 8. Implementation Order

1. ✅ Settlement IBAN / Ledger configuration (done)
2. ✅ Add `mandate_active_or_paused` column + Active/Paused toggle UI (done)
3. ✅ Create `directdebit:process-collections` Artisan command (done)
4. ✅ Implement billing schedule matching logic (daily/weekly/monthly — built into command)
5. ✅ Implement SH Financial API client — `DirectDebitApiService` (payment + batch endpoints)
6. ✅ Add transaction recording with idempotency — `direct_debit_collections` table + model
7. ✅ Register cron in `routes/console.php` — daily at 01:00 UTC, withoutOverlapping
8. ✅ Add webhook listener for DD transaction status updates — `POST /webhooks/sh-financial/directdebit/v1/`
9. ✅ Add DD transactions page at `/transactions/directdebit` — expandable row details, refund button, search/filter
