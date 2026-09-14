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

Route::get('/login', [PasswordlessController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [PasswordlessController::class, 'sendLink'])
    ->name('login.send');

Route::get('/login/verify', [PasswordlessController::class, 'verify'])
    ->name('login.verify');

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        $loginActivities = $user->loginActivities()
            ->latest('created_at')
            ->take(10)
            ->get();

        return view('dashboard', compact('loginActivities'));
    })->name('dashboard');

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