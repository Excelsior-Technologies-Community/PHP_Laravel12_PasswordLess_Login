# PHP_Laravel12_PasswordLess_Login

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
    <img src="https://img.shields.io/badge/Passwordless-Magic%20Link-4F46E5?style=for-the-badge&logo=keycdn&logoColor=white" />
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
    <img src="https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
    <img src="https://img.shields.io/badge/Auth-Secure-16A34A?style=for-the-badge&logo=shield&logoColor=white" />
    <img src="https://img.shields.io/badge/License-MIT-22C55E?style=for-the-badge" />
</p>


---

## Overview

This project implements a **Passwordless Authentication system (Magic Link Login)** using **Laravel 12**.

Users can log in **without entering a password**. Instead, they provide their email address and receive a **secure, one-time login link** via email. Clicking the link automatically logs the user in and redirects them to the dashboard.

This project is ideal for:

* Modern web applications
* Admin panels
* SaaS products
* Secure authentication without passwords

---

## Features

*  Passwordless login using Magic Link
*  One-time login token
*  Token expiry (10 minutes)
*  Session-based authentication
*  Secure login verification
*  Modern Login UI
*  Clean Dashboard UI
*  Logout functionality
*  Laravel Auth middleware protection
*  No third-party authentication packages

---

## Folder Structure

```
PHP_Laravel12_PasswordLess_Login
├── app
│   ├── Http
│   │   └── Controllers
│   │       └── Auth
│   │           └── PasswordlessController.php
│   └── Models
│       └── User.php
│
├── database
│   └── migrations
│       └── add_passwordless_fields_to_users_table.php
│
├── resources
│   └── views
│       ├── auth
│       │   └── login.blade.php
│       └── dashboard.blade.php
│
├── routes
│   └── web.php
│
├── storage
│   └── logs
│       └── laravel.log
│
├── .env
└── README.md
```

---

## STEP 1: Install Laravel 12

```bash
composer create-project laravel/laravel laravel-passwordless

php artisan serve
```

Open in browser:

```
http://127.0.0.1:8000
```

---

## STEP 2: Configure .env

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:HJNMWVKEPjM9LpZNLZDdymNkckjTDADLP1UazKA0SxY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pass_less
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log
```

Create database:

```sql
CREATE DATABASE pass_less;
```

---

## STEP 3: Run Default Migrations

```bash
php artisan migrate
```

---

## STEP 4: Add Passwordless Columns to Users Table

```bash
php artisan make:migration add_passwordless_fields_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
```

```bash
php artisan migrate
```

---

## STEP 5: Update User Model

**app/Models/User.php**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'login_token',
        'token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'token_expires_at' => 'datetime',
        ];
    }
}
```

---

## STEP 6: Create Passwordless Controller

```bash
php artisan make:controller Auth/PasswordlessController
```

**app/Http/Controllers/Auth/PasswordlessController.php**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordlessController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $token = Str::random(64);

        $user->update([
            'login_token' => $token,
            'token_expires_at' => now()->addMinutes(10),
        ]);

        $link = route('login.verify', ['token' => $token]);

        Mail::raw("Click this link to login: $link", function ($mail) use ($user) {
            $mail->to($user->email)
                 ->subject('Your Login Link');
        });

        return back()->with('success', 'Login link sent to your email.');
    }

    public function verify(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            abort(404);
        }

        $user = User::where('login_token', $token)
            ->where('token_expires_at', '>', now())
            ->firstOrFail();

        $user->update([
            'login_token' => null,
            'token_expires_at' => null,
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }
}
```

---

## STEP 7: Routes

**routes/web.php**

```php
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\PasswordlessController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [PasswordlessController::class, 'showLogin'])->name('login');
Route::post('/login', [PasswordlessController::class, 'sendLink'])->name('login.send');
Route::get('/login/verify', [PasswordlessController::class, 'verify'])->name('login.verify');

Route::middleware('auth')->get('/dashboard', function () {
    return view('dashboard');
});

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');
```

---

## STEP 8: Login View

**resources/views/auth/login.blade.php**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Passwordless Login</title>
    <style>
        body { margin:0;height:100vh;display:flex;justify-content:center;align-items:center;background:linear-gradient(135deg,#020617,#0f172a);font-family:Arial;color:#e5e7eb; }
        .login-box { background:#020617;padding:40px;width:350px;border-radius:12px;box-shadow:0 15px 40px rgba(0,0,0,0.5);text-align:center; }
        .login-box h2 { color:#38bdf8; }
        .login-box input { width:100%;padding:12px;margin-bottom:15px;border-radius:6px;border:none; }
        .login-box button { width:100%;padding:12px;background:#38bdf8;border:none;border-radius:6px;font-weight:bold; }
    </style>
</head>
<body>
<div class="login-box">
    <h2> Passwordless Login</h2>
    @if(session('success')) <div>{{ session('success') }}</div> @endif
    @if($errors->any()) <div>{{ $errors->first() }}</div> @endif
    <form method="POST" action="{{ route('login.send') }}">
        @csrf
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Login Link</button>
    </form>
</div>
</body>
</html>
```

---

## STEP 9: Dashboard View

**resources/views/dashboard.blade.php**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
<h2>Welcome {{ auth()->user()->name }}</h2>
<p>Email: {{ auth()->user()->email }}</p>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button>Logout</button>
</form>
</body>
</html>
```

---

## STEP 10: Create Test User

```bash
php artisan tinker
```

```php
App\Models\User::create([
 'name' => 'Test User',
 'email' => 'test@example.com',
 'password' => bcrypt('password')
]);
```

---

## STEP 11: Test Flow

1. Open `http://127.0.0.1:8000/login` And Enter email

   <img width="732" height="533" alt="Screenshot 2026-01-12 171501" src="https://github.com/user-attachments/assets/58228bd8-db48-4eca-8809-e2f77e6f698c" />

   <img width="749" height="516" alt="Screenshot 2026-01-12 171512" src="https://github.com/user-attachments/assets/9db0ab70-bd67-4140-838a-fd6e99e82ab1" />


2. Copy link from `storage/logs/laravel.log`

   <img width="1044" height="219" alt="Screenshot 2026-01-12 171538" src="https://github.com/user-attachments/assets/29a4e1f0-0bd2-4580-b865-5e4b860115a3" />

3. Paste link in browser

   http://127.0.0.1:8000/login/verify?token=XCBvRo2YK4kpxlBBKHE9saI3M6Y2kQiwZzpvylsau0wUFbmyuCtu6pG1uphSwL18
   
4. Logged in → `/dashboard`

   <img width="1919" height="424" alt="Screenshot 2026-01-12 171342" src="https://github.com/user-attachments/assets/0ed214a8-dd3d-4f6c-ab4b-59e63068cf12" />



---

## Security Notes

* Tokens are one-time use
* Tokens expire automatically
* Session-based authentication
