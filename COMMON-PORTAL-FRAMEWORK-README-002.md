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
| `DB_HOST` | `postgres` |
| `DB_DATABASE` | `platform` |
| `OPENAI_API_KEY` | *(required for translator)* |

---

## Project Structure

```
├── docker/                 # Docker configuration
├── scripts/                # Setup scripts
├── src/                    # Laravel application
│   ├── app/
│   ├── database/
│   ├── resources/
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

## Key Concepts

For detailed specifications, see `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md`:

| Concept | Section |
|---------|---------|
| Data Model | → Data Model (Consolidated) |
| Authentication | → Authentication UX (OTP-primary) |
| Permissions | → Permissions System |
| Branding | → Branding Hierarchy |
| Admin Panel | → Administrator Panel |
| Sidebar | → Sidebar Menu Structure |

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
