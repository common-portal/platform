<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\OtpAuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TeamController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('pages.homepage');
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
        Route::delete('/delete', [AccountController::class, 'destroy'])->name('delete');
        
        Route::get('/create', [AccountController::class, 'showCreate'])->name('create');
        Route::post('/create', [AccountController::class, 'store'])->name('store');
        
        Route::get('/dashboard', function () {
            return view('pages.account.dashboard');
        })->name('dashboard');
        
        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::get('/team/invite', [TeamController::class, 'showInvite'])->name('team.invite');
        Route::post('/team/invite', [TeamController::class, 'sendInvite'])->name('team.invite.send');
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
        Route::get('/settings', function () {
            return view('pages.member.settings');
        })->name('settings');
    });

    // Administrator Routes (platform admins only)
    Route::prefix('administrator')->name('admin.')->group(function () {
        Route::get('/', function () {
            if (!auth()->user()->is_platform_administrator) {
                abort(403);
            }
            return view('pages.administrator.index');
        })->name('index');
        
        Route::get('/exit-impersonation', function () {
            session()->forget(['impersonating_account_id', 'impersonating_account_name']);
            return redirect()->route('home');
        })->name('exit-impersonation');
    });
});
