# Common Portal Platform — Development Roadmap

Phase-by-phase implementation plan aligned with `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md`.

> **📋 Requirements Reference:** See `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md` for full feature details.

---

## Phase 0: Project Setup
**Status:** ✅ Complete

- [x] Laravel + Jetstream + packages installed
- [x] Docker containers configured
- [x] PostgreSQL database ready
- [x] Development environment verified

---

## Phase 1: Database Schema & Models
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Data Model (Consolidated)

### Tables Created
| Table | Migration | Model |
|-------|-----------|-------|
| `platform_members` | ✅ `2026_01_07_100001` | ✅ `PlatformMember.php` |
| `tenant_accounts` | ✅ `2026_01_07_100002` | ✅ `TenantAccount.php` |
| `tenant_account_memberships` | ✅ `2026_01_07_100003` | ✅ `TenantAccountMembership.php` |
| `one_time_password_tokens` | ✅ `2026_01_07_100004` | ✅ `OneTimePasswordToken.php` |
| `team_membership_invitations` | ✅ `2026_01_07_100005` | ✅ `TeamMembershipInvitation.php` |
| `platform_settings` | ✅ `2026_01_07_100006` | ✅ `PlatformSetting.php` |
| `external_service_api_credentials` | ✅ `2026_01_07_100007` | ✅ `ExternalServiceApiCredential.php` |
| `cached_text_translations` | ✅ `2026_01_07_100008` | ✅ `CachedTextTranslation.php` |
| `support_tickets` | ✅ `2026_01_07_100009` | ✅ `SupportTicket.php` |

### Completed Steps
- [x] Create migrations for all 9 tables
- [x] Create Eloquent models with relationships
- [x] Create PostgreSQL enums: `account_type_enum`, `membership_status_enum`, `account_membership_role_enum`, `invitation_status_enum`, `ticket_status_enum`, `ticket_priority_enum`
- [x] Seed `platform_settings` with defaults (PlatformSettingsSeeder)
- [x] Implement Dual-ID Schema Pattern (`id` + `record_unique_identifier`)
- [x] Create `HasRecordUniqueIdentifier` trait for auto-generation

---

## Phase 2: Core Layout & Branding
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Branding Hierarchy, Homepage Structure

### Completed Steps
- [x] Create master layout with sidebar (`layouts/platform.blade.php`)
- [x] Implement CSS variables for theming (`resources/css/theme.css`)
- [x] Build homepage with sticky header/footer (`pages/homepage.blade.php`)
- [x] Add language selector to sidebar (12 languages)
- [x] Implement responsive design (sidebar collapse, burger menu)
- [x] Create ViewComposerServiceProvider for injecting platform settings
- [x] Define all routes for Phase 2 pages

### Key Deliverables
| Component | File | Status |
|-----------|------|--------|
| Master layout | `layouts/platform.blade.php` | ✅ |
| Sidebar menu | `components/sidebar-menu.blade.php` | ✅ |
| Language selector | `components/language-selector.blade.php` | ✅ |
| Action button | `components/action-button.blade.php` | ✅ |
| CSS variables | `resources/css/theme.css` | ✅ |
| Homepage | `pages/homepage.blade.php` | ✅ |
| Login/Register | `pages/login-register.blade.php` | ✅ (placeholder) |
| Account pages | `pages/account/*.blade.php` | ✅ (placeholders) |
| Member settings | `pages/member/settings.blade.php` | ✅ (placeholder) |
| Admin panel | `pages/administrator/index.blade.php` | ✅ (placeholder) |
| View Composer | `Providers/ViewComposerServiceProvider.php` | ✅ |
| Routes | `routes/web.php` | ✅ |

---

## Phase 3: Authentication (OTP-Primary)
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Authentication UX, Registration & Account Flow

### Completed Steps
- [x] Build `/login-register` page (combined login/register)
- [x] Implement OTP flow as primary authentication
- [x] Implement password login as optional secondary
- [x] Auto-create personal account on registration
- [x] Integrate mailer for OTP delivery (SMTP via Laravel Mail)

### Key Deliverables
| Component | File | Status |
|-----------|------|--------|
| OTP Auth Controller | `Controllers/Auth/OtpAuthController.php` | ✅ |
| Platform Mailer Service | `Services/PlatformMailerService.php` | ✅ |
| Login/Register Page | `pages/login-register.blade.php` | ✅ |
| OTP Verify Page | `pages/otp-verify.blade.php` | ✅ |

### OTP Flow (Implemented)
1. Email entered → check if member exists
2. **New member:** Create member + personal account + send OTP
3. **Existing member:** Send OTP
4. OTP validated → logged in

---

## Phase 4: Account System
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Registration & Account Flow, Account Types

### Completed Steps
- [x] Implement personal account (auto-created, cannot delete)
- [x] Implement business account creation ("+ Add Business Account")
- [x] Build account switcher dropdown in sidebar
- [x] Implement soft delete for business accounts (Danger Zone)
- [x] Account-level branding (logo, subdomain)

### Key Deliverables
| Component | File | Status |
|-----------|------|--------|
| Account Controller | `Controllers/AccountController.php` | ✅ |
| Account Settings Page | `pages/account/settings.blade.php` | ✅ |
| Create Account Page | `pages/account/create.blade.php` | ✅ |
| Account Switcher | `components/sidebar-menu.blade.php` | ✅ |

### Account Types (Implemented)
| Type | Behavior |
|------|----------|
| `personal_individual` | Auto-created, one per member, cannot delete |
| `business_organization` | Manual creation, shareable, can soft delete |

---

## Phase 5: Permissions & Sidebar
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Permissions System, Sidebar Menu

### Completed Steps
- [x] Implement permission slugs in `tenant_account_memberships.granted_permission_slugs`
- [x] Build permission-based sidebar visibility
- [x] Implement platform toggle for menu items (`platform_settings.sidebar_menu_item_visibility_toggles`)
- [x] Platform administrator override (`is_platform_administrator = true`)
- [x] Self-protection (cannot remove own team management access)

### Key Deliverables
| Component | File | Status |
|-----------|------|--------|
| Permission Helpers | `Models/TenantAccountMembership.php` | ✅ |
| Sidebar Visibility | `Providers/ViewComposerServiceProvider.php` | ✅ |

### Permission Slugs (Implemented)
| Slug | Controls |
|------|----------|
| `can_access_account_settings` | Account Settings page |
| `can_access_account_dashboard` | Dashboard page |
| `can_manage_team_members` | Team management page |
| `can_access_developer_tools` | Developer page |
| `can_access_support_tickets` | Support page |
| `can_view_transaction_history` | Transactions page |
| `can_view_billing_history` | Billing page |

---

## Phase 6: Team Management & Invitations
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Team Page Actions, `team_membership_invitations` Table

### Completed Steps
- [x] Build Team page with member list
- [x] Implement invitation flow (email → accept → membership)
- [x] Edit member permissions
- [x] Disable/re-enable members
- [x] Resend invitation functionality
- [x] Cancel invitation functionality
- [x] Pending invitation redirect after login

### Key Deliverables
| Component | File | Status |
|-----------|------|--------|
| Team Controller | `Controllers/TeamController.php` | ✅ |
| Invitation Controller | `Controllers/InvitationController.php` | ✅ |
| Team Page | `pages/account/team.blade.php` | ✅ |
| Team Invite Page | `pages/account/team-invite.blade.php` | ✅ |
| Accept Invitation Page | `pages/invitation-accept.blade.php` | ✅ |
| Migration | `invited_permission_slugs` column | ✅ |

### Team Actions (Implemented)
| Action | Permission Required |
|--------|---------------------|
| Invite | `can_manage_team_members` |
| Resend | `can_manage_team_members` |
| Edit Permissions | `can_manage_team_members` |
| Disable/Re-enable | `can_manage_team_members` |

---

## Phase 7: Member Settings
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Sidebar Menu → Member Profile

### Completed Steps
- [x] Profile tab (first name, last name)
- [x] Login Email tab (change email with OTP verification)
- [x] Login Password tab (set, update, remove password)
- [x] Language preference (synced to session)

### Key Deliverables
| Component | File | Status |
|-----------|------|--------|
| Member Controller | `Controllers/MemberController.php` | ✅ |
| Member Settings Page | `pages/member/settings.blade.php` | ✅ |
| Email Verify Page | `pages/member/verify-email.blade.php` | ✅ |

---

## Phase 8: Platform Administrator
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Administrator Access, Platform-Level Branding

### Completed Steps
- [x] Administrator sidebar section (visible only if `is_platform_administrator = true`)
- [x] Dashboard with platform stats
- [x] Platform Theme page (name, description, theme preset)
- [x] Menu Items toggle page
- [x] Platform Members management (toggle admin, impersonate)
- [x] Accounts list with impersonation
- [x] Impersonation mode with exit functionality

### Key Deliverables
| Component | File | Status |
|-----------|------|--------|
| Admin Controller | `Controllers/AdminController.php` | ✅ |
| Admin Dashboard | `pages/administrator/index.blade.php` | ✅ |
| Members Page | `pages/administrator/members.blade.php` | ✅ |
| Accounts Page | `pages/administrator/accounts.blade.php` | ✅ |
| Theme Page | `pages/administrator/theme.blade.php` | ✅ |
| Menu Items Page | `pages/administrator/menu-items.blade.php` | ✅ |

### Admin Features (Implemented)
| Feature | Function |
|---------|----------|
| Platform Stats | Members, accounts, invitations count |
| Theme Settings | Platform name, description, dark/light theme |
| Menu Items | Toggle sidebar feature visibility |
| Member Management | Toggle admin status, impersonate users |
| Account Management | View all accounts, impersonate accounts |

---

## Phase 9: Multi-Tenant Subdomains
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Branding Hierarchy → Account-Level Branding

### Completed Steps
- [x] Subdomain detection middleware (no Stancl Tenancy needed - KISS)
- [x] Subdomain routing (`client.common-portal.nsdb.com`)
- [x] Account branding overrides platform branding on subdomain
- [x] Subdomain management in account settings (business accounts only)
- [x] Reserved subdomain validation
- [x] Configurable base domain via `APP_BASE_DOMAIN`

### Key Deliverables
| Component | File | Status |
|-----------|------|--------|
| Subdomain Middleware | `Middleware/ResolveSubdomainTenant.php` | ✅ |
| Branding Override | `Providers/ViewComposerServiceProvider.php` | ✅ |
| Subdomain Field | `pages/account/settings.blade.php` | ✅ |
| Config | `config/app.php` → `base_domain` | ✅ |

### Subdomain Flow
```
client.common-portal.nsdb.com
  → Middleware extracts "client"
  → Finds TenantAccount with whitelabel_subdomain_slug
  → Applies branding overrides (name, logo)
```

---

## Phase 10: Optional Modules
**Status:** ✅ Complete

> **Reference:** WISH-LIST-003 → Optional Modules

### Modules Implemented
| Module | Default | Description |
|--------|---------|-------------|
| Developer | Off | API docs, keys, webhooks |
| Support | Off | Ticket system (create, view, list) |
| Transactions | Off | Transaction history |
| Billing | Off | Billing/invoice history |

### Completed Steps
- [x] Create ModuleController with all module methods
- [x] Developer module: API docs page, keys placeholder, webhooks placeholder
- [x] Support module: ticket list, create, view pages
- [x] Transactions module: transaction history page
- [x] Billing module: invoice history page
- [x] Add module routes under `/modules/*`

### Key Deliverables
| File | Purpose |
|------|---------|
| `app/Http/Controllers/ModuleController.php` | Controller for all optional modules |
| `resources/views/pages/modules/developer.blade.php` | Developer tools page |
| `resources/views/pages/modules/support-index.blade.php` | Support tickets list |
| `resources/views/pages/modules/support-create.blade.php` | Create ticket form |
| `resources/views/pages/modules/support-show.blade.php` | View single ticket |
| `resources/views/pages/modules/transactions.blade.php` | Transaction history |
| `resources/views/pages/modules/billing.blade.php` | Billing/invoice history |

---

## Phase 11: Production Deployment
**Status:** ⬜ Not Started

### Steps
- [ ] Managed PostgreSQL setup
- [ ] Production environment configuration
- [ ] Wildcard DNS configuration
- [ ] SSL certificates
- [ ] CI/CD pipeline

---

## Commands Reference

```bash
make setup     # Initial Laravel installation
make up        # Start containers
make down      # Stop containers
make migrate   # Run database migrations
make fresh     # Fresh migrate + seed
make shell     # Shell into app container
make test      # Run tests
make logs      # View container logs
```

---

## Progress Tracking

| Phase | Description | Status |
|-------|-------------|--------|
| 0 | Project Setup | ✅ |
| 1 | Database Schema & Models | ✅ |
| 2 | Core Layout & Branding | ✅ |
| 3 | Authentication (OTP-Primary) | ✅ |
| 4 | Account System | ✅ |
| 5 | Permissions & Sidebar | ✅ |
| 6 | Team Management & Invitations | ✅ |
| 7 | Member Settings | ✅ |
| 8 | Platform Administrator | ✅ |
| 9 | Multi-Tenant Subdomains | ✅ |
| 10 | Optional Modules | ✅ |
| 11 | Production Deployment | ⬜ |

---

## Document Cross-References

| Document | Purpose |
|----------|---------|
| `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md` | 📋 Full requirements (source of truth) |
| `COMMON-PORTAL-FRAMEWORK-README-002.md` | Quick start & setup |
| `COMMON-PORTAL-DATABASE-SCHEMA-002.md` | PostgreSQL table definitions |
| `COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md` | 🔴 Translator (follow exactly) |
| `COMMON-PORTAL-MAILER-CODE-002.md` | 🔴 Mailer (follow exactly) |
