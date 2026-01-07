# Common Portal Platform

A white-label, multi-tenant payment portal built with Laravel, Tailwind CSS, and PostgreSQL.

## Features

- **Multi-tenant subdomains** — Each client gets their own branded subdomain (e.g., `acme.yourportal.com`)
- **Team management** — Invite team members with role-based access control
- **Role-based permissions** — Admin, Manager, Member roles with customizable permissions
- **White-labeling** — Per-tenant branding (logo, colors, name)
- **Docker-ready** — Full containerized setup for development and production

## Tech Stack

- **Backend**: PHP 8.3, Laravel 11, Jetstream (Livewire + Teams)
- **Frontend**: Tailwind CSS, Alpine.js
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Packages**: 
  - [Spatie Permissions](https://spatie.be/docs/laravel-permission) — Role management
  - [Stancl Tenancy](https://tenancyforlaravel.com/) — Multi-tenancy

---

## Quick Start (Development)

### Prerequisites

- Docker & Docker Compose
- Git

### 1. Clone the repository

```bash
git clone https://github.com/common-portal/platform.git
cd platform
```

### 2. Run setup

```bash
make setup
```

This installs Laravel, Jetstream, and all dependencies.

### 3. Start containers

```bash
make up
```

### 4. Run migrations

```bash
make migrate
```

### 5. Visit the app

Open [http://localhost:8080](http://localhost:8080)

---

## Environment Configuration

Copy `.env.example` to `.env` and configure:

```bash
cp .env.example .env
```

### Key Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_URL` | Application URL | `http://localhost:8080` |
| `DB_HOST` | Database host | `postgres` (Docker) |
| `DB_DATABASE` | Database name | `platform` |
| `DB_USERNAME` | Database user | `platform` |
| `DB_PASSWORD` | Database password | `secret` |
| `TENANT_DOMAIN` | Base domain for tenants | `localhost` |

---

## Common Commands

```bash
make up        # Start containers
make down      # Stop containers
make logs      # View logs
make shell     # Shell into app container
make migrate   # Run migrations
make fresh     # Fresh migrate + seed
make test      # Run tests
make tinker    # Laravel tinker
```

---

## Production Deployment

### Using Managed PostgreSQL (Recommended)

1. **Create a managed PostgreSQL database** on DigitalOcean (or your provider)

2. **Configure production environment:**

```bash
# .env.production
DB_HOST=your-db-cluster.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=platform
DB_USERNAME=doadmin
DB_PASSWORD=your-password
DB_SSLMODE=require
```

3. **Deploy with production compose:**

```bash
make prod-up
```

This uses `docker-compose.prod.yml` which:
- Removes the local PostgreSQL container
- Connects to your managed database
- Sets production environment variables

### Container Registry

The GitHub Actions workflow automatically builds and pushes to `ghcr.io/common-portal/platform:latest` on every push to `main`.

To pull and run:

```bash
docker pull ghcr.io/common-portal/platform:latest
docker run -d -p 8080:80 --env-file .env.production ghcr.io/common-portal/platform:latest
```

---

## Multi-Tenancy Setup

Each tenant (client) operates on their own subdomain:

```
acme.yourportal.com      → Acme Corp's portal
bigcorp.yourportal.com   → BigCorp's portal
admin.yourportal.com     → Central admin (optional)
```

### Creating a Tenant

```php
use App\Models\Tenant;

$tenant = Tenant::create([
    'id' => 'acme',
    'name' => 'Acme Corporation',
    'domain' => 'acme.yourportal.com',
    'brand_color' => '#FF5733',
    'logo_url' => '/storage/logos/acme.png',
]);
```

### DNS Configuration

For production, configure wildcard DNS:

```
*.yourportal.com → Your server IP
```

---

## Roles & Permissions

Default roles (customizable):

| Role | Description |
|------|-------------|
| `admin` | Full access, manage team, billing, settings |
| `manager` | View reports, manage transactions |
| `member` | View own transactions only |

### Assigning Roles

```php
$user->assignRole('admin');
$user->hasRole('admin'); // true
$user->can('manage-team'); // check permission
```

---

## White-Labeling

Each tenant can customize:

- **Logo** — Uploaded per tenant
- **Brand color** — Primary color for UI
- **Portal name** — Shown in header/emails

Access in Blade templates:

```blade
<div style="background-color: {{ tenant()->brand_color }}">
    <img src="{{ tenant()->logo_url }}" alt="{{ tenant()->name }}">
</div>
```

---

## Project Structure

```
├── docker/                 # Docker configuration
│   ├── nginx.conf          # Nginx config
│   ├── php.ini             # PHP config
│   └── supervisord.conf    # Process manager
├── scripts/
│   └── setup.sh            # Initial setup script
├── src/                    # Laravel application
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   └── ...
├── .github/workflows/
│   └── build.yml           # CI/CD pipeline
├── docker-compose.yml      # Development (with local Postgres)
├── docker-compose.prod.yml # Production (managed DB)
├── Dockerfile
├── Makefile
└── README.md
```

---

## License

MIT License — see [LICENSE](LICENSE) for details.
