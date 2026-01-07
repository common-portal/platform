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
| `COMMON-PORTAL-FRAMEWORK-README-001.md` | Project overview and setup instructions |
| `COMMON-PORTAL-DEVELOPMENT-ROADMAP-001.md` | Step-by-step development guide |
| `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-001.md` | Feature requests and ideas |
| `COMMON-PORTAL-DIRECTORY-INDEX-001.md` | This file — directory structure reference |

---

## Application Structure (`src/`)

### Controllers (`src/app/Http/Controllers/`)

| Directory | Purpose |
|-----------|---------|
| `Member/` | Member-specific functionality — profile, auth, personal settings |
| `Account/` | Account-level functionality — team, billing, branding settings |
| `Gateway/` | Public-facing endpoints for external integrations |
| `Gateway/Api/` | Public API — incoming calls from **clients** |
| `Gateway/Webhooks/` | Public webhooks — incoming calls from **partners** |

### Models (`src/app/Models/`)

| Model | Purpose |
|-------|---------|
| `User.php` | Laravel default user (maps to members) |
| `Team.php` | Jetstream teams (maps to accounts) |
| `Tenant.php` | Stancl tenancy for subdomain branding |

### Views (`src/resources/views/`)

| Directory | Purpose |
|-----------|---------|
| `layouts/` | Master layouts (app, guest) |
| `components/` | Reusable Blade components |
| `dashboard.blade.php` | Main dashboard view |
| `welcome.blade.php` | Public homepage |

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
┌─────────────┐       ┌─────────────────┐       ┌─────────────┐
│   members   │◄─────►│  account_member │◄─────►│  accounts   │
│  (users)    │       │    (pivot)      │       │  (tenants)  │
└─────────────┘       └─────────────────┘       └─────────────┘
                              │
                              ▼
                       ┌─────────────┐
                       │    roles    │
                       └─────────────┘
```

- **Members** = individual users with login credentials
- **Accounts** = client organizations (white-labeled via subdomain)
- **Pivot** = many-to-many with role assignment
