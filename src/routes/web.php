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
        
        Route::get('/dashboard', function () {
            return view('pages.account.dashboard');
        })->name('dashboard');
        
        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::get('/team/invite', [TeamController::class, 'showInvite'])->name('team.invite');
        Route::post('/team/invite', [TeamController::class, 'sendInvite'])->name('team.invite.send');
        Route::post('/team/invite/{invitation_id}/resend', [TeamController::class, 'resendInvite'])->name('team.invite.resend');
        Route::post('/team/invite/{invitation_id}/cancel', [TeamController::class, 'cancelInvite'])->name('team.invite.cancel');
        Route::post('/team/{membership_id}/permissions', [TeamController::class, 'updatePermissions'])->name('team.permissions');
        Route::post('/team/{membership_id}/revoke', [TeamController::class, 'revoke'])->name('team.revoke');
        Route::post('/team/{membership_id}/reactivate', [TeamController::class, 'reactivate'])->name('team.reactivate');
        
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
            
            session(['active_account_id' => $account_id]);
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
    });

    // Administrator Routes (platform admins only)
    Route::prefix('administrator')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/members', [AdminController::class, 'members'])->name('members');
        Route::post('/members/{member_id}/toggle-admin', [AdminController::class, 'toggleAdmin'])->name('members.toggle-admin');
        Route::post('/members/{member_id}/impersonate', [AdminController::class, 'impersonate'])->name('members.impersonate');
        Route::get('/accounts', [AdminController::class, 'accounts'])->name('accounts');
        Route::post('/accounts/{account_id}/impersonate', [AdminController::class, 'impersonateAccount'])->name('accounts.impersonate');
        Route::get('/theme', [AdminController::class, 'theme'])->name('theme');
        Route::post('/theme', [AdminController::class, 'updateTheme'])->name('theme.update');
        Route::get('/menu-items', [AdminController::class, 'menuItems'])->name('menu-items');
        Route::post('/menu-items', [AdminController::class, 'updateMenuItems'])->name('menu-items.update');
        Route::get('/exit-impersonation', [AdminController::class, 'exitImpersonation'])->name('exit-impersonation');
    });

    // Optional Module Routes
    Route::prefix('modules')->name('modules.')->group(function () {
        // Developer Tools
        Route::get('/developer', [ModuleController::class, 'developer'])->name('developer');
        
        // Support Tickets
        Route::get('/support', [ModuleController::class, 'supportIndex'])->name('support.index');
        Route::get('/support/create', [ModuleController::class, 'supportCreate'])->name('support.create');
        Route::post('/support', [ModuleController::class, 'supportStore'])->name('support.store');
        Route::get('/support/{ticket_id}', [ModuleController::class, 'supportShow'])->name('support.show');
        
        // Transactions
        Route::get('/transactions', [ModuleController::class, 'transactions'])->name('transactions');
        
        // Billing
        Route::get('/billing', [ModuleController::class, 'billing'])->name('billing');
    });
});
