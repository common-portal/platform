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
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Authentication UX, Registration & Account Flow

### Steps
- [ ] Build `/login-register` page (combined login/register)
- [ ] Implement OTP flow as primary authentication
- [ ] Implement password login as optional secondary
- [ ] Auto-create personal account on registration
- [ ] Integrate mailer for OTP delivery (MAILER-CODE-002)

### OTP Flow (from WISH-LIST-003)
1. Email entered → check if member exists
2. **New member:** Create member + personal account + send OTP
3. **Existing member:** Send OTP
4. OTP validated → logged in

---

## Phase 4: Account System
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Registration & Account Flow, Account Types

### Steps
- [ ] Implement personal account (auto-created, cannot delete)
- [ ] Implement business account creation ("+ Add Business Account")
- [ ] Build account switcher dropdown in sidebar
- [ ] Implement soft delete for business accounts (Danger Zone)
- [ ] Account-level branding (logo, subdomain)

### Account Types (from WISH-LIST-003)
| Type | Behavior |
|------|----------|
| `personal_individual` | Auto-created, one per member, cannot delete |
| `business_organization` | Manual creation, shareable, can soft delete |

---

## Phase 5: Permissions & Sidebar
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Permissions System, Sidebar Menu

### Steps
- [ ] Implement permission slugs in `tenant_account_memberships.granted_permission_slugs`
- [ ] Build permission-based sidebar visibility
- [ ] Implement platform toggle for menu items (`platform_settings.sidebar_menu_item_visibility_toggles`)
- [ ] Platform administrator override (`is_platform_administrator = true`)

### Permission Slugs (from WISH-LIST-003)
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
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Team Page Actions, `team_membership_invitations` Table

### Steps
- [ ] Build Team page with member list
- [ ] Implement invitation flow (email → accept → membership)
- [ ] Edit member permissions
- [ ] Disable/re-enable members
- [ ] Resend invitation functionality

### Team Actions (from WISH-LIST-003)
| Action | Permission Required |
|--------|---------------------|
| Invite | `can_manage_team_members` |
| Resend | `can_manage_team_members` |
| Edit Permissions | `can_manage_team_members` |
| Disable/Re-enable | `can_manage_team_members` |

---

## Phase 7: Member Settings
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Sidebar Menu → Member Profile

### Steps
- [ ] Profile tab (avatar, first name, last name)
- [ ] Login Email tab (change email with OTP verification)
- [ ] Login Password tab (optional password management)
- [ ] Language preference (synced to translator)

---

## Phase 8: Platform Administrator
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Administrator Access, Platform-Level Branding

### Steps
- [ ] Administrator sidebar section (visible only if `is_platform_administrator = true`)
- [ ] Platform Theme page (logo, colors, favicon, meta)
- [ ] Menu Items toggle page
- [ ] Platform Members management

### Admin Pages (from WISH-LIST-003)
| Tab | Function |
|-----|----------|
| Platform Theme | Logo, colors, favicon, OG image |
| Menu Items | Toggle optional modules |
| Platform Members | Manage all platform members |

---

## Phase 9: Multi-Tenant Subdomains
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Branding Hierarchy → Account-Level Branding

### Steps
- [ ] Configure Stancl Tenancy
- [ ] Subdomain routing (`clientname.commonportal.com`)
- [ ] Account branding overrides platform branding on subdomain
- [ ] CNAME/A record support for custom domains

---

## Phase 10: Optional Modules
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Optional Modules

### Modules to Implement
| Module | Default | Description |
|--------|---------|-------------|
| Developer | Off | API docs, keys |
| Support | Off | Ticket system |
| Transactions | Off | Transaction history |
| Billing | Off | Billing history |

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
| 3 | Authentication (OTP-Primary) | ⬜ |
| 4 | Account System | ⬜ |
| 5 | Permissions & Sidebar | ⬜ |
| 6 | Team Management & Invitations | ⬜ |
| 7 | Member Settings | ⬜ |
| 8 | Platform Administrator | ⬜ |
| 9 | Multi-Tenant Subdomains | ⬜ |
| 10 | Optional Modules | ⬜ |
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
