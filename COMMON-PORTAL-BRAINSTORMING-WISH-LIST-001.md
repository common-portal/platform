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

## Notes

- Each directory should have a small README explaining its purpose
- Top-level `COMMON-PORTAL-DIRECTORY-INDEX-001.md` provides overview of all directories
- Subdomain branding handled via Stancl Tenancy package (already installed)
