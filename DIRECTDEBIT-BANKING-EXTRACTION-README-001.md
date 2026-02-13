# Billing Account Details & Bank Name Extraction

> **Feature Date:** 2026-02-05  
> **Status:** Implemented & Migrated

---

## Overview

A new **Billing Account Details** card (Card 4) was added to the Add Customer form at `/account/customers`, positioned before the "Send Mandate Invitation" button. This card collects the customer's banking details and auto-detects the bank name from the BIC/SWIFT code using the xAI API.

---

## Form Fields

| Field | Type | Details |
|---|---|---|
| **Name on Account** | Text (max 255) | Account holder name |
| **Account IBAN** | Text (max 34) | Customer's IBAN number |
| **Account BIC** | Text (max 11) | BIC/SWIFT code; triggers bank name lookup on blur |
| **Bank Name** | Text (max 255) | Auto-populated from BIC lookup; also manually editable |

---

## Bank Name Auto-Detection (Dual Lookup)

Both IBAN and BIC fields trigger a bank name lookup on blur, with **short-circuit logic** — whichever is filled first and returns a valid bank name wins; the second field's blur will skip the API call entirely.

### Flow

1. User enters an **IBAN** (min 15 chars) or **BIC** (min 8 chars) and tabs out (blur event fires).
2. If the **Bank Name** field is already populated, the lookup is **skipped** (no API call).
3. Otherwise, an async `POST /account/lookup-bank-from-bic` request is sent with either `{ iban }` or `{ bic }`.
4. The backend sends a type-specific prompt to the **xAI API** (`grok-4-1-fast` model) that returns only the official bank name.
5. A **spinner** and "Identifying bank..." label appear next to the Bank Name field during processing.
6. On success, the Bank Name field is auto-filled.
7. On failure, an error message is displayed below the field; the user can still enter the bank name manually.

### Short-Circuit Examples

- **IBAN first → BIC second:** If the IBAN lookup returns a valid bank name, the BIC blur will not trigger an API call.
- **BIC first → IBAN second:** If the BIC lookup returns a valid bank name, the IBAN blur will not trigger an API call.
- **Both fail:** User can manually type the bank name.

---

## Files Changed

| File | Change |
|---|---|
| `database/migrations/2026_02_05_220000_add_billing_account_details_to_customers_table.php` | New migration: adds `billing_name_on_account` and `billing_bank_name` columns to `customers` table |
| `app/Models/Customer.php` | Added `billing_name_on_account`, `billing_bank_name` to `$fillable` |
| `config/services.php` | Added `xai` config block (`api_key`, `model`, `base_url`) |
| `app/Http/Controllers/AccountController.php` | New `lookupBankFromBic()` endpoint; updated `sendMandateInvitation()` validation & create with 4 new fields |
| `routes/web.php` | Added `POST /account/lookup-bank-from-bic` route |
| `resources/views/pages/account/customers.blade.php` | New Billing Account Details card with Alpine.js `bicLookup()` component |
| `.env` | `XAI_API_KEY` set |

---

## Environment Variables

```env
XAI_API_KEY=<your-xai-api-key>
XAI_MODEL=grok-4-1-fast          # optional, defaults to grok-4-1-fast
XAI_BASE_URL=https://api.x.ai/v1  # optional, defaults to https://api.x.ai/v1
```

---

## Database Columns Added

```
customers.billing_name_on_account  VARCHAR(255)  NULLABLE
customers.billing_bank_name        VARCHAR(255)  NULLABLE
```
