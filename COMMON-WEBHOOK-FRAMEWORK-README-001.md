# Webhook Framework Documentation

## Overview

This document describes the webhook framework for xramp.io. All inbound webhooks are received at:
```
https://xramp.io/webhooks/{provider}/{version}/
```

---

## Current Webhook Integrations

### SH Financial (`/webhooks/sh-financial/v1/`)

**Purpose:** Receive transaction notifications from SH Financial banking gateway.

**Source Gateway:** `https://utilities.getmondo.co/gateway/sh-financial/webhooks/v1/index.php`

**Authentication:** 
- Header: `Shf-Secret-Key: AKmjCcK)sFA50yyhyJ2xkEN*dXe4fesD`
- All requests without valid secret key are rejected with 401

---

## Webhook Processing Flow

### Phase 1: Receive & Validate
1. Validate authentication header
2. Parse JSON payload
3. Check for duplicate (using webhook `Id` field)
4. Log webhook to `webhook_logs` table

### Phase 2: Route by Type
- **Type 0:** Transfer (internal)
- **Type 1:** Incoming Payment (SEPA/SWIFT) ← Primary use case
- **Type 2:** Outgoing Payment status update
- **Type 3:** FX Transfer
- **Type 4:** Internal Payment

### Phase 3: Process Incoming Payments (Type 1, Status 1)
1. Fetch full transaction details from SH Financial API
2. Extract destination IBAN from transaction data
3. Lookup IBAN in `iban_accounts` table to find `tenant_account_id`
4. Create Transaction record with:
   - `transaction_status: 'phase1_received'`
   - `datetime_received: now()`
   - `amount: webhook_amount / 100` (convert from cents)
   - `currency_code: from API response`
5. Return 200 OK

---

## Database Tables

### `webhook_logs`
Tracks all received webhooks for:
- Duplicate prevention (idempotency)
- Audit trail
- Debugging

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| record_unique_identifier | varchar(36) | UUID |
| provider | varchar(50) | e.g., 'sh-financial' |
| webhook_id | varchar(100) | External webhook ID |
| transaction_uid | varchar(100) | External transaction UID |
| webhook_type | int | Transaction type (0-4) |
| webhook_status | int | Transaction status (0-6) |
| payload | json | Full webhook payload |
| processing_status | varchar(20) | received/processed/failed |
| processing_notes | text | Error messages, etc. |
| created_at_timestamp | datetime | When received |
| processed_at_timestamp | datetime | When processed |

---

## SH Financial Webhook Payload

```json
{
  "TransactionUID": "c691b6eb-435b-f011-8f7c-000d3ab6e73e",
  "Amount": 40000,
  "Type": 1,
  "TransactionDateTime": "2025-07-07T15:27:08.680722",
  "Status": 1,
  "ProgrammeUID": "CidrusMoney-dev",
  "DateOccurred": "2025-07-07T15:27:15.3616824Z",
  "Id": "7a6cda82-a092-403e-aca1-f204e8248d8f"
}
```

### Transaction Types
| Type | Value | Description |
|------|-------|-------------|
| Transfer | 0 | Internal transfers |
| IncomingPayment | 1 | SEPA/SWIFT incoming |
| OutgoingPayment | 2 | Outgoing payments |
| FxTransfer | 3 | FX transfers |
| InternalPayment | 4 | Internal payments |

### Transaction Statuses
| Status | Value | Description |
|--------|-------|-------------|
| Pending | 0 | Transaction pending |
| Cleared | 1 | Successfully cleared |
| Rejected | 2 | Rejected |
| Clearing | 3 | In clearing process |
| FailedToClear | 4 | Failed to clear |
| RejectPending | 5 | Rejection pending |
| RejectFailed | 6 | Rejection failed |

---

## API Endpoints for Additional Data

### Get Full Transaction Details
```
GET https://utilities.getmondo.co/gateway/sh-financial/get_transaction_update_v2.php?transaction_uid={TransactionUID}
```

Returns full transaction data including:
- Destination IBAN
- Network type (SEPA/SWIFT)
- Currency code
- Sender/receiver details

---

## Implementation Files

- **Routes:** `routes/web.php` - Webhook routes at `/webhooks/*`
- **Controller:** `app/Http/Controllers/Webhook/ShFinancialController.php`
- **Model:** `app/Models/WebhookLog.php`
- **Migration:** `database/migrations/xxxx_create_webhook_logs_table.php`

---

## Security Considerations

1. **Secret Key Validation:** All webhooks must include valid `Shf-Secret-Key` header
2. **Idempotency:** Duplicate webhooks (same `Id`) are logged but not reprocessed
3. **Logging:** All webhooks are logged for audit trail
4. **Error Handling:** Failures don't block webhook acknowledgment (return 200)

---

## Testing

### Test Incoming Payment Webhook
```bash
curl -X POST https://xramp.io/webhooks/sh-financial/v1/ \
  -H "Content-Type: application/json" \
  -H "Shf-Secret-Key: AKmjCcK)sFA50yyhyJ2xkEN*dXe4fesD" \
  -d '{
    "TransactionUID": "test-uid-001",
    "Amount": 10000,
    "Type": 1,
    "TransactionDateTime": "2026-02-01T21:00:00.000000",
    "Status": 1,
    "ProgrammeUID": "CidrusMoney-dev",
    "DateOccurred": "2026-02-01T21:00:05.000000Z",
    "Id": "test-webhook-id-001"
  }'
```

---

*Last Updated: February 1, 2026*
