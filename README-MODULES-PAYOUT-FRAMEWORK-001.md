# Payout Module Framework

## Overview

The `/modules/payout` route serves as a **hub page** — a menu of payout methods available for the current project. It links to currency-type-specific sub-pages that each guide the user through a payout payflow.

This follows the same namespacing pattern as the Transactions module (see `README-MODULES-TRANSACTIONS-FRAMEWORK-001.md`). Payouts are organized by currency type because each rail has fundamentally different source accounts, destination formats, networks, and compliance requirements. Fashion follows function.

## The Framework

```
/modules/payout                → Hub page (menu: Crypto vs Fiat)
/modules/payout/crypto         → Crypto payout payflow (wallet → amount → Solana → destination wallet ID)
/modules/payout/fiat           → Fiat payout payflow (IBAN → amount → SEPA/SWIFT → destination account)
/modules/payout/{future-type}  → Any future payout type
```

Each sub-route has:
- Its own **route definition** in `web.php`
- Its own **controller method** in `ModuleController.php`
- Its own **Blade view** at `pages/modules/payout-{type}.blade.php`

## Payout Payflows

### Crypto Payout (`/modules/payout/crypto`)

| Step | Field | Source |
|------|-------|--------|
| 1 | **Source Wallet** | Dropdown of user's crypto wallets (with live balance) |
| 2 | **Amount** | Numeric input (with currency badge: USDT/USDC/EURC/SOL) |
| 3 | **Rail** | Solana (auto-selected based on wallet network) |
| 4 | **Destination** | Wallet address (text input) + optional memo |

Submits to the existing `walletSend` API endpoint.

### Fiat Payout (`/modules/payout/fiat`)

| Step | Field | Source |
|------|-------|--------|
| 1 | **Source IBAN** | Dropdown of user's IBANs |
| 2 | **Amount** | Numeric input (with currency: EUR/GBP/USD) |
| 3 | **Rail** | SEPA or SWIFT (radio selection) |
| 4 | **Destination** | Beneficiary name, IBAN/account number, BIC/SWIFT code, bank name, reference |

Submits to a new `payoutFiatStore` API endpoint (Phase 2).

## Permission

- **Slug**: `can_initiate_payout`
- **Label**: "Payout"
- **Sidebar**: "Payout" menu item, positioned after Transactions

## Phase 1 (Current)

- [x] Hub page with Crypto + Fiat tiles
- [x] Crypto payout form (source wallet, amount, destination, submit via existing walletSend)
- [x] Fiat payout form (source IBAN, amount, rail, destination — UI only, submission placeholder)
- [x] Sidebar menu item with permission gating
- [ ] Fiat payout backend processing (Phase 2)
- [ ] Transaction confirmation screens
- [ ] Payout history / status tracking

## Key Principle

> Payouts are organized by currency type (Crypto vs Fiat) because each rail has fundamentally different workflows, validations, and compliance requirements. The hub exists to route users to the correct payflow while maintaining clean git namespaces across the common-portal framework.