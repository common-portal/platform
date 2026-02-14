# Common Portal Git Framework

> **Version:** 1.0  
> **Last Updated:** February 1, 2026  
> **Repository:** https://github.com/common-portal/platform

---

## Overview

This document outlines the Git workflow and configuration strategy for managing multiple Common Portal deployments from a single codebase. The framework supports independent projects (e.g., XRAMP.io, DirectDebit.now) while maintaining a shared core platform.

---

## Repository Structure

### GitHub Branches

```
github.com/common-portal/platform
├── main        ← Canonical framework code (shared by all projects)
├── xramp       ← XRAMP.io specific features/customizations
└── directdebit ← DirectDebit.now specific features/customizations
```

### Branch Purposes

| Branch | Purpose | Tracked By |
|--------|---------|------------|
| `main` | Core platform framework, shared features, bug fixes | Primary development |
| `xramp` | XRAMP-specific features, configurations | xramp.io deployment |
| `directdebit` | DirectDebit-specific features, configurations | directdebit.now deployment |

---

## Local Project Configuration

### Directory Layout

```
/root/CascadeProjects/
├── xramp.io/                      ← XRAMP deployment
│   ├── docker-compose.yml         ← Base config (tracked)
│   ├── docker-compose.override.yml ← XRAMP-specific (gitignored)
│   └── src/                       ← Laravel application
│
└── directdebit.now/               ← DirectDebit deployment
    ├── docker-compose.yml         ← Base config (tracked)
    ├── docker-compose.override.yml ← DirectDebit-specific (gitignored)
    └── src/                       ← Laravel application
```

### Branch Tracking

| Local Project | Git Branch | Upstream |
|---------------|------------|----------|
| `/root/CascadeProjects/xramp.io` | `main` | `origin/main` |
| `/root/CascadeProjects/directdebit.now` | `directdebit` | `origin/directdebit` |

---

## Docker Compose Override Pattern

To prevent merge conflicts on environment-specific configurations (ports, container names, volumes), we use Docker Compose's built-in override mechanism.

### How It Works

Docker Compose automatically merges these files in order:
1. `docker-compose.yml` (base configuration - tracked in Git)
2. `docker-compose.override.yml` (environment overrides - gitignored)

### Base Configuration (`docker-compose.yml`)

Contains shared service definitions without environment-specific values:
- Build contexts and Dockerfiles
- Environment variable templates
- Health checks
- Service dependencies

**This file is tracked in Git and shared across all deployments.**

### Override Configuration (`docker-compose.override.yml`)

Contains environment-specific values:
- Container names (e.g., `platform-app` vs `directdebit-app`)
- Port mappings (e.g., `8080:80` vs `8081:80`)
- Volume names (e.g., `xramp_pgdata` vs `directdebit_pgdata`)
- Network names

**This file is gitignored and unique to each deployment.**

### Example Override Files

#### XRAMP.io (`docker-compose.override.yml`)
```yaml
services:
  app:
    container_name: platform-app
    ports:
      - "8080:80"
    networks:
      - platform-network

  postgres:
    container_name: platform-postgres
    volumes:
      - xramp_pgdata:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    networks:
      - platform-network

  redis:
    container_name: platform-redis
    ports:
      - "6379:6379"
    networks:
      - platform-network

volumes:
  xramp_pgdata:
    external: true
    name: xrampio_xramp_pgdata

networks:
  platform-network:
    driver: bridge
```

#### DirectDebit.now (`docker-compose.override.yml`)
```yaml
services:
  app:
    container_name: directdebit-app
    ports:
      - "8081:80"
    networks:
      - directdebit-network

  postgres:
    container_name: directdebit-postgres
    volumes:
      - directdebit_pgdata:/var/lib/postgresql/data
    ports:
      - "5433:5432"
    networks:
      - directdebit-network

  redis:
    container_name: directdebit-redis
    ports:
      - "6380:6379"
    networks:
      - directdebit-network

volumes:
  directdebit_pgdata:
    external: true
    name: directdebitnow_directdebit_pgdata

networks:
  directdebit-network:
    driver: bridge
```

---

## Port Allocation

To run multiple projects simultaneously on the same server:

| Service | XRAMP.io | DirectDebit.now |
|---------|----------|-----------------|
| **App (HTTP)** | 8080 | 8081 |
| **PostgreSQL** | 5432 | 5433 |
| **Redis** | 6379 | 6380 |

---

## Common Git Workflows

### 1. Push Framework Updates (from XRAMP.io)

When making changes that should be shared across all projects:

```bash
cd /root/CascadeProjects/xramp.io

# Make your changes, then:
git add -A
git commit -m "Feature: Description of shared feature"
git push origin main
```

### 2. Sync DirectDebit with Framework Updates

Pull the latest framework changes into DirectDebit:

```bash
cd /root/CascadeProjects/directdebit.now

git fetch origin
git merge origin/main

# Resolve any conflicts (override file won't conflict - it's gitignored)
# Test the changes
docker compose up -d
```

### 3. Push DirectDebit-Specific Changes

For features only relevant to DirectDebit:

```bash
cd /root/CascadeProjects/directdebit.now

git add -A
git commit -m "DirectDebit: Description of specific feature"
git push origin directdebit
```

### 4. Cherry-Pick Specific Commits Between Branches

To selectively apply a commit from one branch to another:

```bash
# Get the commit hash from the source branch
git log --oneline origin/xramp

# Apply it to your current branch
git cherry-pick <commit-hash>
```

### 5. Create a New Project Deployment

To add a new project (e.g., `newproject.com`):

```bash
# Clone from GitHub
cd /root/CascadeProjects
git clone https://github.com/common-portal/platform.git newproject.com
cd newproject.com

# Create and checkout a new branch
git checkout -b newproject
git push -u origin newproject

# Create the override file with unique ports/names
cp /path/to/template/docker-compose.override.yml .
# Edit with unique ports (e.g., 8082, 5434, 6381)

# Configure environment
cp src/.env.example src/.env
# Edit src/.env with project-specific values

# Start the project
docker compose up -d
```

---

## Gitignore Configuration

The following entries in `.gitignore` ensure environment-specific files aren't tracked:

```gitignore
# Environment-specific Docker Compose overrides
docker-compose.override.yml

# Laravel environment file
src/.env

# Other environment files
.env
docker/cloudflare.ini
docker/digitalocean.ini
```

---

## ⚠️ Naming Conventions & Conflict Avoidance (HIGH PRIORITY)

Because all projects share a single codebase and merge from `main`, **every function, file, variable, and class name must be specific enough to avoid collisions across projects**. This is a standing mandate for all development going forward.

### Rules

1. **No generic names.** Every new controller, model method, service class, migration, route name, and Blade view must clearly indicate its domain or integration context.
   - ❌ `fetchBalance()` → ✅ `fetchShFinancialIbanBalance()`
   - ❌ `WebhookController` → ✅ `ShFinancialController`
   - ❌ `process_payment.php` → ✅ `process_sh_financial_payment.php`
   - ❌ `$balance` → ✅ `$shFinancialBalance`

2. **Prefix project-specific features.** If a feature exists only for one project, prefix it clearly:
   - DirectDebit-only: `MandateInvitation`, `Customer`, `PublicMandateController`
   - XRAMP-only: `ShFinancialController`, `WebhookLog`
   - Shared/core: Generic platform features (auth, team, settings, support)

3. **Database migrations must be descriptive.** Include the table name and the specific change:
   - ❌ `add_new_columns` → ✅ `add_bic_routing_to_iban_accounts`
   - ❌ `update_table` → ✅ `add_payment_details_to_transactions_table`

4. **Route names must be namespaced.** Use dot-notation that reflects the module:
   - ❌ `webhook` → ✅ `webhook.sh-financial`
   - ❌ `customers.update` → ✅ `account.customers.update`

5. **All projects keep all functions.** We do not strip out project-specific code when merging. Instead, features are enabled/disabled per-project via:
   - Platform settings / module toggles in the admin panel
   - Sidebar menu visibility toggles
   - Permission slugs on team memberships

### Why This Matters

When merging `origin/main` into any project branch, additive changes (new files, new functions) apply cleanly. Conflicts only arise when two projects modify the **same line** in the **same file**. Specific naming ensures that new code lands in distinct locations, keeping merges conflict-free.

---

## Mandatory Sync Cadence

To minimize drift and reduce merge complexity, all projects **must sync with `main` regularly**.

### Sync Schedule

| Frequency | Action | Who |
|-----------|--------|-----|
| **Every development session** | `git fetch origin` to check for upstream changes | All developers |
| **Before starting new features** | `git merge origin/main` into project branch | Project developer |
| **After completing a feature on `main`** | Push to `origin/main` immediately | Feature developer |
| **End of each work day** | Commit and push all work to the appropriate branch | All developers |

### Sync Workflow

```bash
# At the start of each session on any project branch:
git fetch origin
git log --oneline origin/main..HEAD   # See what you have that main doesn't
git log --oneline HEAD..origin/main   # See what main has that you don't

# If main has new commits, merge them:
git stash push -m "WIP before sync"
git merge origin/main
# Resolve any conflicts (should be rare with proper naming)
git stash pop
```

### Why Frequent Syncs

- **Smaller diffs** = fewer conflicts = easier resolution
- Each project always has the latest shared improvements
- Avoids "big bang" merges where weeks of divergence collide
- Ensures all projects benefit from bug fixes immediately

---

## Merge Strategy

### Framework Updates Flow

```
main (canonical)
  │
  ├──► xramp (merges from main)
  │
  └──► directdebit (merges from main)
```

### Feature Promotion

If a project-specific feature should become part of the core framework:

1. Develop and test on project branch
2. Create PR from project branch to `main`
3. Review and merge
4. Other projects can then merge `main` to receive the feature

---

## Troubleshooting

### Merge Conflicts in docker-compose.yml

If you see conflicts in `docker-compose.yml` after merging:

1. Accept the incoming changes (from `main`)
2. Your environment-specific settings are safe in `docker-compose.override.yml`
3. Run `docker compose config` to verify the merged configuration

### Verifying Override Merge

To see the effective configuration after override:

```bash
docker compose config
```

### Containers Using Wrong Ports

Ensure `docker-compose.override.yml` exists and has correct values:

```bash
ls -la docker-compose.override.yml
cat docker-compose.override.yml
```

---

## Quick Reference

### Check Current Branch
```bash
git branch -vv
```

### View All Branches
```bash
git branch -a
```

### Fetch Latest from GitHub
```bash
git fetch origin
```

### View Commit History
```bash
git log --oneline -10
```

### View Differences from Main
```bash
git diff origin/main
```

---

## Related Documentation

- `COMMON-PORTAL-FRAMEWORK-README-002.md` - Platform architecture overview
- `COMMON-PORTAL-DATABASE-SCHEMA-002.md` - Database schema documentation
- `COMMON-PORTAL-BRANDING-README-001.md` - Branding configuration
- `COMMON-PORTAL-SERVER-REBOOT-README.md` - Multi-project server management
- `DIRECTDEBIT-CREATION-ROADMAP-README-001.md` - DirectDebit deployment guide

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.1 | 2026-02-08 | Added naming conventions, conflict avoidance policy, and mandatory sync cadence |
| 1.0 | 2026-02-01 | Initial Git framework setup with branch structure and override pattern |
