# Common Portal Platform — Development Roadmap

Step-by-step guide to building the multi-tenant payment portal.

---

## Phase 0: Project Setup
**Status:** ✅ Complete

### Steps
- [x] Run `make setup` to install Laravel + Jetstream + packages
- [x] Run `make up` to start Docker containers
- [x] Run `make migrate` to create database tables
- [x] Visit `http://localhost:8080` to verify it works

### Files Created
After setup, `src/` will contain:
```
src/
├── app/
│   ├── Models/User.php
│   ├── Http/Controllers/
│   └── Providers/
├── resources/
│   └── views/
│       ├── layouts/app.blade.php      ← Main layout
│       ├── dashboard.blade.php        ← Dashboard page
│       ├── auth/                      ← Login, register
│       └── components/                ← Reusable UI components
├── routes/
│   └── web.php                        ← Page routes
├── public/
│   ├── css/
│   └── js/
└── database/
    └── migrations/
```

---

## Phase 1: Core Layout & Branding
**Status:** ⬜ Not Started

### Goal
Create the shared layout structure that all pages use.

### Steps
- [ ] Customize `resources/views/layouts/app.blade.php`
  - [ ] Add shared header with logo placeholder
  - [ ] Add sidebar navigation
  - [ ] Add shared footer
- [ ] Create CSS variables for white-label branding
  - [ ] `--brand-primary` (primary color)
  - [ ] `--brand-logo` (logo URL)
- [ ] Create homepage at `resources/views/welcome.blade.php`

### Key Files
| File | Purpose |
|------|---------|
| `resources/views/layouts/app.blade.php` | Master layout (header, sidebar, footer) |
| `resources/views/components/sidebar.blade.php` | Sidebar navigation component |
| `resources/views/components/header.blade.php` | Top header with logo |
| `resources/css/app.css` | Tailwind + brand CSS variables |

---

## Phase 2: Authentication (Login/Register)
**Status:** ⬜ Not Started

### Goal
Customize Jetstream's auth pages with portal branding.

### Steps
- [ ] Customize login page: `resources/views/auth/login.blade.php`
- [ ] Customize register page: `resources/views/auth/register.blade.php`
- [ ] Add tenant logo to auth pages
- [ ] Test registration → login → dashboard flow

### Notes
Jetstream provides auth out of the box. We're customizing the look, not rebuilding.

---

## Phase 3: Multi-Tenant Subdomains
**Status:** ⬜ Not Started

### Goal
Route `acme.portal.com` to Acme's branded portal.

### Steps
- [ ] Configure Stancl Tenancy package
- [ ] Create `Tenant` model with fields:
  - `id` (subdomain slug, e.g., "acme")
  - `name` (display name)
  - `domain` (full domain)
  - `brand_color` (hex color)
  - `logo_path` (storage path)
- [ ] Create tenant database migration
- [ ] Set up subdomain routing in `routes/tenant.php`
- [ ] Create middleware to load tenant branding
- [ ] Test: create tenant → visit subdomain → see branding

### Key Files
| File | Purpose |
|------|---------|
| `app/Models/Tenant.php` | Tenant model |
| `database/migrations/xxx_create_tenants_table.php` | Tenant schema |
| `config/tenancy.php` | Tenancy configuration |
| `routes/tenant.php` | Tenant-scoped routes |
| `app/Http/Middleware/LoadTenantBranding.php` | Inject branding into views |

---

## Phase 4: Role-Based Access Control
**Status:** ⬜ Not Started

### Goal
Admin sees admin menu, Member sees limited menu.

### Steps
- [ ] Configure Spatie Permission package
- [ ] Create roles: `admin`, `manager`, `member`
- [ ] Create permissions:
  - `view-dashboard`
  - `manage-team`
  - `view-transactions`
  - `manage-transactions`
  - `manage-settings`
- [ ] Assign default permissions to roles
- [ ] Create seeder for roles/permissions
- [ ] Update sidebar to show/hide items based on role

### Key Files
| File | Purpose |
|------|---------|
| `database/seeders/RolesAndPermissionsSeeder.php` | Create roles/permissions |
| `resources/views/components/sidebar.blade.php` | Role-based menu items |
| `app/Http/Middleware/CheckRole.php` | Route protection |

### Sidebar Menu Structure
```
Admin sees:
├── Dashboard
├── Transactions
├── Reports
├── Team Management ← admin only
├── Settings ← admin only
└── Billing ← admin only

Member sees:
├── Dashboard
├── My Transactions
└── Profile
```

---

## Phase 5: Team Invitations
**Status:** ⬜ Not Started

### Goal
Admin invites team members and assigns roles.

### Steps
- [ ] Customize Jetstream team invitation flow
- [ ] Add role selection to invite form
- [ ] Create invite email template
- [ ] Handle invite acceptance → assign role
- [ ] Build Team Management page:
  - List team members with roles
  - Edit member role
  - Remove member
  - Pending invites list

### Key Files
| File | Purpose |
|------|---------|
| `resources/views/teams/team-member-manager.blade.php` | Team management UI |
| `app/Mail/TeamInvitation.php` | Invite email |
| `resources/views/emails/team-invitation.blade.php` | Email template |

---

## Phase 6: Dashboard & Core Pages
**Status:** ⬜ Not Started

### Goal
Build the main pages users interact with.

### Steps
- [ ] Dashboard (`/dashboard`)
  - Summary cards (balance, recent activity)
  - Quick actions
- [ ] Transactions page (`/transactions`)
  - Table with search/filter
  - Transaction detail modal
- [ ] Profile/Settings page (`/settings`)
  - Update profile
  - Change password
  - Notification preferences
- [ ] (Admin) Tenant Settings (`/admin/settings`)
  - Upload logo
  - Set brand color
  - Update portal name

### Key Files
| File | Purpose |
|------|---------|
| `resources/views/dashboard.blade.php` | Main dashboard |
| `resources/views/transactions/index.blade.php` | Transaction list |
| `resources/views/settings/profile.blade.php` | User settings |
| `resources/views/admin/settings.blade.php` | Tenant branding settings |

---

## Phase 7: Production Deployment
**Status:** ⬜ Not Started

### Steps
- [ ] Create managed PostgreSQL on DigitalOcean
- [ ] Configure `.env.production` with managed DB credentials
- [ ] Push final code to GitHub
- [ ] Pull on DigitalOcean server
- [ ] Run `make prod-up`
- [ ] Run migrations
- [ ] Configure DNS (wildcard subdomain)
- [ ] Set up SSL (Let's Encrypt)

---

## File Structure Reference

After full build, key files:

```
src/
├── app/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Tenant.php
│   │   └── Transaction.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── TransactionController.php
│   │   │   └── Admin/SettingsController.php
│   │   └── Middleware/
│   │       ├── LoadTenantBranding.php
│   │       └── CheckRole.php
│   └── Providers/
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php           ← Master layout
│       ├── components/
│       │   ├── header.blade.php        ← Shared header
│       │   ├── sidebar.blade.php       ← Role-based sidebar
│       │   └── footer.blade.php        ← Shared footer
│       ├── auth/
│       │   ├── login.blade.php
│       │   └── register.blade.php
│       ├── dashboard.blade.php
│       ├── transactions/
│       │   └── index.blade.php
│       ├── settings/
│       │   └── profile.blade.php
│       └── admin/
│           └── settings.blade.php
├── routes/
│   ├── web.php                         ← Main routes
│   └── tenant.php                      ← Tenant-scoped routes
└── database/
    ├── migrations/
    └── seeders/
        └── RolesAndPermissionsSeeder.php
```

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
| 1 | Core Layout & Branding | ⬜ |
| 2 | Authentication | ⬜ |
| 3 | Multi-Tenant Subdomains | ⬜ |
| 4 | Role-Based Access Control | ⬜ |
| 5 | Team Invitations | ⬜ |
| 6 | Dashboard & Core Pages | ⬜ |
| 7 | Production Deployment | ⬜ |
