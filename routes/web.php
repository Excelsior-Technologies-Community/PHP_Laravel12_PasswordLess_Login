<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\PasswordlessController;

Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Passwordless Authentication
|--------------------------------------------------------------------------
*/

Route::get('/login', [
    PasswordlessController::class,
    'showLogin'
])->name('login');

Route::post('/login', [
    PasswordlessController::class,
    'sendLink'
])->name('login.send');

Route::get('/login/verify', [
    PasswordlessController::class,
    'verify'
])->name('login.verify');

// 1. 6-Digit OTP Verification & Resend
Route::post('/login/verify-otp', [
    PasswordlessController::class,
    'verifyOtp'
])->name('login.verify-otp');

Route::post('/login/resend-otp', [
    PasswordlessController::class,
    'resendOtp'
])->name('login.resend-otp');

// 2. Instant QR Code Cross-Device Login
Route::get('/login/qr-session', [
    PasswordlessController::class,
    'generateQrSession'
])->name('login.qr-session');

Route::get('/login/qr-status', [
    PasswordlessController::class,
    'checkQrStatus'
])->name('login.qr-status');

Route::post('/login/qr-approve', [
    PasswordlessController::class,
    'approveQrLogin'
])->name('login.qr-approve');

// 4. WebAuthn / Passkeys Login Routes (Public)
Route::post('/passkeys/login-options', [
    PasswordlessController::class,
    'passkeyLoginOptions'
])->name('passkeys.login-options');

Route::post('/passkeys/login', [
    PasswordlessController::class,
    'passkeyLogin'
])->name('passkeys.login');

Route::post('/passkeys/demo-register', [
    PasswordlessController::class,
    'demoRegisterPasskey'
])->name('passkeys.demo-register');


/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        PasswordlessController::class,
        'dashboard'
    ])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Full Login History
    |--------------------------------------------------------------------------
    */

    Route::get('/login-history', [
        PasswordlessController::class,
        'loginHistory'
    ])->name('login.history');

    /*
    |--------------------------------------------------------------------------
    | Export Login History
    |--------------------------------------------------------------------------
    */

    Route::get('/login-history/export', [
        PasswordlessController::class,
        'exportLoginHistory'
    ])->name('login.history.export');

    /*
    |--------------------------------------------------------------------------
    | Clear Login History
    |--------------------------------------------------------------------------
    */

    Route::delete('/login-history/clear', [
        PasswordlessController::class,
        'clearLoginHistory'
    ])->name('login.history.clear');

    /*
    |--------------------------------------------------------------------------
    | Revoke Active Magic Link
    |--------------------------------------------------------------------------
    */

    Route::post('/magic-link/revoke', [
        PasswordlessController::class,
        'revokeLink'
    ])->name('magic-link.revoke');

    /*
    |--------------------------------------------------------------------------
    | 3. Multi-Device Session Management (Remote Logout)
    |--------------------------------------------------------------------------
    */

    Route::delete('/sessions/logout-others', [
        PasswordlessController::class,
        'logoutOtherDevices'
    ])->name('sessions.logout-others');

    Route::delete('/sessions/{sessionId}', [
        PasswordlessController::class,
        'terminateSession'
    ])->name('sessions.terminate');

    /*
    |--------------------------------------------------------------------------
    | 4. Passkeys / WebAuthn Management (Authenticated)
    |--------------------------------------------------------------------------
    */

    Route::get('/passkeys/register-options', [
        PasswordlessController::class,
        'passkeyRegisterOptions'
    ])->name('passkeys.register-options');

    Route::post('/passkeys/register', [
        PasswordlessController::class,
        'passkeyRegister'
    ])->name('passkeys.register');

    Route::delete('/passkeys/{id}', [
        PasswordlessController::class,
        'deletePasskey'
    ])->name('passkeys.delete');

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', function () {

        Auth::logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();

        return redirect('/login');

    })->name('logout');
});