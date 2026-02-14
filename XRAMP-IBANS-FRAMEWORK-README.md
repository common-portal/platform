# XRAMP IBAN Management Framework

## Overview

The IBAN Management Framework allows platform administrators to manage IBAN (International Bank Account Number) accounts for client accounts. This system enables administrators to add, edit, and soft-delete IBAN records associated with impersonated accounts.

**Key Design Decision:** IBAN balances are retrieved in real-time via API calls to host banks rather than stored locally, ensuring accuracy and eliminating synchronization issues.

---

## Database Schema

### Table: `iban_host_banks`

Stores the list of supported host banks that can be associated with IBANs.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (auto-increment) | Primary key |
| `record_unique_identifier` | VARCHAR(32) | Unique hash (md5) |
| `host_bank_name` | VARCHAR(255) | Friendly name of the host bank |
| `is_active` | BOOLEAN | Whether the host bank is active (default: true) |
| `is_deleted` | BOOLEAN | Soft delete flag (default: false) |
| `datetime_created` | TIMESTAMP | Record creation timestamp |
| `datetime_updated` | TIMESTAMP | Last update timestamp |

### Table: `iban_accounts`

Stores IBAN account records linked to tenant accounts.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (auto-increment) | Primary key |
| `record_unique_identifier` | VARCHAR(32) | Unique hash (md5) |
| `account_hash` | VARCHAR(32) | Links to `tenant_accounts.record_unique_identifier` |
| `iban_friendly_name` | VARCHAR(255) | User-friendly name for the IBAN |
| `iban_currency_iso3` | VARCHAR(3) | Currency code (AUD, CNY, EUR, GBP, MXN, USD) |
| `iban_number` | VARCHAR(34) | The IBAN number |
| `iban_host_bank_hash` | VARCHAR(32) | Links to `iban_host_banks.record_unique_identifier` |
| `creator_member_hash` | VARCHAR(32) | Links to `platform_members.record_unique_identifier` |
| `is_active` | BOOLEAN | Status toggle: ACTIVE (true) / PAUSED (false) |
| `is_deleted` | BOOLEAN | Soft delete flag (default: false) |
| `datetime_created` | TIMESTAMP | Record creation timestamp |
| `datetime_updated` | TIMESTAMP | Last update timestamp |

---

## Admin Pages

### 1. IBAN Host Banks (`/administrator/iban-host-banks`)

Manages the list of supported host banks.

**Features:**
- Add new host bank with friendly name
- Edit existing host bank names
- Toggle active/inactive status
- Soft delete host banks

### 2. IBANs (`/administrator/ibans`)

Manages IBAN accounts for client accounts.

**Features:**
- Select account (via impersonation or dropdown)
- Dropdown to select existing IBAN or "ADD NEW IBAN"
- Form fields:
  - **IBAN Friendly Name** (required)
  - **IBAN Currency** (dropdown: AUD, CNY, EUR, GBP, MXN, USD - alphabetical)
  - **IBAN Number** (required)
  - **IBAN Host Bank** (dropdown from `iban_host_banks` table)
  - **IBAN Status** (toggle: ACTIVE / PAUSED)
- Soft Delete button for existing records
- Create/Update button

---

## API Endpoints

### IBAN Host Banks

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/administrator/iban-host-banks` | Host banks management page |
| GET | `/administrator/iban-host-banks/list` | Get all host banks (JSON) |
| GET | `/administrator/iban-host-banks/{hash}` | Get single host bank (JSON) |
| POST | `/administrator/iban-host-banks` | Create new host bank |
| PUT | `/administrator/iban-host-banks/{hash}` | Update host bank |
| DELETE | `/administrator/iban-host-banks/{hash}` | Soft delete host bank |

### IBANs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/administrator/ibans` | IBAN management page |
| GET | `/administrator/ibans/list?account_hash={hash}` | Get IBANs for account (JSON) |
| GET | `/administrator/ibans/{hash}` | Get single IBAN (JSON) |
| POST | `/administrator/ibans` | Create new IBAN |
| PUT | `/administrator/ibans/{hash}` | Update IBAN |
| DELETE | `/administrator/ibans/{hash}` | Soft delete IBAN |

---

## Future Enhancements

### Real-Time Balance API Integration

Each host bank record can be extended with API credentials and endpoint configuration:

```php
// Future columns for iban_host_banks table
'api_endpoint_url' => 'https://api.hostbank.com/v1',
'api_auth_type' => 'oauth2', // oauth2, api_key, basic
'api_credentials_encrypted' => '...', // Encrypted credentials
```

This will enable:
- Real-time balance fetching per IBAN
- Transaction history retrieval
- Automated reconciliation

---

## Files

### Models
- `app/Models/IbanAccount.php`
- `app/Models/IbanHostBank.php`

### Migrations
- `database/migrations/2026_02_01_170000_create_iban_accounts_table.php`
- `database/migrations/2026_02_01_171000_create_iban_host_banks_table.php`

### Views
- `resources/views/pages/administrator/ibans.blade.php`
- `resources/views/pages/administrator/iban-host-banks.blade.php`

### Routes
Defined in `routes/web.php` under the administrator prefix with `platform.admin` middleware.

### Controller
`app/Http/Controllers/AdminController.php` - Contains all IBAN and Host Bank CRUD methods.

---

## Usage

1. **First**, create host banks in the IBAN Host Banks admin page
2. **Then**, create IBANs for client accounts by:
   - Impersonating an account (recommended) or selecting from dropdown
   - Selecting "ADD NEW IBAN" or an existing IBAN to edit
   - Filling in the IBAN details including selecting the host bank
   - Clicking Create/Update to save
