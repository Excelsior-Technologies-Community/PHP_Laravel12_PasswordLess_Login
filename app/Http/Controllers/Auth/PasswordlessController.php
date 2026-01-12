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
