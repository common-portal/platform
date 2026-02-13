<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\OtpAuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\Webhook\ShFinancialController;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Logout Route (No Middleware - Prevents 419 Errors)
|--------------------------------------------------------------------------
*/

// GET logout - clears session without validation to prevent 419 errors
Route::get('/logout', function () {
    try {
        // Preserve language preference
        $preferredLanguage = session('preferred_language');
        
        \Illuminate\Support\Facades\Auth::logout();
        Session::flush();
        Session::regenerate();
        
        // Restore language preference
        if ($preferredLanguage) {
            session(['preferred_language' => $preferredLanguage]);
        }
    } catch (\Exception $e) {
        // Silently catch any session/auth errors
    }
    return redirect('/login-register')->withHeaders([
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
})->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return view('pages.homepage-authenticated');
    }
    return view('pages.homepage-guest');
})->name('home');

// Language preference (available to guests too)
Route::post('/language', function () {
    $language_code = request('language_code');
    if (auth()->check()) {
        auth()->user()->update(['preferred_language_code' => $language_code]);
    }
    session(['preferred_language' => $language_code]);
    return response()->json(['success' => true]);
})->name('language.update');

// Invitation acceptance (public - works for logged in or guests)
Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.accept');
Route::post('/invitation/{token}/accept', [InvitationController::class, 'accept'])->name('invitation.accept.submit');

// Support page (public)
Route::get('/support', [SupportController::class, 'index'])->name('support');
Route::post('/support', [SupportController::class, 'submit'])->name('support.submit');

/*
|--------------------------------------------------------------------------
| Authentication Routes (OTP-Primary)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login-register', [OtpAuthController::class, 'showLoginRegister'])->name('login-register');
    Route::post('/login-register', [OtpAuthController::class, 'sendOtp'])->name('otp.send');
    Route::get('/verify', [OtpAuthController::class, 'showVerifyForm'])->name('otp.verify.form');
    Route::post('/verify', [OtpAuthController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('/verify/resend', [OtpAuthController::class, 'resendOtp'])->name('otp.resend');
    Route::post('/login/password', [OtpAuthController::class, 'loginWithPassword'])->name('login.password');
});

Route::post('/logout', [OtpAuthController::class, 'logout'])->name('logout')->middleware('auth');

// 2FA Challenge (semi-authenticated - user passed step 1 but not yet fully logged in)
Route::get('/two-factor-challenge', [OtpAuthController::class, 'showTwoFactorChallenge'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [OtpAuthController::class, 'verifyTwoFactorChallenge'])->name('two-factor.challenge.verify');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
])->group(function () {
    
    // Dashboard (redirect to homepage for now)
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    // Account Routes
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/settings', [AccountController::class, 'showSettings'])->name('settings');
        Route::post('/settings', [AccountController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/logo', [AccountController::class, 'uploadLogo'])->name('settings.logo');
        Route::delete('/settings/logo', [AccountController::class, 'removeLogo'])->name('settings.logo.remove');
        Route::delete('/delete', [AccountController::class, 'destroy'])->name('delete');
        
        Route::get('/create', [AccountController::class, 'showCreate'])->name('create');
        Route::post('/create', [AccountController::class, 'store'])->name('store');
        
        Route::get('/dashboard', [AccountController::class, 'dashboard'])->name('dashboard');
        
        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::get('/team/invite', [TeamController::class, 'showInvite'])->name('team.invite');
        Route::post('/team/invite', [TeamController::class, 'sendInvite'])->name('team.invite.send');
        Route::post('/team/invite/{invitation_id}/resend', [TeamController::class, 'resendInvite'])->name('team.invite.resend');
        Route::post('/team/invite/{invitation_id}/cancel', [TeamController::class, 'cancelInvite'])->name('team.invite.cancel');
        Route::post('/team/{membership_id}/permissions', [TeamController::class, 'updatePermissions'])->name('team.permissions');
        Route::post('/team/{membership_id}/revoke', [TeamController::class, 'revoke'])->name('team.revoke');
        Route::post('/team/{membership_id}/reactivate', [TeamController::class, 'reactivate'])->name('team.reactivate');
        
        Route::get('/transactions', [AccountController::class, 'transactions'])->name('transactions');
        
        Route::get('/switch/{account_id}', function ($account_id) {
            // Verify user has active membership in this account (exclude soft-deleted)
            $hasAccess = auth()->user()->tenant_accounts()
                ->where('tenant_accounts.id', $account_id)
                ->where('is_soft_deleted', false)
                ->wherePivot('membership_status', 'membership_active')
                ->exists();
            
            if (!$hasAccess) {
                abort(403, 'You do not have access to this account.');
            }
            
            // If admin was impersonating and switches to their own account, exit impersonation
            if (session('admin_impersonating_from')) {
                session()->forget('admin_impersonating_from');
            }
            
            session(['active_account_id' => $account_id]);

            // Persist last active account for next login
            \App\Models\MemberLastActiveAccount::remember(auth()->id(), $account_id);

            return redirect()->back();
        })->name('switch');
    });

    // Member Routes
    Route::prefix('member')->name('member.')->group(function () {
        Route::get('/settings', [MemberController::class, 'showSettings'])->name('settings');
        Route::post('/settings/profile', [MemberController::class, 'updateProfile'])->name('settings.profile');
        Route::post('/settings/avatar', [MemberController::class, 'uploadAvatar'])->name('settings.avatar');
        Route::delete('/settings/avatar', [MemberController::class, 'removeAvatar'])->name('settings.avatar.remove');
        Route::post('/settings/email', [MemberController::class, 'requestEmailChange'])->name('settings.email');
        Route::get('/settings/email/verify', [MemberController::class, 'showEmailVerifyForm'])->name('settings.email.verify');
        Route::post('/settings/email/verify', [MemberController::class, 'verifyEmailChange'])->name('settings.email.verify.submit');
        Route::post('/settings/password', [MemberController::class, 'updatePassword'])->name('settings.password');
        Route::delete('/settings/password', [MemberController::class, 'removePassword'])->name('settings.password.remove');
        Route::post('/settings/language', [MemberController::class, 'updateLanguage'])->name('settings.language');
        
        // 2FA Settings
        Route::post('/settings/two-factor/enable', [MemberController::class, 'enableTwoFactor'])->name('settings.two-factor.enable');
        Route::post('/settings/two-factor/confirm', [MemberController::class, 'confirmTwoFactor'])->name('settings.two-factor.confirm');
        Route::delete('/settings/two-factor', [MemberController::class, 'disableTwoFactor'])->name('settings.two-factor.disable');
    });

    // Admin exit impersonation (no middleware - must always be accessible)
    Route::get('/administrator/exit-impersonation', [AdminController::class, 'exitImpersonation'])->name('admin.exit-impersonation');

    // Administrator Routes (platform admins only)
    Route::prefix('administrator')->name('admin.')->middleware('platform.admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/members', [AdminController::class, 'members'])->name('members');
        Route::post('/members/{member_id}/toggle-admin', [AdminController::class, 'toggleAdmin'])->name('members.toggle-admin');
        Route::post('/members/{member_id}/update-email', [AdminController::class, 'updateEmail'])->name('members.update-email');
        Route::post('/members/{member_id}/impersonate', [AdminController::class, 'impersonate'])->name('members.impersonate');
        Route::get('/accounts', [AdminController::class, 'accounts'])->name('accounts');
        Route::get('/accounts/search', [AdminController::class, 'accountsSearch'])->name('accounts.search');
        Route::post('/accounts/{account_id}/impersonate', [AdminController::class, 'impersonateAccount'])->name('accounts.impersonate');
        Route::get('/members/search', [AdminController::class, 'membersSearch'])->name('members.search');
        Route::get('/theme', [AdminController::class, 'theme'])->name('theme');
        Route::post('/theme', [AdminController::class, 'updateTheme'])->name('theme.update');
        Route::get('/menu-items', [AdminController::class, 'menuItems'])->name('menu-items');
        Route::post('/menu-items', [AdminController::class, 'updateMenuItems'])->name('menu-items.update');
        
        // Support Tickets Management
        Route::get('/support-tickets', [AdminController::class, 'supportTickets'])->name('support-tickets');
        Route::get('/support-tickets/search', [AdminController::class, 'supportTicketsSearch'])->name('support-tickets.search');
        Route::get('/support-tickets/{ticket_id}', [AdminController::class, 'supportTicketShow'])->name('support-tickets.show');
        Route::post('/support-tickets/{ticket_id}/respond', [AdminController::class, 'supportTicketRespond'])->name('support-tickets.respond');
        Route::post('/support-tickets/{ticket_id}/status', [AdminController::class, 'supportTicketStatus'])->name('support-tickets.status');
        Route::post('/support-tickets/{ticket_id}/assign', [AdminController::class, 'supportTicketAssign'])->name('support-tickets.assign');
        
        // Transactions Management
        Route::get('/transactions', [AdminController::class, 'accounting'])->name('transactions');
        Route::get('/transactions/transaction/{transaction_hash}', [AdminController::class, 'getTransaction'])->name('transactions.transaction');
        Route::post('/transactions/phase1', [AdminController::class, 'storePhase1Received'])->name('transactions.phase1');
        Route::patch('/transactions/phase2/{transaction_id}', [AdminController::class, 'updatePhase2Exchanged'])->name('transactions.phase2');
        Route::patch('/transactions/phase3/{transaction_id}', [AdminController::class, 'updatePhase3Settled'])->name('transactions.phase3');
        
        // IBAN Host Banks Management
        Route::get('/iban-host-banks', [AdminController::class, 'ibanHostBanks'])->name('iban-host-banks');
        Route::get('/iban-host-banks/list', [AdminController::class, 'ibanHostBanksList'])->name('iban-host-banks.list');
        Route::get('/iban-host-banks/{hash}', [AdminController::class, 'ibanHostBankGet'])->name('iban-host-banks.get');
        Route::post('/iban-host-banks', [AdminController::class, 'ibanHostBankStore'])->name('iban-host-banks.store');
        Route::put('/iban-host-banks/{hash}', [AdminController::class, 'ibanHostBankUpdate'])->name('iban-host-banks.update');
        Route::delete('/iban-host-banks/{hash}', [AdminController::class, 'ibanHostBankDelete'])->name('iban-host-banks.delete');
        
        // IBAN Management
        Route::get('/ibans', [AdminController::class, 'ibans'])->name('ibans');
        Route::get('/ibans/list', [AdminController::class, 'ibansList'])->name('ibans.list');
        Route::get('/ibans/{iban_hash}', [AdminController::class, 'ibanGet'])->name('ibans.get');
        Route::post('/ibans', [AdminController::class, 'ibanStore'])->name('ibans.store');
        Route::put('/ibans/{iban_hash}', [AdminController::class, 'ibanUpdate'])->name('ibans.update');
        Route::delete('/ibans/{iban_hash}', [AdminController::class, 'ibanDelete'])->name('ibans.delete');
        
        // Crypto Wallets Management
        Route::get('/wallets', [AdminController::class, 'wallets'])->name('wallets');
        Route::get('/wallets/list', [AdminController::class, 'walletsList'])->name('wallets.list');
        Route::get('/wallets/{hash}', [AdminController::class, 'walletGet'])->name('wallets.get');
        Route::post('/wallets', [AdminController::class, 'walletStore'])->name('wallets.store');
        Route::put('/wallets/{hash}', [AdminController::class, 'walletUpdate'])->name('wallets.update');
        Route::delete('/wallets/{hash}', [AdminController::class, 'walletDelete'])->name('wallets.delete');
        Route::post('/wallets/{hash}/send', [AdminController::class, 'walletSend'])->name('wallets.send');
    });

    // Optional Module Routes
    Route::prefix('modules')->name('modules.')->group(function () {
        // Developer Tools
        Route::get('/developer', [ModuleController::class, 'developer'])->name('developer');
        Route::get('/developer/{tab?}', [ModuleController::class, 'developer'])->name('developer.tab');
        
        // Webhooks
        Route::post('/webhooks', [ModuleController::class, 'webhookStore'])->name('webhooks.store');
        Route::put('/webhooks/{webhook_id}', [ModuleController::class, 'webhookUpdate'])->name('webhooks.update');
        Route::post('/webhooks/{webhook_id}/toggle', [ModuleController::class, 'webhookToggle'])->name('webhooks.toggle');
        Route::delete('/webhooks/{webhook_id}', [ModuleController::class, 'webhookDestroy'])->name('webhooks.destroy');
        
        // API Keys
        Route::post('/api-keys', [ModuleController::class, 'apiKeyStore'])->name('apikeys.store');
        Route::post('/api-keys/{api_key_id}/toggle', [ModuleController::class, 'apiKeyToggle'])->name('apikeys.toggle');
        Route::delete('/api-keys/{api_key_id}', [ModuleController::class, 'apiKeyDestroy'])->name('apikeys.destroy');
        
        // Support Tickets
        Route::get('/support', [ModuleController::class, 'supportIndex'])->name('support.index');
        Route::get('/support/create', [ModuleController::class, 'supportCreate'])->name('support.create');
        Route::post('/support', [ModuleController::class, 'supportStore'])->name('support.store');
        Route::get('/support/{ticket_id}', [ModuleController::class, 'supportShow'])->name('support.show');
        Route::post('/support/{ticket_id}/reply', [ModuleController::class, 'supportReply'])->name('support.reply');
        
        // Transactions
        Route::get('/transactions', [ModuleController::class, 'transactions'])->name('transactions');
        
        // Billing
        Route::get('/billing', [ModuleController::class, 'billing'])->name('billing');
        
        // IBANs
        Route::get('/ibans', [ModuleController::class, 'ibans'])->name('ibans');
        
        // Wallets
        Route::get('/wallets', [ModuleController::class, 'wallets'])->name('wallets');
        Route::get('/wallets/balances', [ModuleController::class, 'walletBalances'])->name('wallets.balances');
        Route::get('/wallets/{hash}/balance', [ModuleController::class, 'walletBalance'])->name('wallets.balance');
        Route::get('/wallets/{hash}/transactions', [ModuleController::class, 'walletTransactions'])->name('wallets.transactions');
        Route::get('/wallets/tx/{hash}/detail', [ModuleController::class, 'walletTxDetail'])->name('wallets.tx.detail');
        Route::post('/wallets/{hash}/send', [ModuleController::class, 'walletSend'])->name('wallets.send');
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (No CSRF, No Auth)
|--------------------------------------------------------------------------
*/

// SH Financial webhooks
Route::post('/webhooks/sh-financial/v1/{trailing?}', [ShFinancialController::class, 'handle'])
    ->where('trailing', '\/?\/?')
    ->name('webhooks.sh-financial.v1');

// WalletIDs.net webhooks (payment_detected, balance_changed)
Route::post('/webhooks/walletids-net/v1/{trailing?}', [AdminController::class, 'walletIdsWebhook'])
    ->where('trailing', '\/?\/?')
    ->name('webhooks.walletids');
