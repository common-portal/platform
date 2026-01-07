# Common Portal — Brainstorming Wish List

Collected feature requests and ideas for the multi-tenant portal platform.

---

## Data Model (Consolidated)

### Table Overview
| Table | Purpose |
|-------|---------|
| `platform_members` | Individual users (login credentials, profile) |
| `tenant_accounts` | Client organizations/companies (personal or business) |
| `tenant_account_memberships` | Relationship — members ↔ accounts with role, permissions, status |
| `one_time_password_tokens` | Authentication OTPs with expiration tracking |
| `team_membership_invitations` | Team invitations with status tracking |
| `support_tickets` | Support ticket system (optional module) |
| `platform_settings` | Global platform configuration (theme, branding, toggles) |
| `external_service_api_credentials` | API keys for translation and other services |

---

### `platform_members` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `login_email_address` | string | Login email (unique) |
| `hashed_login_password` | string | Optional bcrypt hash (nullable — OTP primary) |
| `member_first_name` | string | Member's first name(s) |
| `member_last_name` | string | Member's last name(s) |
| `profile_avatar_image_path` | string | Profile image file path |
| `preferred_language_code` | string | Language code for translation (e.g., "en", "es") |
| `is_platform_administrator` | boolean | Grants platform-wide admin access |
| `email_verified_at_timestamp` | timestamp | When email was verified via OTP |
| `created_at_timestamp` | timestamp | |
| `updated_at_timestamp` | timestamp | |

---

### `tenant_accounts` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `account_display_name` | string | Account display name |
| `account_type` | enum | `personal_individual` or `business_organization` |
| `whitelabel_subdomain_slug` | string | Custom subdomain for white-label (nullable) |
| `branding_logo_image_path` | string | Account logo file path (nullable) |
| `primary_contact_full_name` | string | |
| `primary_contact_email_address` | string | |
| `is_soft_deleted` | boolean | Soft delete flag (default: false) |
| `soft_deleted_at_timestamp` | timestamp | When soft deleted (nullable) |
| `created_at_timestamp` | timestamp | |
| `updated_at_timestamp` | timestamp | |

#### Account Types
| Type | Description |
|------|-------------|
| **personal_individual** | Auto-created on registration, cannot be deleted, one per member |
| **business_organization** | Manually created, can be soft deleted, shareable |

---

### `tenant_account_memberships` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `tenant_account_id` | bigint | FK → tenant_accounts |
| `platform_member_id` | bigint | FK → platform_members |
| `account_membership_role` | enum | `account_owner`, `account_administrator`, `account_team_member` |
| `granted_permission_slugs` | json | Array of permission slugs |
| `membership_status` | enum | `awaiting_acceptance`, `membership_active`, `membership_revoked` |
| `invitation_sent_at_timestamp` | timestamp | When invitation was sent |
| `invitation_accepted_at_timestamp` | timestamp | When member first logged in (nullable) |
| `membership_revoked_at_timestamp` | timestamp | When disabled (nullable) |
| `created_at_timestamp` | timestamp | |
| `updated_at_timestamp` | timestamp | |

#### Membership Status Flow
| Status | Description |
|--------|-------------|
| **awaiting_acceptance** | Invitation sent, member has not yet logged in |
| **membership_active** | Member has accepted and logged in |
| **membership_revoked** | Access revoked, account hidden from member's dropdown |

---

### `one_time_password_tokens` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `platform_member_id` | bigint | FK → platform_members |
| `hashed_verification_code` | string | 4-6 digit PIN (bcrypt hashed) |
| `token_expires_at_timestamp` | timestamp | 72 hours from creation |
| `token_used_at_timestamp` | timestamp | When successfully validated (nullable) |
| `created_at_timestamp` | timestamp | |

#### OTP Rules
- Multiple tokens can be valid simultaneously (re-send doesn't invalidate old)
- On successful validation, all other pending tokens for that member are invalidated
- 72-hour validity period

---

### `team_membership_invitations` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `tenant_account_id` | bigint | FK → tenant_accounts |
| `invited_email_address` | string | Invited email address |
| `invited_by_member_id` | bigint | FK → platform_members (who sent invitation) |
| `invitation_status` | enum | `invitation_pending`, `invitation_accepted`, `invitation_expired` |
| `invitation_last_sent_at_timestamp` | timestamp | Last time invitation email was sent |
| `invitation_accepted_at_timestamp` | timestamp | When accepted (nullable) |
| `created_at_timestamp` | timestamp | |
| `updated_at_timestamp` | timestamp | |

---

### `platform_settings` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `setting_key` | string | Setting key (unique) |
| `setting_value` | text | Setting value (JSON for complex values) |
| `created_at_timestamp` | timestamp | |
| `updated_at_timestamp` | timestamp | |

#### Platform Settings Keys
| Key | Value Type | Purpose |
|-----|------------|---------|
| `platform_display_name` | string | Brand name next to logo |
| `platform_logo_image_path` | string | Logo file path |
| `platform_favicon_image_path` | string | Favicon file path |
| `social_sharing_preview_image_path` | string | Open Graph card image path |
| `social_sharing_meta_description` | string | Default meta description |
| `active_theme_preset_name` | string | Active theme preset name |
| `custom_theme_color_overrides` | json | Custom CSS variable overrides |
| `sidebar_menu_item_visibility_toggles` | json | Toggleable menu items (on/off) |

---

### `external_service_api_credentials` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `external_service_name` | string | e.g., "openai", "grok", "deepl" |
| `encrypted_api_key` | string | Encrypted API key |
| `is_currently_active_service` | boolean | Which service to use |
| `created_at_timestamp` | timestamp | |
| `updated_at_timestamp` | timestamp | |

---

### `support_tickets` Table (Optional Module)
| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `tenant_account_id` | bigint | FK → tenant_accounts |
| `created_by_member_id` | bigint | FK → platform_members (who created) |
| `ticket_subject_line` | string | Ticket subject |
| `ticket_status` | enum | `ticket_open`, `ticket_in_progress`, `ticket_resolved`, `ticket_closed` |
| `assigned_to_administrator_id` | bigint | FK → platform_members (platform admin, nullable) |
| `created_at_timestamp` | timestamp | |
| `updated_at_timestamp` | timestamp | |

---

### Relationships Summary
- A **platform_member** can belong to multiple **tenant_accounts** (via tenant_account_memberships)
- A **tenant_account** can have multiple **platform_members** (via tenant_account_memberships)
- Each platform_member has one **personal_individual** account (auto-created on registration)
- Platform members can create/join multiple **business_organization** accounts
- **is_platform_administrator = true** bypasses all account-level permissions

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
| Sidebar background | `--sidebar-background-color` |
| Sidebar text | `--sidebar-text-color` |
| Sidebar hover | `--sidebar-hover-background-color` |
| Primary color | `--brand-primary-color` |
| Secondary color | `--brand-secondary-color` |
| Success color | `--status-success-color` |
| Warning color | `--status-warning-color` |
| Error color | `--status-error-color` |
| Link color | `--hyperlink-text-color` |
| Button background | `--button-background-color` |
| Button text | `--button-text-color` |

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

## Responsive Design (Mobile-Friendly)

### Breakpoint Behavior
| Screen Size | Sidebar Behavior |
|-------------|------------------|
| **Desktop** (≥992px) | Full sidebar visible, always open |
| **Tablet** (768px–991px) | Sidebar collapsed, expandable on click |
| **Mobile** (<768px) | Sidebar hidden, burger menu icon in header |

### Burger Menu (Mobile)
- **Trigger:** Hamburger icon (☰) appears in top-left when sidebar collapses
- **Behavior:** Tap to slide sidebar in from left as overlay
- **Dismiss:** Tap outside sidebar or tap X to close
- **Content:** Same menu items as desktop sidebar

### Tailwind Breakpoint Reference
| Class Prefix | Min Width |
|--------------|-----------|
| `sm:` | 640px |
| `md:` | 768px |
| `lg:` | 1024px |
| `xl:` | 1280px |

Use `lg:` breakpoint for sidebar collapse (≈992px equivalent).

---

## Action Button UX Patterns

All submit buttons, action buttons, and links that trigger a process (without page navigation) must follow this pattern:

### On Click Behavior
| Step | Action |
|------|--------|
| 1. **Disable** | Button becomes `disabled` immediately on click |
| 2. **Spinner** | Button text replaced with processing spinner icon |
| 3. **Process** | Backend/async function executes |
| 4. **Revert** | On return (success or error), button re-enables and spinner removed |

### Visual States
| State | Appearance |
|-------|------------|
| **Default** | Normal button styling, clickable |
| **Processing** | Disabled, grayed out, spinner icon visible |
| **Success** | Revert to default (optionally flash green briefly) |
| **Error** | Revert to default, show error message nearby |

### Why This Pattern?
- **Prevents double-clicks** — Users can't submit twice
- **Visual feedback** — User knows action is in progress
- **Better UX** — No confusion about whether click registered

### Implementation Notes
- Use a reusable wrapper/component for all action buttons
- Spinner should be inline (replace button text, not separate)
- Timeout fallback: auto-revert after 30s if no response (edge case)

### Applies To
- Form submit buttons
- "Save", "Update", "Delete" buttons
- "Send Invitation", "Resend Code" links
- Any button that triggers an async operation

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
Stored in `tenant_account_memberships.granted_permission_slugs` as JSON array. Tied to sidebar menu items.

| Permission Slug | Controls Access To |
|-----------------|-------------------|
| `can_access_account_settings` | Account Settings page |
| `can_access_account_dashboard` | Dashboard page |
| `can_manage_team_members` | Team management page |
| `can_access_developer_tools` | Developer page (when enabled) |
| `can_access_support_tickets` | Support page (when enabled) |
| `can_view_transaction_history` | Transactions page (when enabled) |
| `can_view_billing_history` | Billing page (when enabled) |

### Permission Logic
1. **Platform toggle** — Is menu item enabled globally? (via `platform_settings.sidebar_menu_item_visibility_toggles`)
2. **Member permission** — Does member have permission in current account? (via `tenant_account_memberships.granted_permission_slugs`)
3. **Show menu item** — Only if BOTH are true

### Per-Member Per-Account
| Scenario | Description |
|----------|-------------|
| **Member in Account A** | Has `["can_access_account_dashboard", "can_manage_team_members"]` |
| **Same Member in Account B** | Has `["can_access_account_dashboard"]` only |
| **Result** | Different menu items visible depending on active account |

### Platform Administrator Override
- `is_platform_administrator = true` **bypasses all permission checks**
- Full access to every menu item in every account
- Separate from account-level roles

### Team Page Actions
| Action | Who Can Do It | Description |
|--------|---------------|-------------|
| **Invite** | `can_manage_team_members` | Send invitation to email |
| **Resend** | `can_manage_team_members` | Resend pending invitation |
| **Edit Permissions** | `can_manage_team_members` | Modify member's permission array |
| **Disable** | `can_manage_team_members` | Set status to `membership_revoked` (cannot self-disable) |
| **Re-enable** | `can_manage_team_members` | Set status back to `membership_active` |

*(See Data Model → `tenant_account_memberships` and `team_membership_invitations` tables for status tracking)*

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
> **🔴 IMPORTANT:** Follow `COMMON-PORTAL-MAILER-CODE-001.md` exactly for implementation.

| Aspect | Description |
|--------|-------------|
| **Implementation** | See `COMMON-PORTAL-MAILER-CODE-001.md` |
| **Core Function** | `send_platform_email($to, $subject, $html, ...)` |
| **Gateway** | MX.NSDB.COM centralized SMTP routing |
| **Response** | `['success' => bool, 'message' => string]` |

### Email Use Cases
| Use Case | When Sent |
|----------|-----------|
| **OTP Code** | Login/register verification |
| **Team Invitation** | Member invited to account |
| **Password Reset** | Member requests password reset |
| **Welcome Email** | After email verification |

### Translator Service

> **🔴 IMPORTANT:** Follow `COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md` exactly for implementation.

| Aspect | Description |
|--------|-------------|
| **Implementation** | See `COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md` |
| **Core Functions** | `translator($text, $lang, $pdo)`, `language_selector()`, `__t($text)` |
| **Language Storage** | Cookie-based with IP auto-detection on first visit |
| **Member Preference** | Synced to `platform_members.preferred_language_code` |
| **Caching** | PostgreSQL `translations` table with OpenAI fallback |
| **Languages** | 100+ supported, top 10 shown first in selector |

### Language Selector Locations
| Location | Behavior |
|----------|----------|
| **Homepage** (sticky footer) | Available before login |
| **Login/Register page** | Sticky footer selector |
| **Sidebar menu** (bottom) | For logged-in members, syncs to profile |

### Implementation Pattern
```php
<?= __t("Welcome to the platform") ?>
<?= language_selector() ?>
```

---

## Technical Notes

### 🔴 HIGH PRIORITY: Naming Convention Policy

**This is a framework-wide policy for all code contributions.**

All table names, column names, function names, method names, variable names, and parameters MUST be:

| Principle | Description |
|-----------|-------------|
| **Highly Descriptive** | Names should be self-documenting |
| **No Abbreviations** | Write `button` not `btn`, `background` not `bg` |
| **No Generic Names** | Avoid `data`, `info`, `item`, `value` without context |
| **Action-Based Permissions** | Use `can_access_*`, `can_manage_*`, `can_view_*` |
| **Context-Prefixed** | Include table/entity context when helpful |
| **LLM-Readable** | An AI should understand purpose without tracing code |
| **Human-Readable** | A new developer should understand without documentation |

#### Examples
| ❌ Avoid | ✅ Prefer |
|----------|----------|
| `$user` | `$current_platform_member` |
| `$acct` | `$active_tenant_account` |
| `getData()` | `fetch_member_account_memberships()` |
| `process()` | `validate_one_time_password_token()` |
| `status` | `membership_status` or `invitation_status` |
| `type` | `account_type` or `ticket_status` |

#### Rationale
This is an **open-source project** intended to be:
1. Easily understood by LLMs assisting with development
2. Self-documenting without extensive comments
3. Readable by developers unfamiliar with the codebase
4. Maintainable long-term without tribal knowledge

---

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
- Soft deletes via `is_soft_deleted` flag (not Laravel's SoftDeletes trait)

---

## Document Cross-References

| Document | Purpose |
|----------|---------|
| `COMMON-PORTAL-FRAMEWORK-README-001.md` | Project setup instructions |
| `COMMON-PORTAL-DEVELOPMENT-ROADMAP-001.md` | Phase-by-phase development plan |
| `COMMON-PORTAL-DIRECTORY-INDEX-001.md` | Directory structure overview |
| `COMMON-PORTAL-DATABASE-SCHEMA-001.md` | PostgreSQL table definitions |
| `COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md` | 🔴 Translator framework (follow exactly) |
| `COMMON-PORTAL-MAILER-CODE-001.md` | 🔴 Mailer framework (follow exactly) |
