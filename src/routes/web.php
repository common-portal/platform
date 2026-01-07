<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('pages.homepage');
})->name('home');

Route::get('/login-register', function () {
    return view('pages.login-register');
})->name('login-register');

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
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    
    // Dashboard (redirect to homepage for now)
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    // Account Routes
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/settings', function () {
            return view('pages.account.settings');
        })->name('settings');
        
        Route::get('/dashboard', function () {
            return view('pages.account.dashboard');
        })->name('dashboard');
        
        Route::get('/team', function () {
            return view('pages.account.team');
        })->name('team');
        
        Route::get('/create', function () {
            return view('pages.account.create');
        })->name('create');
        
        Route::get('/switch/{account_id}', function ($account_id) {
            session(['active_account_id' => $account_id]);
            return redirect()->back();
        })->name('switch');
    });

    // Member Routes
    Route::prefix('member')->name('member.')->group(function () {
        Route::get('/settings', function () {
            return view('pages.member.settings');
        })->name('settings');
        
        Route::post('/language', function () {
            $language_code = request('language_code');
            if (auth()->check()) {
                auth()->user()->update(['preferred_language_code' => $language_code]);
            }
            session(['preferred_language' => $language_code]);
            return response()->json(['success' => true]);
        })->name('language');
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
