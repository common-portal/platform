# Common Portal — Database Schema

PostgreSQL database schema for the multi-tenant portal platform.

> **See also:** `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md` for feature context and business logic.

---

## 🔴 Dual-ID Schema Pattern (Framework Standard)

**Every table has TWO identifiers:**

| Column | Type | Purpose |
|--------|------|---------|
| `id` | BIGSERIAL | Internal primary key for FK relationships, fast joins |
| `record_unique_identifier` | VARCHAR(64) UNIQUE | External-safe identifier for APIs, URLs, data portability |

### Why Both?
| Use Case | Which ID |
|----------|----------|
| Foreign key references | `id` (auto-increment) |
| API responses / URLs | `record_unique_identifier` (hash) |
| Data migrations | `record_unique_identifier` survives ID changes |
| External integrations | `record_unique_identifier` (opaque, no enumeration) |

### Auto-Generation
The `record_unique_identifier` is auto-generated on model creation via the `HasRecordUniqueIdentifier` trait:
```php
// Generated as: md5(random_int + microtime + uniqid)
$model->record_unique_identifier = md5(random_int(100000, 999999) . microtime(true) . uniqid('', true));
```

### Lookup Helper
```php
$member = PlatformMember::findByUniqueIdentifier('a8f3b2c1d4e5f6...');
```

---

## 🔴 Naming Convention Policy

All table names, column names, and enum values follow the **Highly Descriptive Naming Convention**:
- No abbreviations
- No generic names without context
- Self-documenting for LLMs and humans
- Action-based permission slugs (`can_access_*`, `can_manage_*`)

---

## Table Overview

| Table | Purpose | Module |
|-------|---------|--------|
| `platform_members` | Individual users (login credentials, profile) | Core |
| `tenant_accounts` | Client organizations (personal or business) | Core |
| `tenant_account_memberships` | Members ↔ Accounts relationship with permissions | Core |
| `one_time_password_tokens` | OTP authentication tokens | Core |
| `team_membership_invitations` | Team invitation tracking | Core |
| `platform_settings` | Global platform configuration | Core |
| `external_service_api_credentials` | API keys for external services | Core |
| `cached_text_translations` | Translation cache for translator service | Core |
| `support_tickets` | Support ticket system | Optional |

---

## `platform_members`

Individual users who can log in to the platform.

```sql
CREATE TABLE platform_members (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    login_email_address             VARCHAR(255) NOT NULL UNIQUE,
    hashed_login_password           VARCHAR(255) NULL,
    member_first_name               VARCHAR(255) NOT NULL DEFAULT '',
    member_last_name                VARCHAR(255) NOT NULL DEFAULT '',
    profile_avatar_image_path       VARCHAR(500) NULL,
    preferred_language_code         VARCHAR(10) NOT NULL DEFAULT 'en',
    is_platform_administrator       BOOLEAN NOT NULL DEFAULT FALSE,
    email_verified_at_timestamp     TIMESTAMP NULL,
    created_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_platform_members_login_email 
    ON platform_members(login_email_address);

CREATE INDEX idx_platform_members_is_admin 
    ON platform_members(is_platform_administrator) 
    WHERE is_platform_administrator = TRUE;
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `login_email_address` | VARCHAR(255) | No | - | Unique login email |
| `hashed_login_password` | VARCHAR(255) | Yes | NULL | bcrypt hash (optional — OTP is primary auth) |
| `member_first_name` | VARCHAR(255) | No | '' | First name(s) |
| `member_last_name` | VARCHAR(255) | No | '' | Last name(s) |
| `profile_avatar_image_path` | VARCHAR(500) | Yes | NULL | Path to avatar image |
| `preferred_language_code` | VARCHAR(10) | No | 'en' | ISO language code |
| `is_platform_administrator` | BOOLEAN | No | FALSE | Platform-wide admin access |
| `email_verified_at_timestamp` | TIMESTAMP | Yes | NULL | When verified via OTP |
| `created_at_timestamp` | TIMESTAMP | No | NOW | Record creation |
| `updated_at_timestamp` | TIMESTAMP | No | NOW | Last update |

### Relationships Summary
- A **platform_member** can belong to multiple **tenant_accounts** (via tenant_account_memberships)
- A **tenant_account** can have multiple **platform_members** (via tenant_account_memberships)
- Each platform_member has one **personal_individual** account (auto-created on registration)
- Platform members can create/join multiple **business_organization** accounts
- **is_platform_administrator = true** bypasses all account-level permissions

### Personal Account Relationship (Important)
The 1:1 relationship between a member and their personal account is **implicit**, not enforced by FK:
- On registration, system creates `tenant_accounts` row with `account_type = 'personal_individual'`
- Then creates `tenant_account_memberships` row with `account_membership_role = 'account_owner'`
- Personal account is identified by: `account_type = 'personal_individual'` AND member is `account_owner`
- This design allows the standard membership table to handle all relationships uniformly

---

## `tenant_accounts`

Client organizations/companies. Each member has one personal account (auto-created) and can create/join multiple business accounts.

```sql
CREATE TYPE account_type_enum AS ENUM (
    'personal_individual',
    'business_organization'
);

CREATE TABLE tenant_accounts (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    account_display_name            VARCHAR(255) NOT NULL,
    account_type                    account_type_enum NOT NULL DEFAULT 'personal_individual',
    whitelabel_subdomain_slug       VARCHAR(100) NULL UNIQUE,
    branding_logo_image_path        VARCHAR(500) NULL,
    primary_contact_full_name       VARCHAR(255) NULL,
    primary_contact_email_address   VARCHAR(255) NULL,
    is_soft_deleted                 BOOLEAN NOT NULL DEFAULT FALSE,
    soft_deleted_at_timestamp       TIMESTAMP NULL,
    created_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tenant_accounts_subdomain 
    ON tenant_accounts(whitelabel_subdomain_slug) 
    WHERE whitelabel_subdomain_slug IS NOT NULL;

CREATE INDEX idx_tenant_accounts_not_deleted 
    ON tenant_accounts(is_soft_deleted) 
    WHERE is_soft_deleted = FALSE;
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `account_display_name` | VARCHAR(255) | No | - | Display name |
| `account_type` | ENUM | No | 'personal_individual' | Account type |
| `whitelabel_subdomain_slug` | VARCHAR(100) | Yes | NULL | Custom subdomain |
| `branding_logo_image_path` | VARCHAR(500) | Yes | NULL | Account logo path |
| `primary_contact_full_name` | VARCHAR(255) | Yes | NULL | Primary contact name |
| `primary_contact_email_address` | VARCHAR(255) | Yes | NULL | Primary contact email |
| `is_soft_deleted` | BOOLEAN | No | FALSE | Soft delete flag |
| `soft_deleted_at_timestamp` | TIMESTAMP | Yes | NULL | When soft deleted |
| `created_at_timestamp` | TIMESTAMP | No | NOW | Record creation |
| `updated_at_timestamp` | TIMESTAMP | No | NOW | Last update |

### Account Types

| Value | Description |
|-------|-------------|
| `personal_individual` | Auto-created on registration, one per member, cannot be deleted |
| `business_organization` | Manually created, shareable, can be soft deleted |

---

## `tenant_account_memberships`

Relationship between members and accounts. Stores role, permissions, and membership status.

```sql
CREATE TYPE account_membership_role_enum AS ENUM (
    'account_owner',
    'account_administrator',
    'account_team_member'
);

CREATE TYPE membership_status_enum AS ENUM (
    'awaiting_acceptance',
    'membership_active',
    'membership_revoked'
);

CREATE TABLE tenant_account_memberships (
    id                                      BIGSERIAL PRIMARY KEY,
    record_unique_identifier                VARCHAR(64) NOT NULL UNIQUE,
    tenant_account_id                       BIGINT NOT NULL REFERENCES tenant_accounts(id) ON DELETE CASCADE,
    platform_member_id                      BIGINT NOT NULL REFERENCES platform_members(id) ON DELETE CASCADE,
    account_membership_role                 account_membership_role_enum NOT NULL DEFAULT 'account_team_member',
    granted_permission_slugs                JSONB NOT NULL DEFAULT '[]',
    membership_status                       membership_status_enum NOT NULL DEFAULT 'awaiting_acceptance',
    membership_accepted_at_timestamp        TIMESTAMP NULL,
    membership_revoked_at_timestamp         TIMESTAMP NULL,
    created_at_timestamp                    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_timestamp                    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_account_id, platform_member_id)
);

CREATE INDEX idx_memberships_by_member 
    ON tenant_account_memberships(platform_member_id);

CREATE INDEX idx_memberships_by_account 
    ON tenant_account_memberships(tenant_account_id);

CREATE INDEX idx_memberships_active 
    ON tenant_account_memberships(membership_status) 
    WHERE membership_status = 'membership_active';
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `tenant_account_id` | BIGINT | No | - | FK → tenant_accounts |
| `platform_member_id` | BIGINT | No | - | FK → platform_members |
| `account_membership_role` | ENUM | No | 'account_team_member' | Role in account |
| `granted_permission_slugs` | JSONB | No | '[]' | Array of permission slugs |
| `membership_status` | ENUM | No | 'awaiting_acceptance' | Current status |
| `membership_accepted_at_timestamp` | TIMESTAMP | Yes | NULL | When member accepted invitation |
| `membership_revoked_at_timestamp` | TIMESTAMP | Yes | NULL | When revoked |
| `created_at_timestamp` | TIMESTAMP | No | NOW | Record creation |
| `updated_at_timestamp` | TIMESTAMP | No | NOW | Last update |

### Membership Roles

| Value | Description |
|-------|-------------|
| `account_owner` | Full control, created the account |
| `account_administrator` | Can manage team, settings |
| `account_team_member` | Limited by granted permissions |

### Membership Status Flow

| Value | Description |
|-------|-------------|
| `awaiting_acceptance` | Invitation sent, member has not logged in |
| `membership_active` | Member accepted, has access |
| `membership_revoked` | Access revoked, account hidden from dropdown |

### Permission Slugs

Stored in `granted_permission_slugs` as JSON array:

```json
["can_access_account_dashboard", "can_manage_team_members"]
```

| Slug | Controls |
|------|----------|
| `can_access_account_settings` | Account Settings page |
| `can_access_account_dashboard` | Dashboard page |
| `can_manage_team_members` | Team management page |
| `can_access_developer_tools` | Developer page (when enabled) |
| `can_access_support_tickets` | Support page (when enabled) |
| `can_view_transaction_history` | Transactions page (when enabled) |
| `can_view_billing_history` | Billing page (when enabled) |

---

## `one_time_password_tokens`

OTP tokens for passwordless authentication.

```sql
CREATE TABLE one_time_password_tokens (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    platform_member_id              BIGINT NOT NULL REFERENCES platform_members(id) ON DELETE CASCADE,
    hashed_verification_code        VARCHAR(255) NOT NULL,
    token_expires_at_timestamp      TIMESTAMP NOT NULL,
    token_used_at_timestamp         TIMESTAMP NULL,
    created_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_otp_by_member 
    ON one_time_password_tokens(platform_member_id);

CREATE INDEX idx_otp_valid 
    ON one_time_password_tokens(platform_member_id, token_expires_at_timestamp) 
    WHERE token_used_at_timestamp IS NULL;
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `platform_member_id` | BIGINT | No | - | FK → platform_members |
| `hashed_verification_code` | VARCHAR(255) | No | - | bcrypt hash of 4-6 digit PIN |
| `token_expires_at_timestamp` | TIMESTAMP | No | - | 72 hours from creation |
| `token_used_at_timestamp` | TIMESTAMP | Yes | NULL | When successfully used |
| `created_at_timestamp` | TIMESTAMP | No | NOW | When created |

### OTP Rules

1. Multiple tokens can be valid simultaneously (re-send doesn't invalidate old)
2. Tokens expire after 72 hours
3. On successful validation, all other pending tokens for that member are invalidated
4. PIN is 4-6 digits, stored as bcrypt hash

---

## `team_membership_invitations`

Tracks team invitations separately from memberships.

```sql
CREATE TYPE invitation_status_enum AS ENUM (
    'invitation_pending',
    'invitation_accepted',
    'invitation_expired'
);

CREATE TABLE team_membership_invitations (
    id                                      BIGSERIAL PRIMARY KEY,
    record_unique_identifier                VARCHAR(64) NOT NULL UNIQUE,
    tenant_account_id                       BIGINT NOT NULL REFERENCES tenant_accounts(id) ON DELETE CASCADE,
    invited_email_address                   VARCHAR(255) NOT NULL,
    invited_by_member_id                    BIGINT NOT NULL REFERENCES platform_members(id) ON DELETE CASCADE,
    invitation_status                       invitation_status_enum NOT NULL DEFAULT 'invitation_pending',
    invitation_resend_count                 INTEGER NOT NULL DEFAULT 0,
    invitation_last_sent_at_timestamp       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    invitation_accepted_at_timestamp        TIMESTAMP NULL,
    created_at_timestamp                    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_timestamp                    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_account_id, invited_email_address)
);

CREATE INDEX idx_invitations_by_email 
    ON team_membership_invitations(invited_email_address);

CREATE INDEX idx_invitations_pending 
    ON team_membership_invitations(invitation_status) 
    WHERE invitation_status = 'invitation_pending';
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `tenant_account_id` | BIGINT | No | - | FK → tenant_accounts |
| `invited_email_address` | VARCHAR(255) | No | - | Email being invited |
| `invited_by_member_id` | BIGINT | No | - | FK → platform_members (who sent) |
| `invitation_status` | ENUM | No | 'invitation_pending' | Current status |
| `invitation_resend_count` | INTEGER | No | 0 | Times invitation was resent |
| `invitation_last_sent_at_timestamp` | TIMESTAMP | No | NOW | Last time email sent |
| `invitation_accepted_at_timestamp` | TIMESTAMP | Yes | NULL | When accepted |
| `created_at_timestamp` | TIMESTAMP | No | NOW | Record creation |
| `updated_at_timestamp` | TIMESTAMP | No | NOW | Last update |

---

## `platform_settings`

Key-value store for global platform configuration.

```sql
CREATE TABLE platform_settings (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    setting_key                     VARCHAR(255) NOT NULL UNIQUE,
    setting_value                   TEXT NOT NULL,
    created_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX idx_platform_settings_key 
    ON platform_settings(setting_key);
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `setting_key` | VARCHAR(255) | No | - | Unique setting key |
| `setting_value` | TEXT | No | - | Value (JSON for complex) |
| `created_at_timestamp` | TIMESTAMP | No | NOW | Record creation |
| `updated_at_timestamp` | TIMESTAMP | No | NOW | Last update |

### Setting Keys

| Key | Value Type | Description |
|-----|------------|-------------|
| `platform_display_name` | string | Brand name next to logo |
| `platform_logo_image_path` | string | Logo file path |
| `platform_favicon_image_path` | string | Favicon file path |
| `social_sharing_preview_image_path` | string | Open Graph image path |
| `social_sharing_meta_description` | string | Default meta description |
| `active_theme_preset_name` | string | Current theme preset |
| `custom_theme_color_overrides` | JSON | CSS variable overrides |
| `sidebar_menu_item_visibility_toggles` | JSON | Menu item on/off toggles |

### Example Values

```sql
INSERT INTO platform_settings (setting_key, setting_value) VALUES
('platform_display_name', 'Common Portal'),
('active_theme_preset_name', 'dark_mode'),
('sidebar_menu_item_visibility_toggles', '{"can_access_account_settings": true, "can_access_account_dashboard": true, "can_manage_team_members": true, "can_access_developer_tools": false, "can_access_support_tickets": false}'),
('custom_theme_color_overrides', '{"--sidebar-background-color": "#1a1a2e", "--brand-primary-color": "#00ff88"}');
```

---

## `external_service_api_credentials`

API keys for external services (translation, etc.).

```sql
CREATE TABLE external_service_api_credentials (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    external_service_name           VARCHAR(100) NOT NULL,
    encrypted_api_key               TEXT NOT NULL,
    is_currently_active_service     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_api_credentials_active 
    ON external_service_api_credentials(is_currently_active_service) 
    WHERE is_currently_active_service = TRUE;
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `external_service_name` | VARCHAR(100) | No | - | e.g., "openai", "deepl" |
| `encrypted_api_key` | TEXT | No | - | Encrypted API key |
| `is_currently_active_service` | BOOLEAN | No | FALSE | Which service to use |
| `created_at_timestamp` | TIMESTAMP | No | NOW | Record creation |
| `updated_at_timestamp` | TIMESTAMP | No | NOW | Last update |

---

## `cached_text_translations`

Translation cache for the translator service. Stores OpenAI translations to avoid repeated API calls.

> **Implementation:** See `COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md` for full translator framework.

```sql
CREATE TABLE cached_text_translations (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    translation_hash                VARCHAR(64) NOT NULL UNIQUE,
    original_english_text           TEXT NOT NULL,
    target_language_iso3            VARCHAR(3) NOT NULL,
    translated_text                 TEXT NOT NULL,
    created_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_translations_lookup 
    ON cached_text_translations(target_language_iso3);

CREATE UNIQUE INDEX idx_translations_unique 
    ON cached_text_translations(md5(original_english_text), target_language_iso3);
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `translation_hash` | VARCHAR(64) | No | - | Unique hash for deduplication |
| `original_english_text` | TEXT | No | - | Source English text |
| `target_language_iso3` | VARCHAR(3) | No | - | ISO 639-3 language code |
| `translated_text` | TEXT | No | - | Cached translation result |
| `created_at_timestamp` | TIMESTAMP | No | NOW | When cached |

### Usage Notes
- Populated automatically by `translator()` function on OpenAI API calls
- Queried first before making API calls (cache-first pattern)
- No `updated_at` — translations are immutable once cached
- Hash prevents duplicate entries for same text+language combo

---

## `support_tickets` (Optional Module)

Support ticket system. Only created if Support module is enabled.

```sql
CREATE TYPE ticket_status_enum AS ENUM (
    'ticket_open',
    'ticket_in_progress',
    'ticket_resolved',
    'ticket_closed'
);

CREATE TABLE support_tickets (
    id                              BIGSERIAL PRIMARY KEY,
    record_unique_identifier        VARCHAR(64) NOT NULL UNIQUE,
    tenant_account_id               BIGINT NOT NULL REFERENCES tenant_accounts(id) ON DELETE CASCADE,
    created_by_member_id            BIGINT NOT NULL REFERENCES platform_members(id) ON DELETE CASCADE,
    ticket_subject_line             VARCHAR(500) NOT NULL,
    ticket_description_body         TEXT NOT NULL,
    ticket_status                   ticket_status_enum NOT NULL DEFAULT 'ticket_open',
    assigned_to_administrator_id    BIGINT NULL REFERENCES platform_members(id) ON DELETE SET NULL,
    created_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at_timestamp            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tickets_by_account 
    ON support_tickets(tenant_account_id);

CREATE INDEX idx_tickets_by_creator 
    ON support_tickets(created_by_member_id);

CREATE INDEX idx_tickets_open 
    ON support_tickets(ticket_status) 
    WHERE ticket_status IN ('ticket_open', 'ticket_in_progress');
```

### Column Details

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGSERIAL | No | auto | Primary key |
| `record_unique_identifier` | VARCHAR(64) | No | auto | External-safe unique hash |
| `tenant_account_id` | BIGINT | No | - | FK → tenant_accounts |
| `created_by_member_id` | BIGINT | No | - | FK → platform_members (creator) |
| `ticket_subject_line` | VARCHAR(500) | No | - | Ticket subject |
| `ticket_description_body` | TEXT | No | - | Full ticket description/body |
| `ticket_status` | ENUM | No | 'ticket_open' | Current status |
| `assigned_to_administrator_id` | BIGINT | Yes | NULL | FK → platform_members (admin) |
| `created_at_timestamp` | TIMESTAMP | No | NOW | Record creation |
| `updated_at_timestamp` | TIMESTAMP | No | NOW | Last update |

---

## Entity Relationship Diagram (Text)

```
platform_members
    │
    ├── 1:N ── one_time_password_tokens
    │
    ├── N:M ── tenant_accounts (via tenant_account_memberships)
    │              │
    │              ├── 1:N ── team_membership_invitations
    │              │
    │              └── 1:N ── support_tickets (optional)
    │
    └── 1:N ── team_membership_invitations (as inviter)

platform_settings (standalone key-value store)

external_service_api_credentials (standalone)

cached_text_translations (standalone - translator cache)
```

---

## Migration Order

Run migrations in this order to satisfy foreign key constraints:

1. `platform_members`
2. `tenant_accounts`
3. `tenant_account_memberships`
4. `one_time_password_tokens`
5. `team_membership_invitations`
6. `platform_settings`
7. `external_service_api_credentials`
8. `cached_text_translations`
9. `support_tickets` (optional)

---

## Notes

- **Soft deletes:** Use `is_soft_deleted` boolean flag, not Laravel's `SoftDeletes` trait
- **Timestamps:** All tables use `*_at_timestamp` suffix for clarity
- **JSONB:** Used for `granted_permission_slugs` to enable PostgreSQL JSON queries
- **Indexes:** Created for common query patterns (lookups by email, active records, etc.)
- **Cascades:** ON DELETE CASCADE for child records, ON DELETE SET NULL for optional FKs
