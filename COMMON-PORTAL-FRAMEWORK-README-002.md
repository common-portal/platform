# Common Portal Platform

A white-label, multi-tenant portal framework built with Laravel, Tailwind CSS, and PostgreSQL.

> **📋 Full Requirements:** See `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md` for complete feature specifications.

---

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Setup

```bash
# 1. Clone repository
git clone https://github.com/common-portal/platform.git
cd platform

# 2. Install Laravel + dependencies
make setup

# 3. Start containers
make up

# 4. Run migrations
make migrate

# 5. Visit app
open http://localhost:8080
```

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.3, Laravel 11, Jetstream |
| **Frontend** | Tailwind CSS, Alpine.js |
| **Database** | PostgreSQL 16 |
| **Cache** | Redis 7 |

---

## Commands

```bash
make setup     # Initial installation
make up        # Start containers
make down      # Stop containers
make migrate   # Run migrations
make fresh     # Fresh migrate + seed
make shell     # Shell into container
make logs      # View logs
make test      # Run tests
```

---

## Environment

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

| Variable | Default |
|----------|---------|
| `APP_URL` | `http://localhost:8080` |
| `APP_BASE_DOMAIN` | `common-portal.nsdb.com` |
| `DB_HOST` | `postgres` |
| `DB_DATABASE` | `platform` |
| `MAIL_MAILER` | `smtp` |
| `OPENAI_API_KEY` | *(required for translator)* |

---

## Project Structure

```
├── docker/                 # Docker configuration
├── scripts/                # Setup scripts
├── src/                    # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/   # Route controllers
│   │   ├── Http/Middleware/    # Custom middleware
│   │   ├── Models/             # Eloquent models
│   │   ├── Providers/          # Service providers
│   │   ├── Services/           # Business logic services
│   │   └── Traits/             # Reusable traits
│   ├── database/
│   ├── resources/views/
│   │   ├── layouts/            # Master layouts
│   │   ├── components/         # Blade components
│   │   └── pages/              # Page views
│   └── routes/
├── docker-compose.yml      # Development
├── docker-compose.prod.yml # Production
├── Dockerfile
└── Makefile
```

---

## Document Cross-References

| Document | Purpose |
|----------|---------|
| `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md` | 📋 **Full requirements** (source of truth) |
| `COMMON-PORTAL-DEVELOPMENT-ROADMAP-002.md` | Phase-by-phase implementation plan |
| `COMMON-PORTAL-DATABASE-SCHEMA-002.md` | PostgreSQL table definitions |
| `COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md` | 🔴 Translator framework (follow exactly) |
| `COMMON-PORTAL-MAILER-CODE-002.md` | 🔴 Mailer framework (follow exactly) |

---

## Page Layouts

### Guest Layout (`layouts/guest.blade.php`)

All public/unauthenticated pages use a shared guest layout with:

| Section | Content |
|---------|---------|
| **Header** | Logo + Platform Name (gold #e3be3b), Support link, Login/Register buttons |
| **Main Frame** | Centered content area with `@yield('content')` |
| **Footer** | Language selector (100+ languages), "Powered by NSDB.COM", CC0 license |

**Public pages using this layout:**

| Page | Route | View |
|------|-------|------|
| Homepage | `/` | `pages/homepage-guest.blade.php` |
| Support | `/support` | `pages/support.blade.php` |
| Login/Register | `/login-register` | `pages/login-register.blade.php` |
| OTP Verify | `/verify` | `pages/otp-verify.blade.php` |
| Login | `/login` | `auth/login.blade.php` |
| Register | `/register` | `auth/register.blade.php` |
| Forgot Password | `/forgot-password` | `auth/forgot-password.blade.php` |
| Reset Password | `/reset-password/{token}` | `auth/reset-password.blade.php` |
| Confirm Password | `/user/confirm-password` | `auth/confirm-password.blade.php` |
| Verify Email | `/email/verify` | `auth/verify-email.blade.php` |
| Two-Factor | `/two-factor-challenge` | `auth/two-factor-challenge.blade.php` |

### Platform Layout (`layouts/platform.blade.php`)

Authenticated pages use the platform layout with sidebar navigation.

---

## Key Concepts

For detailed specifications, see `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md`:

| Concept | Section | Status |
|---------|---------|--------|
| Data Model | → Data Model (Consolidated) | ✅ Implemented |
| Authentication | → Authentication UX (OTP-primary) | ✅ Implemented |
| Permissions | → Permissions System | ✅ Implemented |
| Branding | → Branding Hierarchy | ✅ Implemented |
| Admin Panel | → Administrator Panel | ✅ Implemented |
| Sidebar | → Sidebar Menu Structure | ✅ Implemented |
| Multi-Tenant Subdomains | → Account-Level Branding | ✅ Implemented |
| Public Page Layout | → Guest Layout (header/footer) | ✅ Implemented |

---

## Production Deployment

```bash
# Configure managed PostgreSQL
cp .env.example .env.production
# Edit .env.production with managed DB credentials

# Deploy
make prod-up
```

See `COMMON-PORTAL-DEVELOPMENT-ROADMAP-002.md` → Phase 11 for full deployment steps.

---

## License

MIT License — see [LICENSE](LICENSE) for details.
