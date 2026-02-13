# 2FA Google Authenticator — Implementation Plan (KISS v1)

**Feature:** Add TOTP-based Two-Factor Authentication (Google Authenticator / Authy / etc.) to member profiles, with enforcement at login when activated.

**Principle:** Keep It Super Simple — minimal moving parts, no over-engineering. Recovery codes and extra middleware deferred to v2.

---

## Overview

- A new **"2FA"** tab is added to the Member Settings page (`/member/settings`) alongside Profile, Login Email, and Login Password.
- Members can **enable, verify, and disable** 2FA from this tab.
- When 2FA is **inactive**, login works as today (email + OTP or email + password).
- When 2FA is **active**, after the normal login step, the user is redirected to a **2FA challenge screen** where they must enter a 6-digit code from their authenticator app before being fully authenticated.
- If a user loses their authenticator device, a **platform admin can disable 2FA** for them via the existing admin impersonation feature.

---

## Architecture Summary

| Layer | What Changes |
|---|---|
| **Database** | New migration: add `two_factor_secret_key` (encrypted) and `two_factor_enabled_at_timestamp` to `platform_members` |
| **Composer** | Add `pragmarx/google2fa-laravel` (TOTP library) |
| **Model** | `PlatformMember` — add new fields to `$fillable`, `$hidden`, `$casts`; add `hasTwoFactorEnabled()` helper |
| **Controller** | `MemberController` — add `enableTwoFactor()`, `confirmTwoFactor()`, `disableTwoFactor()` |
| **Controller** | `OtpAuthController` — modify `verifyOtp()` and `loginWithPassword()` to check 2FA; add `showTwoFactorChallenge()`, `verifyTwoFactorChallenge()` |
| **Routes** | 3 new member settings routes + 2 challenge routes |
| **Views** | Add 2FA tab to `settings.blade.php`; new `two-factor-challenge.blade.php` |
| **QR Code** | Client-side rendering via `qrcode.js` (CDN, ~3KB) — no extra Composer package |

---

## Detailed Implementation Steps

### Step 1 — Install Composer Package

```bash
composer require pragmarx/google2fa-laravel
```

- Handles TOTP secret generation, `otpauth://` URL generation, and code verification.
- QR code is rendered **client-side** using `qrcode.js` — no server-side QR package needed.

---

### Step 2 — Database Migration

Create migration: `add_two_factor_columns_to_platform_members_table`

```php
Schema::table('platform_members', function (Blueprint $table) {
    $table->text('two_factor_secret_key')->nullable()->after('hashed_login_password');
    $table->timestamp('two_factor_enabled_at_timestamp')->nullable()->after('two_factor_secret_key');
});
```

- **`two_factor_secret_key`** — Encrypted TOTP secret (via Laravel's `encrypted` cast). Populated when user clicks "Enable 2FA", but 2FA is not active until confirmed.
- **`two_factor_enabled_at_timestamp`** — `NULL` = 2FA not active; set = 2FA is active and enforced at login.

---

### Step 3 — Update `PlatformMember` Model

Add to `$fillable`:
```php
'two_factor_secret_key',
'two_factor_enabled_at_timestamp',
```

Add to `$hidden`:
```php
'two_factor_secret_key',
```

Add to `$casts`:
```php
'two_factor_secret_key' => 'encrypted',
'two_factor_enabled_at_timestamp' => 'datetime',
```

Add helper:
```php
public function hasTwoFactorEnabled(): bool
{
    return !is_null($this->two_factor_enabled_at_timestamp);
}
```

---

### Step 4 — Routes

Add to the `member.` route group in `web.php`:

```php
// 2FA Settings
Route::post('/settings/two-factor/enable', [MemberController::class, 'enableTwoFactor'])->name('settings.two-factor.enable');
Route::post('/settings/two-factor/confirm', [MemberController::class, 'confirmTwoFactor'])->name('settings.two-factor.confirm');
Route::delete('/settings/two-factor', [MemberController::class, 'disableTwoFactor'])->name('settings.two-factor.disable');
```

Add **semi-authenticated** routes (outside the auth middleware group, user not yet fully logged in):

```php
Route::get('/two-factor-challenge', [OtpAuthController::class, 'showTwoFactorChallenge'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [OtpAuthController::class, 'verifyTwoFactorChallenge'])->name('two-factor.challenge.verify');
```

---

### Step 5 — `MemberController` Methods

#### `enableTwoFactor()`
1. Generate a new TOTP secret via `$google2fa->generateSecretKey()`.
2. Store it (encrypted) on the member record. Do NOT set `two_factor_enabled_at_timestamp` yet.
3. Redirect back — the 2FA tab detects the secret exists without `enabled_at` and shows the QR + confirm form.

#### `confirmTwoFactor(Request $request)`
1. Validate `code` — 6-digit string.
2. Verify against stored secret using `$google2fa->verifyKey($secret, $code)`.
3. If valid → set `two_factor_enabled_at_timestamp = now()`, redirect back with success.
4. If invalid → return error.

#### `disableTwoFactor()`
1. Set `two_factor_secret_key = null`, `two_factor_enabled_at_timestamp = null`.
2. Redirect back with success.

---

### Step 6 — Login Flow Modification (`OtpAuthController`)

#### In both `verifyOtp()` and `loginWithPassword()`:

After successful primary auth, **before** calling `Auth::login()`:

```php
if ($member->hasTwoFactorEnabled()) {
    session(['two_factor_member_id' => $member->id]);
    session()->forget(['otp_email', 'otp_member_id']);
    return redirect()->route('two-factor.challenge');
}

Auth::login($member, true);
```

#### New `showTwoFactorChallenge()`:
```php
public function showTwoFactorChallenge()
{
    if (!session('two_factor_member_id')) {
        return redirect()->route('login-register');
    }
    return view('pages.two-factor-challenge');
}
```

#### New `verifyTwoFactorChallenge(Request $request)`:
1. Validate `code` (6-digit).
2. Load member from `session('two_factor_member_id')`.
3. Verify with `$google2fa->verifyKey()`.
4. If valid → `Auth::login($member, true)`, clear `two_factor_member_id` from session, set active account, redirect to home.
5. If invalid → return error.

No middleware needed — since `Auth::login()` is never called until TOTP is verified, Laravel's existing `auth` middleware blocks access to all protected routes.

---

### Step 7 — UI: Add "2FA" Tab to Settings Page

Add a 4th tab button after "Login Password" in `settings.blade.php` (matching existing style):

```blade
<button @click="activeTab = 'two-factor'" ...>
    <span class="flex items-center justify-center gap-2">
        <svg ...shield-check icon...>
        {{ __translator('2FA') }}
    </span>
</button>
```

#### 2FA Tab Content — Three States:

**State A: 2FA Not Enabled** (`two_factor_secret_key` is null)
- Explanation: "Add an extra layer of security using an authenticator app (Google Authenticator, Authy, etc.)."
- Button: "Enable 2FA" → POST to `member.settings.two-factor.enable`

**State B: Awaiting Confirmation** (`two_factor_secret_key` exists, `two_factor_enabled_at_timestamp` is null)
- QR code rendered client-side via `qrcode.js` from the `otpauth://` URL
- Text secret displayed for manual entry
- 6-digit code input
- Button: "Verify & Activate" → POST to `member.settings.two-factor.confirm`
- Button: "Cancel" → DELETE to `member.settings.two-factor.disable`

**State C: 2FA Active** (`two_factor_enabled_at_timestamp` is set)
- Green badge: "2FA is enabled"
- Button: "Disable 2FA" (danger, with confirm dialog) → DELETE to `member.settings.two-factor.disable`

---

### Step 8 — Two-Factor Challenge Page (`two-factor-challenge.blade.php`)

Reuses the same layout as the existing OTP verify page:
- Shield icon + heading: "Two-Factor Authentication"
- Subtext: "Enter the 6-digit code from your authenticator app"
- 6-digit input (centered, large, tracking-widest)
- Submit button: "Verify"
- Link: "← Back to login"

---

## Security Considerations

1. **Secret encryption** — TOTP secrets stored encrypted at rest via Laravel's `encrypted` cast.
2. **Session-based 2FA gate** — Member is NOT `Auth::login()`'d until TOTP is verified. No extra middleware needed.
3. **Rate limiting** — Apply throttle to the 2FA challenge route (e.g., 5 attempts per minute).
4. **TOTP window** — Default window of 1 (allows ±30 seconds clock drift).
5. **Logout clears session** — `session('two_factor_member_id')` is naturally cleared when session is invalidated on logout.
6. **Lost device** — Admin disables 2FA for the user via admin panel impersonation (already exists).

---

## File Change Summary

| File | Action |
|---|---|
| `composer.json` | Add `pragmarx/google2fa-laravel` |
| `database/migrations/xxxx_add_two_factor_to_platform_members.php` | **New** — add 2 columns |
| `app/Models/PlatformMember.php` | Update `$fillable`, `$hidden`, `$casts`; add `hasTwoFactorEnabled()` |
| `app/Http/Controllers/MemberController.php` | Add `enableTwoFactor()`, `confirmTwoFactor()`, `disableTwoFactor()` |
| `app/Http/Controllers/Auth/OtpAuthController.php` | Modify `verifyOtp()` + `loginWithPassword()` to gate on 2FA; add `showTwoFactorChallenge()` + `verifyTwoFactorChallenge()` |
| `routes/web.php` | Add 3 settings routes + 2 challenge routes |
| `resources/views/pages/member/settings.blade.php` | Add 4th "2FA" tab with 3-state content |
| `resources/views/pages/two-factor-challenge.blade.php` | **New** — 2FA challenge page |

**Total: 7 files modified, 1 new view, 1 new migration.**

---

## User Flow Diagrams

### Setup Flow
```
Member Settings → 2FA Tab → Click "Enable 2FA"
  → QR Code + Secret displayed
  → User scans QR in authenticator app
  → User enters 6-digit code → "Verify & Activate"
  → 2FA is now active
```

### Login Flow (2FA Active)
```
Login Page → Email + OTP/Password → Verify
  → System detects 2FA is enabled
  → Redirect to /two-factor-challenge
  → User enters 6-digit TOTP code
  → Verify → Auth::login() → Redirect to home
```

### Login Flow (2FA Inactive)
```
Login Page → Email + OTP/Password → Verify
  → No 2FA → Auth::login() → Redirect to home (unchanged)
```

---

## Implementation Order

1. Install package (`pragmarx/google2fa-laravel`)
2. Run migration (2 columns)
3. Update `PlatformMember` model
4. Add `MemberController` 2FA methods (3 methods)
5. Add 2FA tab UI to settings page
6. Modify login flow in `OtpAuthController` + add challenge methods
7. Create challenge page view
8. Test full flow end-to-end

---

## Future v2 Enhancements (Deferred)

- **Recovery codes** — 8 single-use backup codes for lost-device self-service
- **Remember device** — Skip 2FA on trusted devices for 30 days
- **Admin 2FA dashboard** — See which members have 2FA enabled
