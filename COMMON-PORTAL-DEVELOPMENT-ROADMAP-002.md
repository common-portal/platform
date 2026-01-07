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
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Data Model (Consolidated)

### Tables to Create
| Table | Reference |
|-------|-----------|
| `platform_members` | WISH-LIST-003 → `platform_members` Table |
| `tenant_accounts` | WISH-LIST-003 → `tenant_accounts` Table |
| `tenant_account_memberships` | WISH-LIST-003 → `tenant_account_memberships` Table |
| `one_time_password_tokens` | WISH-LIST-003 → `one_time_password_tokens` Table |
| `team_membership_invitations` | WISH-LIST-003 → `team_membership_invitations` Table |
| `platform_settings` | WISH-LIST-003 → `platform_settings` Table |
| `external_service_api_credentials` | WISH-LIST-003 → `external_service_api_credentials` Table |
| `cached_text_translations` | TRANSLATOR-CORE-CODE-001 → Database Schema |

### Steps
- [ ] Create migrations for all tables
- [ ] Create Eloquent models with relationships
- [ ] Create enums: `account_type_enum`, `membership_status_enum`, `account_membership_role_enum`, `invitation_status_enum`
- [ ] Seed `platform_settings` with defaults

---

## Phase 2: Core Layout & Branding
**Status:** ⬜ Not Started

> **Reference:** WISH-LIST-003 → Branding Hierarchy, Homepage Structure

### Steps
- [ ] Create master layout with sidebar
- [ ] Implement CSS variables for theming (see WISH-LIST-003 → Technical Notes)
- [ ] Build homepage with sticky header/footer
- [ ] Add language selector to footer (TRANSLATOR-CORE-CODE-001)
- [ ] Implement responsive design (sidebar collapse, burger menu)

### Key Deliverables
| Component | Reference |
|-----------|-----------|
| Sidebar menu structure | WISH-LIST-003 → Sidebar Menu |
| CSS variables | WISH-LIST-003 → Theming |
| Homepage layout | WISH-LIST-003 → Homepage Structure |
| Mobile responsiveness | WISH-LIST-003 → Responsive Design |

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
| 1 | Database Schema & Models | ⬜ |
| 2 | Core Layout & Branding | ⬜ |
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
