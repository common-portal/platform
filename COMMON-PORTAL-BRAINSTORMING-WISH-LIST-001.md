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

## Administrator Panel

### Tabs (Core)
| Tab | Purpose |
|-----|---------|
| **Stats** | Platform-wide statistics (total members, total accounts, etc.) |
| **Members** | Search and manage all members across the platform |
| **Accounts** | Search and manage all accounts, with impersonation capability |
| **Global** | Global platform settings |

*(Additional tabs like Sweep Fees, Audit are payment-specific — optional modules)*

### Members Tab
- **Keyword search** — find members by name, email
- **Results list** — rows of member details
- **Member row actions** — view/edit member details, see which accounts they belong to

### Accounts Tab
- **Keyword search** — find accounts by name
- **Results list** — rows of account details with "Manage →" button
- **Manage button** — triggers **Account Impersonation**

### Account Impersonation Feature
When an administrator clicks "Manage →" on an account:

1. **Active Account switches** to the selected account
2. **Admin View banner** appears at top (red): "ADMIN VIEW: Managing account | Exit Admin View"
3. **Sidebar context changes** — admin can now access:
   - Account settings
   - Dashboard
   - Team management
   - Any other account-level menu items
4. **Admin acts as account member** without being an actual member
5. **Admin's own profile preserved** — still logged in as themselves
6. **"Exit Admin View"** link returns to normal administrator context

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

## Login/Register Authentication

Single page at `/login-register` — both Login and "Get Started" link here.

### Email-First Flow
1. User enters email address
2. System checks if email exists in members table
3. If exists → send OTP to email
4. Display OTP input screen (4 or 6 digit PIN)

### OTP (One-Time Password) Rules
| Rule | Description |
|------|-------------|
| **PIN Length** | 4 or 6 digits |
| **Validity Period** | 72 hours |
| **Re-send behavior** | Does NOT invalidate previous OTP |
| **Multiple OTPs** | All pending OTPs remain valid (old PIN may arrive after new) |
| **On success** | Validating one OTP invalidates all other pending OTPs for that member |

### UX Flow
1. Enter email → "Send Code"
2. Check inbox for PIN
3. Enter PIN (4-6 digit input boxes)
4. "Resend Code" option available
5. On valid PIN → logged in, redirected to dashboard

### Why Allow Multiple Valid OTPs?
- Email delivery can be delayed
- User might request re-send before first arrives
- First OTP might arrive after second
- All should work until one succeeds

---

## Notes

- Each directory should have a small README explaining its purpose
- Top-level `COMMON-PORTAL-DIRECTORY-INDEX-001.md` provides overview of all directories
- Subdomain branding handled via Stancl Tenancy package (already installed)
