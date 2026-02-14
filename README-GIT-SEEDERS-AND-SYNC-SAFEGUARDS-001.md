# Git, Seeders, Sync & Safeguards Framework

**Version:** 1.0  
**Last Updated:** February 14, 2026  
**Applies To:** All Common Portal Platform deployments

---

## Overview

This document defines the strategy, safeguards, and tooling for managing database migrations, seeders, and cross-project synchronization across all deployments of the Common Portal Platform. It was created after a production incident where running `migrate:fresh --seed` destroyed all live data.

### The Three Projects

All three projects share a single GitHub repository (`github.com/common-portal/platform.git`) on separate branches:

| Project | Branch | Container | Port | Purpose |
|---------|--------|-----------|------|---------|
| `common-portal-platform` | `main` | — (base repo) | — | Upstream source of truth |
| `xramp.io` | `main` | `platform-app` | 8080 | Crypto/fiat exchange platform |
| `directdebit.now` | `directdebit` | `directdebit-app` | 8081 | Direct debit collections platform |

### Unified Schema Principle

All three projects maintain **identical** database schemas:

- **57 migrations** — every project has all 57, even if some tables are only used by one deployment
- **27 Eloquent models** — every project has all 27 model classes
- **44 database tables** — both live databases have all 44 tables

Tables that are not relevant to a specific deployment simply remain empty. This ensures:
- No migration conflicts when merging between branches
- Any feature can be enabled on any deployment via configuration
- One codebase, one schema, features toggled per deployment

---

## The Incident: What Happened

### Root Cause

The Makefile contained a `fresh` target:

```makefile
fresh:
    docker-compose exec app php artisan migrate:fresh --seed
```

`migrate:fresh` is a Laravel command that:
1. **DROPS every table** in the database
2. Re-runs all migrations from scratch (creating empty tables)
3. Runs seeders to populate initial data

This was accidentally triggered on the production database, which:
- Destroyed all `platform_members` (user accounts)
- Destroyed all `tenant_accounts` (organizations)
- Destroyed all `tenant_account_memberships` (user-org links)
- Destroyed all `platform_settings` (branding, theme config)
- Destroyed all `transactions`, `iban_accounts`, and other business data

### Why Seeders Didn't Help

The seeders use `updateOrCreate` (safe), but `migrate:fresh` drops the tables first. By the time seeders run, there is nothing to "update" — only empty tables remain. The seeders only create minimal test data (a test user and one admin member), not the full production dataset.

---

## Safeguards Implemented

### 1. Production Seeder Block

**File:** `src/database/seeders/DatabaseSeeder.php` (all 3 projects)

```php
public function run(): void
{
    // PRODUCTION SAFEGUARD: Abort if running in production
    if (app()->environment('production')) {
        $this->command->error('SEEDER BLOCKED: Cannot run seeders in production environment.');
        $this->command->error('   Seeders are for local development only.');
        $this->command->error('   Use artisan tinker for production data changes.');
        return;
    }
    // ... seeder logic ...
}
```

This prevents `php artisan db:seed` from executing in any environment where `APP_ENV=production`.

### 2. Makefile Confirmation Prompt

**File:** `Makefile` (all 3 projects)

The `fresh` target now requires typing the word `DESTROY` to proceed:

```makefile
fresh:
    @echo "WARNING: migrate:fresh DROPS ALL TABLES and destroys all data!"
    @echo "This is for LOCAL DEVELOPMENT ONLY."
    @read -p "Type 'DESTROY' to confirm: " confirm && [ "$$confirm" = "DESTROY" ] || (echo "Aborted." && exit 1)
    docker-compose exec app php artisan migrate:fresh --seed
```

This provides a two-layer defense:
- Layer 1: The Makefile requires explicit confirmation
- Layer 2: Even if confirmed, the seeder refuses to run in production

### 3. Safe Migration Commands

**SAFE (additive only, never destroys data):**
```bash
php artisan migrate          # Runs only NEW migrations that haven't been run yet
php artisan migrate --force  # Same, but skips the production confirmation prompt
```

**DANGEROUS (destroys all data):**
```bash
php artisan migrate:fresh    # Drops ALL tables and recreates from scratch
php artisan migrate:fresh --seed  # Same + runs seeders
php artisan migrate:rollback      # Reverses the last batch of migrations
php artisan migrate:reset         # Reverses ALL migrations (drops all tables)
```

### Rule: Never Run Dangerous Commands on Production

The only migration command that should ever run on a production server is:
```bash
php artisan migrate --force
```

This is additive only — it creates new tables and adds new columns. It never drops, deletes, or modifies existing data.

---

## Cross-Project Sync Script

**File:** `scripts/sync-migrations.sh` (all 3 projects)

### What It Does

1. Scans migration directories in all 3 projects
2. Copies any missing migration files to the projects that lack them (additive only)
3. Reports what was added
4. Optionally runs `php artisan migrate` on live containers

### What It Does NOT Do

- Delete any files
- Drop any tables
- Modify any existing migration files
- Run seeders

### Usage

```bash
# From any project directory:
./scripts/sync-migrations.sh
```

### Example Output

```
=============================================
  Migration Sync — Additive Only
=============================================

Total unified migrations: 57

  common-portal-platform: already up to date (57 migrations)
  xramp.io: already up to date (57 migrations)
  directdebit.now: already up to date (57 migrations)

=============================================
  Verification
=============================================
  common-portal-platform: 57 migrations
  xramp.io:               57 migrations
  directdebit.now:        57 migrations

  ALL PROJECTS SYNCHRONIZED (57 migrations each)
```

### When to Run

Run `sync-migrations.sh` after:
- Creating new migrations in any project
- Pulling changes from the remote repository
- Before deploying to production

---

## Migration Best Practices

### Writing New Migrations

All migrations must be **additive** and use `IF NOT EXISTS` patterns:

```php
// GOOD: Safe for production
Schema::create('new_table', function (Blueprint $table) {
    // This automatically uses IF NOT EXISTS behavior in Laravel
    $table->id();
    $table->string('name');
    $table->timestamps();
});

// GOOD: Adding a column safely
if (!Schema::hasColumn('existing_table', 'new_column')) {
    Schema::table('existing_table', function (Blueprint $table) {
        $table->string('new_column')->nullable()->after('existing_column');
    });
}

// BAD: Never do this in a migration
Schema::dropIfExists('existing_table');  // Destroys data!
$table->dropColumn('existing_column');   // Destroys data!
DB::table('users')->truncate();          // Destroys data!
```

### New Column Rules

- Always make new columns `nullable()` or provide a `default()` value
- This ensures existing rows won't break when the migration runs
- Never add a non-nullable column without a default to a table that has data

### Migration Naming Convention

```
YYYY_MM_DD_HHMMSS_description_of_change.php
```

Examples:
```
2026_02_14_220000_add_customer_support_email_to_tenant_accounts.php
2026_02_14_221000_create_direct_debit_collections_table.php
```

---

## Git Workflow & Branch Strategy

### Repository Structure

```
github.com/common-portal/platform.git
├── main branch          ← common-portal-platform (base) + xramp.io
└── directdebit branch   ← directdebit.now
```

### Safe Git Operations

```bash
# Pull latest changes (safe)
git pull origin main

# After pulling, run migrations on the live container (safe, additive only)
docker exec platform-app php artisan migrate --force

# After pulling, sync migrations across projects (safe, additive only)
./scripts/sync-migrations.sh
```

### After Code Changes

Use the existing refresh script for xramp.io:
```bash
/root/CascadeProjects/xramp.io/run_this_script_after_changes.sh
```

This clears view cache, clears application cache, and restarts the app container. It does NOT touch the database.

---

## Production Data Changes

### How to Make Data Changes in Production

Never use seeders. Use `artisan tinker` for direct, controlled changes:

```bash
# Example: Update platform branding
docker exec platform-app php artisan tinker --execute="
use App\Models\PlatformSetting;
PlatformSetting::setValue('platform_display_name', 'XRAMP.io');
"

# Example: Promote a member to admin
docker exec platform-app php artisan tinker --execute="
use App\Models\PlatformMember;
\$member = PlatformMember::where('login_email_address', 'admin@example.com')->first();
\$member->update(['is_platform_administrator' => true]);
"
```

### Backups Before Changes

Before any production data change, verify current state:
```bash
docker exec platform-app php artisan tinker --execute="
echo 'Members: ' . \DB::table('platform_members')->count();
echo 'Accounts: ' . \DB::table('tenant_accounts')->count();
echo 'Settings: ' . \DB::table('platform_settings')->count();
"
```

---

## Current State (as of February 14, 2026)

| Check | Status |
|-------|--------|
| 57 migrations identical across all 3 projects | Verified |
| 27 Eloquent models identical across all 3 projects | Verified |
| 44 database tables in both live databases | Verified |
| Production safeguard in `DatabaseSeeder.php` | Active |
| Makefile `fresh` target requires "DESTROY" confirmation | Active |
| Sync script `scripts/sync-migrations.sh` available | Active |
| All changes committed and pushed to GitHub | Done |

### Unified Permission Slugs

All three projects share the same superset of permission slugs in `TenantAccountMembership`:

| Permission Slug | Label |
|----------------|-------|
| `can_access_account_settings` | Account Settings |
| `can_access_account_dashboard` | Dashboard |
| `can_view_transaction_history` | Transactions |
| `can_view_billing_history` | Billing |
| `can_view_ibans` | IBANs |
| `can_view_wallets` | Wallets |
| `can_access_developer_tools` | Developer |
| `can_manage_team_members` | Team Members |
| `can_access_support_tickets` | Support Tickets |
| `can_initiate_payout` | Payout |
| `can_view_fees` | Fees |

---

## Quick Reference: Commands Cheat Sheet

| Command | Safe? | What It Does |
|---------|-------|-------------|
| `php artisan migrate --force` | **YES** | Runs new migrations only (additive) |
| `./scripts/sync-migrations.sh` | **YES** | Copies missing migration files across projects |
| `php artisan tinker` | **YES** | Interactive shell for data changes |
| `make migrate` | **YES** | Shortcut for `php artisan migrate` |
| `make fresh` | **NO** | Drops ALL tables. Requires typing "DESTROY". Blocked in production. |
| `make seed` | **NO** | Runs seeders. Blocked in production via `DatabaseSeeder.php`. |
| `php artisan migrate:fresh` | **NO** | Drops ALL tables. Never run on production. |
| `php artisan migrate:rollback` | **NO** | Reverses last migration batch. Can destroy data. |
| `php artisan migrate:reset` | **NO** | Reverses ALL migrations. Destroys everything. |