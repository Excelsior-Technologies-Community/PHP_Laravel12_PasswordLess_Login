<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\PasswordlessController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [PasswordlessController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [PasswordlessController::class, 'sendLink'])
    ->name('login.send');

Route::get('/login/verify', [PasswordlessController::class, 'verify'])
    ->name('login.verify');

Route::middleware('auth')->get('/dashboard', function () {
    return view('dashboard');
});

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');
