# Transactions Module Framework

## Overview

The `/modules/transactions` route serves as a **hub page** — a menu of transaction history viewers available for the current project. It is not a transaction listing itself; it simply links to the project-specific sub-pages that are.

In practice, end users may never see this hub. Each project's sidebar "Transactions" menu item will likely deep-link directly to the relevant sub-page (e.g. `/modules/transactions/crypto-exchange` on xramp, `/modules/transactions/directdebit` on directdebit.now). The hub exists primarily as a structural anchor and a fallback landing page.

## Why Namespaced Sub-Routes?

Most modules in common-portal are **functionally identical** across projects:

- **Wallets** will always be Wallets
- **IBANs** will always be IBANs
- **Team** will always be Team
- **Billing** will always be Billing

These modules share the same GUI, the same controller logic, and the same views. A single shared codebase works perfectly.

**Transactions are different.** Every service type produces different transaction data with different fields, statuses, tracking details, and workflows. A fiat currency exchange transaction looks nothing like a crypto wallet transfer, which looks nothing like a direct debit collection. Fashion follows function — the GUI must reflect the underlying service.

This means each project inevitably needs its own transaction history view. If those views all lived at `/modules/transactions` and competed for the same route, controller method, and Blade template, **every git merge from `main` into a project branch would conflict**.

## The Framework

```
/modules/transactions                         → Hub page (menu of available transaction types)
/modules/transactions/fiat-exchange           → Fiat currency exchange transactions (shared/main)
/modules/transactions/crypto-exchange         → Crypto wallet transactions (xramp-specific)
/modules/transactions/directdebit             → Direct debit collections (directdebit-specific)
/modules/transactions/{future-service}        → Any future service type
```

Each sub-route has:
- Its own **route definition** in `web.php`
- Its own **controller method** in `ModuleController.php`
- Its own **Blade view** at `pages/modules/transactions-{service}.blade.php`

Because each service type occupies a distinct namespace, project branches can add new transaction viewers without touching shared files. This eliminates merge conflicts entirely.

## Adding a New Transaction Type

1. **Route** — Add to `web.php` inside the modules group:
   ```php
   Route::get('/transactions/{service-name}', [ModuleController::class, 'myServiceTransactions'])
       ->name('transactions.{service-name}');
   ```

2. **Controller** — Add a method to `ModuleController.php`:
   ```php
   public function myServiceTransactions(Request $request)
   {
       // Permission check, account lookup, query, return view
   }
   ```

3. **View** — Create `transactions-{service-name}.blade.php` in `pages/modules/`

4. **Hub** — Add a card to `transactions-hub.blade.php` linking to the new sub-route

5. **Sidebar** (optional) — Update the sidebar menu to deep-link directly to the sub-page if it is the project's primary transaction type

## Current State

| Sub-Route | Service | Project | Status |
|-----------|---------|---------|--------|
| `/transactions/fiat-exchange` | Fiat currency exchange | Shared (all projects) | Active |
| `/transactions/crypto-exchange` | Crypto wallet send/receive | xramp.io | Active |
| `/transactions/directdebit` | Direct debit collections | directdebit.now | Active |

## Key Principle

> Transactions are the one module where "fashion follows function" — the service dictates the UI.
> Every other module (Wallets, IBANs, Team, etc.) shares a single universal implementation.
> By namespacing transaction sub-pages, we support unlimited service-specific transaction viewers with zero git merge conflicts across the common-portal framework.