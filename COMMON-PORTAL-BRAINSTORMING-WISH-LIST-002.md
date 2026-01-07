# Common Portal — Brainstorming Wish List

Collected feature requests and ideas for the multi-tenant portal platform.

---

## Data Model (Consolidated)

### Table Overview
| Table | Purpose |
|-------|---------|
| `members` | Individual users (login credentials, profile) |
| `accounts` | Client organizations/companies (personal or business) |
| `account_member` | Pivot — members ↔ accounts with role, permissions, status |
| `otps` | One-time passwords for authentication |
| `invitations` | Team invitations with status tracking |
| `support_tickets` | Support ticket system (optional module) |
| `platform_settings` | Global platform configuration (theme, branding, toggles) |
| `api_keys` | External service API keys (translation, etc.) |

---

### `members` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `email` | string | Login email (unique) |
| `password` | string | Optional bcrypt hash (nullable — OTP primary) |
| `first_name` | string | Member's first name(s) |
| `last_name` | string | Member's last name(s) |
| `avatar` | string | Profile image path |
| `preferred_language` | string | Language code for translation |
| `is_platform_administrator` | boolean | Grants platform-wide admin access |
| `email_verified_at` | timestamp | When email was verified via OTP |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

### `accounts` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `name` | string | Account display name |
| `account_type` | enum | `personal` or `business` |
| `subdomain` | string | Custom subdomain for white-label (nullable) |
| `logo` | string | Account logo path (nullable) |
| `primary_contact_name` | string | |
| `primary_contact_email` | string | |
| `is_deleted` | boolean | Soft delete flag (default: false) |
| `deleted_at` | timestamp | When soft deleted (nullable) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Account Types
| Type | Description |
|------|-------------|
| **Personal** | Auto-created on registration, cannot be deleted, one per member |
| **Business** | Manually created, can be soft deleted, shareable |

---

### `account_member` Pivot Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `account_id` | bigint | FK → accounts |
| `member_id` | bigint | FK → members |
| `role` | enum | `owner`, `admin`, `member` |
| `permissions` | json | Array of permission slugs (e.g., `["dashboard", "team"]`) |
| `status` | enum | `pending`, `active`, `disabled` |
| `invited_at` | timestamp | When invitation was sent |
| `accepted_at` | timestamp | When member first logged in (nullable) |
| `disabled_at` | timestamp | When disabled (nullable) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Pivot Status Flow
| Status | Description |
|--------|-------------|
| **Pending** | Invitation sent, member has not yet logged in |
| **Active** | Member has accepted and logged in |
| **Disabled** | Access revoked, account hidden from member's dropdown |

---

### `otps` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `member_id` | bigint | FK → members |
| `code` | string | 4-6 digit PIN (hashed) |
| `expires_at` | timestamp | 72 hours from creation |
| `used_at` | timestamp | When successfully validated (nullable) |
| `created_at` | timestamp | |

#### OTP Rules
- Multiple OTPs can be valid simultaneously (re-send doesn't invalidate old)
- On successful validation, all other pending OTPs for that member are invalidated
- 72-hour validity period

---

### `invitations` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `account_id` | bigint | FK → accounts |
| `email` | string | Invited email address |
| `invited_by` | bigint | FK → members (who sent invitation) |
| `status` | enum | `pending`, `accepted`, `expired` |
| `last_sent_at` | timestamp | Last time invitation email was sent |
| `accepted_at` | timestamp | When accepted (nullable) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

### `platform_settings` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `key` | string | Setting key (unique) |
| `value` | text | Setting value (JSON for complex values) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### Platform Settings Keys
| Key | Value Type | Purpose |
|-----|------------|---------|
| `platform_name` | string | Brand name next to logo |
| `platform_logo` | string | Logo file path |
| `favicon` | string | Favicon file path |
| `meta_image` | string | Open Graph card image path |
| `meta_description` | string | Default meta description |
| `theme_preset` | string | Active theme preset name |
| `theme_colors` | json | Custom CSS variable overrides |
| `menu_items` | json | Toggleable menu items (on/off) |

---

### `api_keys` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `service_name` | string | e.g., "openai", "grok", "deepl" |
| `api_key` | string | Encrypted API key |
| `is_active` | boolean | Which service to use |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

### `support_tickets` Table (Optional Module)
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `account_id` | bigint | FK → accounts |
| `member_id` | bigint | FK → members (who created) |
| `subject` | string | Ticket subject |
| `status` | enum | `open`, `in_progress`, `resolved`, `closed` |
| `assigned_to` | bigint | FK → members (platform admin, nullable) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

### Relationships Summary
- A **member** can belong to multiple **accounts** (via pivot)
- An **account** can have multiple **members** (via pivot)
- Each member has one **personal account** (auto-created)
- Members can create/join multiple **business accounts**
- **Platform administrators** bypass all account-level permissions

---

## Branding Hierarchy

Two levels of branding in the system:

### Platform-Level Branding (Admin → Platform Theme)
Controlled by platform administrators. Applies globally.

| Setting | Description |
|---------|-------------|
| Platform logo | Upper-left sidebar logo |
| Platform name | Brand text next to logo |
| Theme colors | CSS variables for entire platform |
| Favicon | Browser tab icon |
| Meta card | Open Graph image/description |

### Account-Level Branding (Account Settings)
Controlled by account owners/admins. Applies to that account's subdomain.

| Setting | Description |
|---------|-------------|
| Subdomain | Custom third-level domain (e.g., `clientname.commonportal.com`) |
| Account logo | Overrides platform logo when on subdomain |
| CNAME/A record | Custom domain support |

**Hierarchy:** Account branding overrides platform branding when viewing via subdomain.

---

## Registration & Account Flow

### New Member Registration
1. Email entered at `/login-register`
2. **If email NOT exists:**
   - Create `members` record
   - Create `accounts` record (type: `personal`)
   - Create `account_member` pivot (role: `owner`, status: `active`)
   - Send OTP to email
3. **If email exists:**
   - Send OTP to email (login flow)

### Add Business Account
- Sidebar link: **"+ Add Business Account"** (below account dropdown)
- Creates new `accounts` record (type: `business`)
- Creates `account_member` pivot (role: `owner`)
- Redirects to Account Settings to set name

### Soft Delete (Business Only)
- Located in Account Settings → **Danger Zone**
- Modal: "This cannot be reversed. Affects all team members."
- Sets `is_deleted = true` — hides from all members' dropdowns
- **Personal accounts cannot be deleted**

---

## Directory Structure

### Application Directories
| Path | Purpose |
|------|---------|
| `/member` | Member-specific functionality (profile, auth, personal settings) |
| `/account` | Account-level functionality (team, billing, branding settings) |
| `/administrator` | Platform-wide admin functions (requires `is_platform_administrator = true`) |
| `/gateway` | Public-facing endpoints for external integrations |
| `/gateway/api` | Public API — incoming calls from **clients** |
| `/gateway/webhooks` | Public webhooks — incoming calls from **partners** (payment processors, etc.) |

### Administrator Access
- Sidebar menu item "Administrator" only visible if member has `is_platform_administrator = true`
- Platform admins can manage all accounts, members, and system settings
- Separate from account-level admin roles

---

## Administrator Panel

### Tabs (Core)
| Tab | Purpose |
|-----|---------|
| **Stats** | Platform-wide statistics (total members, total accounts, etc.) |
| **Members** | Search and manage all members across the platform |
| **Accounts** | Search and manage all accounts, with impersonation capability |
| **Global** | Global platform settings |
| **Platform Theme** | Define colors, styles, and select theme presets |
| **Menu Items** | Toggle sidebar menu items on/off platform-wide |

*(Additional tabs like Sweep Fees, Audit are payment-specific — optional modules)*

### Menu Items Tab
Platform administrator can enable/disable sidebar menu items globally.

#### Toggleable Menu Items
| Menu Item | Default | Description |
|-----------|---------|-------------|
| **Account** | ✅ On | Account settings page |
| **Dashboard** | ✅ On | Account-level statistics |
| **Team** | ✅ On | Team member management |
| **Developer** | ⬜ Off | API documentation, integration tools |
| **Support** | ⬜ Off | Support ticket generator |
| **Transactions** | ⬜ Off | Transaction history page |
| **Billing** | ⬜ Off | Billing history page |

#### Developer Sub-tabs (when enabled)
- API Documentation
- Integration guides
- API keys management

#### Support (when enabled)
Role-based access within the Support menu item:

| Role | Access |
|------|--------|
| **Regular Member** | Create tickets, view own ticket history, track status |
| **Platform Administrator** | All above + manage ALL tickets (reply, close, assign) |

No separate admin tab — platform admins manage tickets directly in Support sidebar item.

#### How It Works
- Checkbox toggles menu item on/off for entire platform
- When off → menu item hidden from all accounts
- Permissions still apply (member must have permission to see enabled items)

### Platform Theme Tab
Full theming control for platform administrators.

#### Theme Presets (~6 options)
| Preset | Description |
|--------|-------------|
| Dark Mode | Dark backgrounds, light text |
| Light Mode | Light backgrounds, dark text |
| Grayscale | Neutral gray tones |
| Dark Blue | Professional blue theme |
| Light Orange | Warm orange accents |
| Custom | Start from scratch |

#### Customizable Elements
| Element | CSS Variable |
|---------|-------------|
| Sidebar background | `--sidebar-bg` |
| Sidebar text | `--sidebar-text` |
| Sidebar hover | `--sidebar-hover` |
| Primary color | `--brand-primary` |
| Secondary color | `--brand-secondary` |
| Success color | `--color-success` |
| Warning color | `--color-warning` |
| Error color | `--color-error` |
| Link color | `--link-color` |
| Button background | `--btn-bg` |
| Button text | `--btn-text` |

#### Workflow
1. Select a preset theme as starting point
2. Refine individual colors as needed
3. Save → applies platform-wide

#### Platform Branding
| Setting | Purpose |
|---------|---------|
| **Platform Logo** | Logo image in upper-left corner of sidebar |
| **Platform Name** | Brand name text displayed next to logo (e.g., "TheCashier.com") |

#### Favicon & Meta (Social Sharing)
| Upload | Purpose |
|--------|---------|
| **Favicon** | Browser tab icon, shared across all pages |
| **Meta Card Image** | Large preview image for social media sharing (Open Graph) |
| **Meta Description** | Default description text for link previews |

These apply globally to all pages — when any link is shared on social media, the card preview shows:
- Platform favicon
- Uploaded card image
- Defined description text

### Members Tab
- **Keyword search** — find members by name, email
- **Results list** — rows of member details
- **Member row actions** — view/edit member details, see which accounts they belong to

#### Admin Member Editing
Platform administrators can edit for any member:
| Field | Action |
|-------|--------|
| **Login Email** | Change member's email address |
| **Login Password** | Reset/set member's password (stored as bcrypt hash) |

### Accounts Tab
- **Keyword search** — find accounts by name
- **Results list** — rows of account details with "Manage →" button
- **Manage button** — triggers **Account Impersonation**

### Account Impersonation Feature
When an administrator clicks "Manage →" on an account:

1. **Active Account switches** to the selected account
2. **Red Admin Banner** appears at very top of screen (above everything else):
   - Text: "ADMIN VIEW: Managing account | Exit Admin View"
   - Shows which account is being impersonated
   - Sticky/fixed position — always visible while impersonating
3. **Sidebar context changes** — admin can now access:
   - Account settings
   - Dashboard
   - Team management
   - Any other account-level menu items
4. **Admin acts as account member** without being an actual member
5. **Admin's own profile preserved** — still logged in as themselves
6. **"Exit Admin View"** link in red banner → returns to admin's default account

### Design Goals
- Admins can troubleshoot/support any account without needing to be invited
- Clear visual indicator (red banner) that admin is in impersonation mode
- All actions logged for audit trail

---

## Sidebar Menu Structure

From top to bottom:

### 1. Platform Logo
- Displays at very top of sidebar
- White-labeled per tenant/subdomain

### 2. Administrator (conditional)
- Only visible if `is_platform_administrator = true`
- Links to platform-wide admin panel

### 3. Active Account Selector
- Dropdown select showing all accounts member has access to
- Displays current account name
- Switching accounts changes context for menu below

### 4. Account Menu Items

| Menu Item | Page | Description |
|-----------|------|-------------|
| **Account** | `account_settings.php` | Logo upload, account name, primary contact name, primary contact email |
| **Dashboard** | `dashboard.php` | Empty/generic page for account-level statistics |
| **Team** | `team.php` | Invite members, assign account-level permissions |

### 5. Member Profile
- Links to `member_settings.php`
- **3 Tabs:**
  1. **Profile** — Avatar/profile image upload, First Name(s), Last Name(s)
  2. **Login Email** — Edit login email address
  3. **Login Password** — Change password

### 6. Exit
- Links to `/login-register`
- Logs out and returns to auth page

### 7. Language Selector (bottom)
- Dropdown for member's preferred language translation
- Persists across sessions

---

## Permissions System

### Account-Level Permissions
Stored in `account_member.permissions` as JSON array. Tied to sidebar menu items.

| Permission Slug | Controls Access To |
|-----------------|-------------------|
| `account_management` | Account Settings page |
| `dashboard` | Dashboard page |
| `team` | Team management page |
| `developer` | Developer page (when enabled) |
| `support` | Support page (when enabled) |
| `transactions` | Transactions page (when enabled) |
| `billing` | Billing page (when enabled) |

### Permission Logic
1. **Platform toggle** — Is menu item enabled globally? (via `platform_settings.menu_items`)
2. **Member permission** — Does member have permission in current account? (via `account_member.permissions`)
3. **Show menu item** — Only if BOTH are true

### Per-Member Per-Account
| Scenario | Description |
|----------|-------------|
| **Member in Account A** | Has `["dashboard", "team"]` permissions |
| **Same Member in Account B** | Has `["dashboard"]` only |
| **Result** | Different menu items visible depending on active account |

### Platform Administrator Override
- `is_platform_administrator = true` **bypasses all permission checks**
- Full access to every menu item in every account
- Separate from account-level roles

### Team Page Actions
| Action | Who Can Do It | Description |
|--------|---------------|-------------|
| **Invite** | `team` permission | Send invitation to email |
| **Resend** | `team` permission | Resend pending invitation |
| **Edit Permissions** | `team` permission | Modify member's permission array |
| **Disable** | `team` permission | Set status to `disabled` (cannot self-disable) |
| **Re-enable** | `team` permission | Set status back to `active` |

*(See Data Model → `account_member` and `invitations` tables for status tracking)*

---

## Optional Modules

These features can be toggled on/off by platform administrators via **Menu Items** tab.

### Core Modules (Default: On)
| Module | Description |
|--------|-------------|
| **Account** | Account settings page |
| **Dashboard** | Account-level statistics |
| **Team** | Team member management |

### Optional Modules (Default: Off)
| Module | Description |
|--------|-------------|
| **Developer** | API documentation, integration guides, API keys |
| **Support** | Ticket system (role-based: members create, admins manage) |
| **Transactions** | Transaction history |
| **Billing** | Billing history |

### Payment-Specific (Not in Base Platform)
These are excluded from the common portal base but can be added for payment-specific implementations:
- Wallets
- Payment processing integrations
- Sweep fees / audit logs

---

## Homepage Structure

Public landing page at root domain (e.g., `commonportal.com`).

### Header (Sticky)
| Position | Element |
|----------|---------|
| **Left** | Platform logo |
| **Center** | Contact link |
| **Right** | Login link + "Get Started" button |

### Footer (Sticky)
| Position | Element |
|----------|---------|
| **Left** | Language selector dropdown |
| **Right** | "Powered by NSDB Common Portal" |

### Mainframe (Body)
- Default: simple, blank layout
- Placeholder content: "NSDB Common Portal" branding
- White-label ready — tenants can customize content later

---

## Authentication UX

Single page at `/login-register` — both Login and "Get Started" link here.

*(See Data Model → `otps` table for OTP rules and `members` table for password storage)*

### Login Page Layout
| Element | Description |
|---------|-------------|
| Email input | Primary input field |
| "Send Code" button | Triggers OTP flow |
| OTP input boxes | 4-6 digit PIN entry (appears after email submitted) |
| "Resend Code" link | Sends new OTP without invalidating old |
| "Fast access with password" | Link at bottom for password login |

### UX Flow (OTP — Primary)
1. Enter email → "Send Code"
2. Check inbox for PIN
3. Enter PIN in input boxes
4. On valid PIN → logged in, redirected to personal account dashboard

### UX Flow (Password — Optional)
1. Click "Fast access with password" at bottom
2. Enter email + password
3. On valid credentials → logged in
4. Forgot password? → Use OTP flow to get in, reset password inside

### Why OTP-Primary?
- No password to remember by default
- More secure (no password to leak)
- Password is convenience feature, not requirement

### Password Security (PCI DSS Compliance)
| Requirement | Implementation |
|-------------|----------------|
| **Hashing** | bcrypt (Laravel default) |
| **Storage** | One-way hash only, never plaintext |
| **PCI DSS 8.3.2** | Strong cryptography ✓ |
| **Laravel** | `Hash::make()` / `Hash::check()` |

---

## System Services

### Mailer Service
Central function for all outgoing emails (OTP, invitations, notifications).

| Aspect | Description |
|--------|-------------|
| **Purpose** | Single point for all SMTP/email sending |
| **Config** | `config/mail.php` + `.env` variables |
| **Options** | SendMail, Mailgun, SendGrid, Amazon SES, etc. |

Laravel handles this natively — ensure all emails route through Mail facade.

### Translator Service
Wraps all UI text for real-time translation.

| Aspect | Description |
|--------|-------------|
| **Purpose** | Translate text based on `members.preferred_language` |
| **Function** | Wrapper function around every text string |
| **API Support** | OpenAI, Grok, DeepL, or other translation APIs |
| **Config** | See Data Model → `api_keys` table |

---

## Technical Notes

### Laravel Packages (Already Installed)
| Package | Purpose |
|---------|---------|
| **Jetstream** | Authentication scaffolding |
| **Spatie Permission** | Role/permission management |
| **Stancl Tenancy** | Multi-tenant subdomain handling |

### File Organization
- Each controller directory has a `README.md` explaining purpose
- Top-level `COMMON-PORTAL-DIRECTORY-INDEX-001.md` provides full overview

### Database
- PostgreSQL (managed or local Docker)
- Migrations in `src/database/migrations/`
- Soft deletes via `is_deleted` flag (not Laravel's `deleted_at` trait)

---

## Document Cross-References

| Document | Purpose |
|----------|---------|
| `COMMON-PORTAL-FRAMEWORK-README-001.md` | Project setup instructions |
| `COMMON-PORTAL-DEVELOPMENT-ROADMAP-001.md` | Phase-by-phase development plan |
| `COMMON-PORTAL-DIRECTORY-INDEX-001.md` | Directory structure overview |
| `COMMON-PORTAL-DATABASE-SCHEMA-001.md` | Database schema (to be created) |
