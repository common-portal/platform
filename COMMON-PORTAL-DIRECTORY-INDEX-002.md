# Common Portal — Directory Index

Overview of the framework directory structure and purpose of each component.

---

## Root Directory

| Path | Purpose |
|------|---------|
| `src/` | Laravel application source code |
| `docker/` | Docker configuration files for dev/prod environments |
| `docker-compose.yml` | Docker Compose for local development |
| `Dockerfile` | Production Docker image |
| `Makefile` | Developer shortcuts (`make up`, `make migrate`, etc.) |

---

## Documentation Files

| File | Purpose |
|------|---------|
| `COMMON-PORTAL-FRAMEWORK-README-002.md` | Project overview and setup instructions |
| `COMMON-PORTAL-DEVELOPMENT-ROADMAP-002.md` | Step-by-step development guide |
| `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md` | 📋 Full requirements (source of truth) |
| `COMMON-PORTAL-DATABASE-SCHEMA-002.md` | PostgreSQL table definitions |
| `COMMON-PORTAL-DIRECTORY-INDEX-002.md` | This file — directory structure reference |
| `COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md` | 🔴 Translator (follow exactly) |
| `COMMON-PORTAL-MAILER-CODE-002.md` | 🔴 Mailer (follow exactly) |

---

## Application Structure (`src/`)

### Controllers (`src/app/Http/Controllers/`)

| Directory | Purpose |
|-----------|---------|
| `Member/` | Member-specific functionality — profile, auth, personal settings |
| `Account/` | Account-level functionality — team, billing, branding settings |
| `Administrator/` | Platform-wide admin — requires `is_platform_administrator = true` |
| `Gateway/` | Public-facing endpoints for external integrations |
| `Gateway/Api/` | Public API — incoming calls from **clients** |
| `Gateway/Webhooks/` | Public webhooks — incoming calls from **partners** |

### Models (`src/app/Models/`)

| Model | Purpose |
|-------|---------|
| `PlatformMember.php` | Platform users with login credentials |
| `TenantAccount.php` | Client organizations (personal or business) |
| `TenantAccountMembership.php` | Many-to-many pivot with role/permissions |
| `OneTimePasswordToken.php` | OTP tokens for authentication |
| `TeamMembershipInvitation.php` | Pending team invitations |
| `PlatformSetting.php` | Platform-wide configuration |
| `ExternalServiceApiCredential.php` | Third-party API keys |
| `CachedTextTranslation.php` | Translator cache |
| `SupportTicket.php` | Support ticket system |

### Traits (`src/app/Traits/`)

| Trait | Purpose |
|-------|---------|
| `HasRecordUniqueIdentifier.php` | Auto-generates `record_unique_identifier` on model creation |

### Providers (`src/app/Providers/`)

| Provider | Purpose |
|----------|---------|
| `ViewComposerServiceProvider.php` | Injects platform settings, accounts, permissions into views |

### Views (`src/resources/views/`)

| Directory/File | Purpose |
|----------------|---------|
| `layouts/platform.blade.php` | Master layout with sidebar, header, footer |
| `layouts/app.blade.php` | Default Jetstream layout |
| `components/sidebar-menu.blade.php` | Sidebar navigation with account switcher |
| `components/language-selector.blade.php` | Language preference dropdown |
| `components/action-button.blade.php` | Reusable button with spinner UX |
| `pages/homepage.blade.php` | Main homepage |
| `pages/login-register.blade.php` | Combined login/register page |
| `pages/account/*.blade.php` | Account-level pages (settings, dashboard, team, create) |
| `pages/member/settings.blade.php` | Member profile settings |
| `pages/administrator/index.blade.php` | Platform admin panel |

### CSS (`src/resources/css/`)

| File | Purpose |
|------|---------|
| `app.css` | Main application styles (imports Tailwind + theme) |
| `theme.css` | CSS variables for theming (dark/light modes) |

### Routes (`src/routes/`)

| File | Purpose |
|------|---------|
| `web.php` | Web routes (browser sessions) |
| `api.php` | API routes (token auth) |
| `tenant.php` | Tenant-specific routes (subdomain scoped) |

---

## Docker Configuration (`docker/`)

| File | Purpose |
|------|---------|
| `Dockerfile.dev` | Development image (nginx + php-fpm + supervisor) |
| `nginx.conf` | Nginx configuration |
| `php.dev.ini` | PHP development settings |
| `supervisord.conf` | Process manager config |

---

## Data Model Summary

```
┌──────────────────┐       ┌───────────────────────────┐       ┌──────────────────┐
│ platform_members │◄─────►│ tenant_account_memberships│◄─────►│  tenant_accounts │
│                  │       │         (pivot)           │       │                  │
└──────────────────┘       └───────────────────────────┘       └──────────────────┘
        │                              │                               │
        │                              ▼                               │
        │                  ┌───────────────────────┐                   │
        │                  │ account_membership_role│                  │
        │                  │ granted_permission_slugs│                 │
        │                  └───────────────────────┘                   │
        │                                                              │
        ▼                                                              ▼
┌──────────────────────┐                              ┌───────────────────────────┐
│one_time_password_tokens│                            │team_membership_invitations│
└──────────────────────┘                              └───────────────────────────┘
```

- **platform_members** = individual users with login credentials
- **tenant_accounts** = client organizations (personal or business)
- **tenant_account_memberships** = many-to-many pivot with role + JSON permissions
- **Dual-ID Pattern** = every table has `id` (internal) + `record_unique_identifier` (external)
