# SEPA Direct Debit — Process Flow & Technical Reference

> Single point of truth for the end-to-end SDD collection process.
> Last updated: 2026-02-10

---

## 1. Overview

The system collects payments from customers via SEPA Direct Debit (SDD) through the SH Financial API. A daily cron job identifies eligible mandates, submits collection requests, and records results. Webhooks from SH Financial asynchronously update collection statuses (cleared, rejected, failed).

---

## 2. End-to-End Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  CRON (daily 01:00 UTC)                                         │
│  artisan directdebit:process-collections                        │
│                                                                 │
│  Step 1 → Query eligible mandates (confirmed + active + IBAN)   │
│  Step 2 → Filter by billing schedule (daily/weekly/monthly)     │
│  Step 3 → Idempotency check (customer_id + billing_date)        │
│  Step 4 → Build payment instructions (amount, IBAN, mandate)    │
│  Step 5 → For each instruction:                                 │
│           a) Look up customer IBAN → sourceLedgerUid             │
│           b) Create local DirectDebitCollection (status: pending)│
│           c) POST /api/v1/payment/sdd/create to SH Financial    │
│           d) On success → mark submitted (store transactionUid) │
│              On failure → mark failed (store error reason)      │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│  SH FINANCIAL API                                               │
│  Processes the SDD collection through SEPA banking network      │
│  Timeline: typically 2-5 business days                          │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│  WEBHOOK (async callback)                                       │
│  POST /webhooks/sh-financial/directdebit/v1                     │
│                                                                 │
│  1. Validate Shf-Secret-Key header                              │
│  2. Check for duplicate webhook (by provider + webhook ID)      │
│  3. Log to webhook_logs table                                   │
│  4. Find matching DirectDebitCollection by:                     │
│     - sh_transaction_uid (primary)                              │
│     - correlation_id UUID (fallback)                            │
│  5. Update collection status based on SH Financial status code  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Status Lifecycle

```
pending → submitted → clearing → cleared ✓
                   ↘ rejected ✗
                   ↘ failed ✗
```

| Our Status   | Trigger                     | SH Financial Status Code |
|-------------|-----------------------------|-----------------------|
| `pending`    | Collection created locally   | —                     |
| `submitted`  | API returns transactionUid   | —                     |
| `clearing`   | Webhook status 3            | 3 (Clearing)          |
| `cleared`    | Webhook status 1            | 1 (Cleared)           |
| `rejected`   | Webhook status 2, 5, or 6   | 2/5/6 (Rejected)      |
| `failed`     | API error or webhook status 4| 4 (Failed to Clear)   |

---

## 4. Key Files

| File | Purpose |
|------|---------|
| `app/Console/Commands/ProcessDirectDebitCollections.php` | Cron command — orchestrates the entire collection process |
| `app/Services/DirectDebitApiService.php` | SH Financial API client — OAuth2 auth, SDD create/cancel, IBAN lookup, transaction status |
| `app/Http/Controllers/Webhook/ShFinancialDirectDebitController.php` | Webhook handler — receives async status updates from SH Financial |
| `app/Models/DirectDebitCollection.php` | Eloquent model — collection record with status methods |
| `app/Models/WebhookLog.php` | Eloquent model — webhook audit trail with duplicate detection |
| `config/services.php` | SH Financial credentials (`services.shfinancial.*`) |
| `config/logging.php` | `directdebit` log channel → `storage/logs/directdebit-cron.log` |
| `routes/console.php` | Cron schedule — daily at 01:00 UTC |
| `routes/web.php` | Webhook route — `POST /webhooks/sh-financial/directdebit/v1` |
| `database/migrations/2026_02_09_*_create_direct_debit_collections_table.php` | DB schema |
| `database/migrations/2026_02_01_*_create_webhook_logs_table.php` | Webhook log schema |

---

## 5. SH Financial API Integration

### Authentication
- **OAuth2 client_credentials** grant
- Token endpoint: `{SHF_API_URL}/connect/token`
- Token is cached in-memory for the process duration (with 60s buffer before expiry)

### Endpoints Used

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/v1/payment/sdd/create` | Submit SDD collection |
| `POST` | `/api/v1/payment/sdd/cancel` | Cancel a pending SDD |
| `GET`  | `/api/v1/bankaccounts?Filters[iban]={iban}` | Look up customer IBAN → ledgerUid |
| `GET`  | `/api/v1/transactions/{transactionUid}` | Fetch transaction status |

### SDD Create Payload Structure
```json
{
  "correlationId": "UUID",
  "destinationLedgerUid": "creditor-ledger-uuid",
  "createDirectDebitRequests": [
    {
      "sourceLedgerUid": "debtor-ledger-uuid",
      "amount": 10000,
      "reference": "DD-JOHNDOE-20260210",
      "paymentReason": "SEPA Direct Debit Collection",
      "mandateInfo": {
        "id": "mandate-uuid",
        "eSignature": "",
        "dateOfSignature": "2026-01-15",
        "creditorId": "SEPA-CREDITOR-ID",
        "amendmentInfo": null
      },
      "sddSequenceType": "FRST(First)",
      "settlementDate": "2026-02-10"
    }
  ]
}
```

### SDD Create Response (GenericResponse wrapper)
```json
{
  "isSuccess": true,
  "errorMessage": null,
  "item": {
    "correlationId": "UUID",
    "destinationLedgerUid": "...",
    "createDirectDebitResponses": [
      {
        "isSuccess": true,
        "transactionUid": "txn-uuid",
        "uniqueKey": "unique-key-string",
        "errors": []
      }
    ]
  }
}
```

---

## 6. Webhook Payload (Inbound from SH Financial)

```json
{
  "Id": "webhook-id",
  "TransactionUID": "txn-uuid",
  "CorrelationId": "uuid-we-sent",
  "Status": 1,
  "Amount": 10000,
  "Type": 2,
  "RejectReason": "...",
  "FailureReason": "..."
}
```

- **Auth**: `Shf-Secret-Key` header must match hardcoded secret
- **Idempotency**: Duplicate webhooks detected by `provider + webhook_id` unique index

---

## 7. Configuration (.env)

```env
SHF_API_URL=https://api.sh-payments.com
SHF_CLIENT_ID=your-client-id
SHF_CLIENT_SECRET=your-client-secret
SHF_SCOPE=apiv1.programme
SHF_CREDITOR_ID=LT10ZZZ188607684         # Lithuania-issued SEPA Creditor ID
```

These are read in `config/services.php` → `services.shfinancial.*` and accessed via `config()` (not `env()`) to support config caching.

---

## 8. Database Tables

### `direct_debit_collections`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `record_unique_identifier` | string(64) | Auto-generated UUID |
| `tenant_account_id` | FK | → tenant_accounts |
| `customer_id` | FK | → customers |
| `correlation_id` | string(128) | UUID sent to SH Financial (unique) |
| `reference` | string(255) | Human-readable: DD-NAME-YYYYMMDD |
| `amount` | decimal(20,2) | EUR amount |
| `amount_minor_units` | bigint | Cents for API |
| `source_iban` | string(64) | Debtor IBAN |
| `destination_iban` | string(64) | Creditor IBAN |
| `destination_ledger_uid` | uuid | Creditor's SH Financial ledger |
| `sh_transaction_uid` | uuid | From API response |
| `sh_batch_id` | string(128) | Stores `uniqueKey` from response |
| `status` | string(30) | pending/submitted/cleared/failed/rejected |
| `failure_reason` | string(500) | Error/rejection detail |
| `billing_date` | date | The billing date this collection is for |
| `sequence_type` | string(10) | FRST or RCUR |

### `webhook_logs`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `provider` | string(50) | `sh-financial-directdebit` |
| `webhook_id` | string(100) | SH Financial's webhook ID |
| `transaction_uid` | string(100) | Links to collection |
| `webhook_type` / `webhook_status` | int | Raw SH Financial values |
| `payload` | json | Full webhook body |
| `processing_status` | string(20) | received/processed/failed/ignored/duplicate |

---

## 9. Sequence Type Logic

| Condition | Sequence Type | SDD Value |
|-----------|--------------|-----------|
| Customer has NO prior cleared collection | First | `FRST(First)` |
| Customer has ≥1 prior cleared collection | Recurring | `RCUR(Recurring)` |

---

## 10. Idempotency

- **Cron**: Checks `customer_id + billing_date` in `direct_debit_collections` where status is pending/submitted/cleared. Failed/rejected collections are allowed to be retried.
- **Webhook**: Duplicate webhooks detected by `provider + webhook_id` composite unique index in `webhook_logs`.
- **API**: Each request carries a unique UUID `correlationId`.

---

## 11. Pending / Known Issues

| # | Issue | Status |
|---|-------|--------|
| 1 | `SHF_CREDITOR_ID` is required but not yet provided | ✅ **Resolved** — `LT10ZZZ188607684` |
| 2 | SH Financial returned 403 Forbidden on SDD create | **Needs SH Financial to enable SDD on programme** |
| 3 | `/api/v1/bankaccounts` IBAN lookup endpoint not confirmed in Swagger | **Verify when SDD access granted** |
| 4 | Webhook `RejectReason`/`FailureReason` field names need SH Financial confirmation | **Low priority — both casings handled** |

---

## 12. Testing

```bash
# Dry run (no API calls, no DB writes)
php artisan directdebit:process-collections --dry-run --date=2026-02-10

# Live run for specific date
php artisan directdebit:process-collections --date=2026-02-10

# Live run for specific tenant
php artisan directdebit:process-collections --tenant=1 --date=2026-02-10

# Check logs
tail -f storage/logs/directdebit-cron.log
```

---

## 13. Monitoring Checklist

- [ ] `storage/logs/directdebit-cron.log` — daily cron execution
- [ ] `direct_debit_collections` where `status = 'failed'` — API submission failures
- [ ] `direct_debit_collections` where `status = 'submitted'` and `submitted_at < NOW() - 5 days` — stuck collections
- [ ] `webhook_logs` where `processing_status = 'failed'` — webhook processing errors
- [ ] `webhook_logs` where `processing_status = 'ignored'` and `processing_notes LIKE '%No matching%'` — orphaned webhooks
