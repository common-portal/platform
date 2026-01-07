# Common Portal — Brainstorming Wish List

Collected feature requests and ideas for the multi-tenant payment portal.

---

## White-Label Branding

- **Subdomain-based branding** — Clients configure their own third-level domain (e.g., `clientname.commonportal.com`)
- **CNAME/A record support** — System recognizes client domains via hostname lookup
- **Branding settings UI** — Within client account settings, clients can:
  - Set custom subdomain
  - Upload logo
  - Configure brand colors
- **Top-level domain reserved** — Root domain reserved for platform admin/marketing

---

## Data Model

### Core Tables
| Table | Purpose |
|-------|---------|
| `members` | Individual users (login credentials, profile) |
| `accounts` | Client organizations/companies |
| `account_member` | Many-to-many pivot — members can share accounts |

### Members Table — Key Columns
| Column | Type | Purpose |
|--------|------|---------|
| `is_platform_administrator` | boolean | Grants access to platform-wide admin functions |

### Relationships
- A **member** can belong to multiple **accounts**
- An **account** can have multiple **members**
- Members have roles within each account (owner, admin, user, etc.)
- Platform administrators have global access regardless of account

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
Permissions tied to sidebar menu items. Easy to extend.

| Permission | Controls Access To |
|------------|-------------------|
| `account_management` | Account Settings page |
| `dashboard` | Dashboard page |
| `team` | Team management page |

### Design Goals
- **Easy to add menu items** — Adding a new menu item should be straightforward
- **Easy to add permissions** — New permissions automatically appear in Team management UI
- **Role assignment** — Members can be assigned permissions per-account

---

## Excluded (Not Core)

These items are specific to payment portals, not all common portals:
- Wallets
- Transactions
- Developer

*(Can be added later as optional modules)*

---

## Notes

- Each directory should have a small README explaining its purpose
- Top-level `COMMON-PORTAL-DIRECTORY-INDEX-001.md` provides overview of all directories
- Subdomain branding handled via Stancl Tenancy package (already installed)
