<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class PasswordlessController extends Controller
{
    /**
     * Display the passwordless login page.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Send a magic login link.
     */
    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        /*
        |--------------------------------------------------------------------------
        | 60 Second Cooldown
        |--------------------------------------------------------------------------
        */

        if (
            $user->magic_link_requested_at &&
            $user->magic_link_requested_at->gt(now()->subSeconds(60))
        ) {
            $remainingSeconds = 60 - now()->diffInSeconds(
                $user->magic_link_requested_at
            );

            $remainingSeconds = max(1, $remainingSeconds);

            return back()
                ->withInput()
                ->with(
                    'error',
                    "Please wait {$remainingSeconds} seconds before requesting another login link."
                )
                ->with('cooldown', $remainingSeconds);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate New Magic Link
        |--------------------------------------------------------------------------
        */

        $token = Str::random(64);

        $user->update([
            'login_token' => $token,
            'token_expires_at' => now()->addMinutes(10),
            'magic_link_requested_at' => now(),
            'magic_link_revoked_at' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Record Login Link Request
        |--------------------------------------------------------------------------
        */

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'Magic Link Requested',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Generate Login URL
        |--------------------------------------------------------------------------
        */

        $link = route('login.verify', [
            'token' => $token,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Send Magic Login Link
        |--------------------------------------------------------------------------
        */

        try {
            Mail::raw(
                "Hello {$user->name},\n\n"
                . "You requested a passwordless login link for "
                . config('app.name') . ".\n\n"
                . "Click the link below to securely log in:\n\n"
                . "{$link}\n\n"
                . "This login link will expire in 10 minutes "
                . "and can only be used once.\n\n"
                . "If you did not request this login link, "
                . "you can safely ignore this email.\n\n"
                . "Regards,\n"
                . config('app.name'),
                function ($mail) use ($user) {
                    $mail->to($user->email)
                        ->subject('Your Passwordless Login Link');
                }
            );
        } catch (Throwable $exception) {

            $user->update([
                'login_token' => null,
                'token_expires_at' => null,
                'magic_link_requested_at' => null,
            ]);

            LoginActivity::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'Magic Link Email',
                'status' => 'Failed - Email Delivery',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            Log::error('Passwordless login email failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'We could not send the login email. Please check your email configuration and try again.'
                );
        }

        return back()
            ->with(
                'success',
                'Login link sent successfully. Please check your email.'
            )
            ->with('cooldown', 60);
    }

    /**
     * Verify magic login token.
     */
    public function verify(Request $request)
    {
        $token = $request->query('token');

        /*
        |--------------------------------------------------------------------------
        | Missing Token
        |--------------------------------------------------------------------------
        */

        if (!$token) {
            return redirect()
                ->route('login')
                ->with('error', 'Invalid login link.');
        }

        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user = User::where('login_token', $token)->first();

        /*
        |--------------------------------------------------------------------------
        | Invalid Token
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            LoginActivity::create([
                'user_id' => null,
                'email' => null,
                'action' => 'Magic Link Login',
                'status' => 'Failed - Invalid Token',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This login link is invalid or has already been used.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Revoked Token
        |--------------------------------------------------------------------------
        */

        if ($user->magic_link_revoked_at) {

            LoginActivity::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'Magic Link Login',
                'status' => 'Failed - Revoked Token',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This login link has been revoked.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Expired Token
        |--------------------------------------------------------------------------
        */

        if (
            !$user->token_expires_at ||
            $user->token_expires_at->lte(now())
        ) {

            LoginActivity::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'Magic Link Login',
                'status' => 'Failed - Expired Token',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This login link has expired. Please request a new one.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Successful Login - One Time Token
        |--------------------------------------------------------------------------
        */

        $user->update([
            'login_token' => null,
            'token_expires_at' => null,
            'magic_link_revoked_at' => null,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | Record Successful Login
        |--------------------------------------------------------------------------
        */

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'Magic Link Login',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect('/dashboard')
            ->with('success', 'You have been logged in successfully.');
    }

    /**
     * Revoke the currently active magic link.
     */
    public function revokeLink(Request $request)
    {
        $user = $request->user();

        if (
            !$user->login_token ||
            !$user->token_expires_at ||
            $user->token_expires_at->lte(now())
        ) {
            return back()->with(
                'error',
                'There is no active magic login link to revoke.'
            );
        }

        $user->update([
            'login_token' => null,
            'token_expires_at' => null,
            'magic_link_revoked_at' => now(),
        ]);

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'Magic Link Revoked',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with(
            'success',
            'Your active magic login link has been revoked.'
        );
    }

    /**
     * Dashboard with statistics and security insights.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $activities = LoginActivity::where('user_id', $user->id);

        /*
        |--------------------------------------------------------------------------
        | Dashboard Statistics
        |--------------------------------------------------------------------------
        */

        $totalActivities = (clone $activities)->count();

        $successfulLogins = (clone $activities)
            ->where('status', 'Success')
            ->where('action', 'Magic Link Login')
            ->count();

        $failedLogins = (clone $activities)
            ->where('status', 'like', 'Failed%')
            ->count();

        $magicLinkRequests = (clone $activities)
            ->where('action', 'Magic Link Requested')
            ->count();

        $todayLogins = (clone $activities)
            ->where('action', 'Magic Link Login')
            ->where('status', 'Success')
            ->whereDate('created_at', today())
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Security Insights
        |--------------------------------------------------------------------------
        */

        $lastLogin = (clone $activities)
            ->where('action', 'Magic Link Login')
            ->where('status', 'Success')
            ->latest('created_at')
            ->first();

        $uniqueIpAddresses = (clone $activities)
            ->whereNotNull('ip_address')
            ->distinct('ip_address')
            ->count('ip_address');

        $uniqueBrowsers = (clone $activities)
            ->whereNotNull('user_agent')
            ->distinct('user_agent')
            ->count('user_agent');

        $recentFailedAttempts = (clone $activities)
            ->where('status', 'like', 'Failed%')
            ->latest('created_at')
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Activity
        |--------------------------------------------------------------------------
        */

        $loginActivities = (clone $activities)
            ->latest('created_at')
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'loginActivities',
            'totalActivities',
            'successfulLogins',
            'failedLogins',
            'magicLinkRequests',
            'todayLogins',
            'lastLogin',
            'uniqueIpAddresses',
            'uniqueBrowsers',
            'recentFailedAttempts'
        ));
    }

    /**
     * Full login history.
     */
    public function loginHistory(Request $request)
    {
        $user = $request->user();

        $query = LoginActivity::where('user_id', $user->id);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            if ($request->status === 'success') {
                $query->where('status', 'Success');
            }

            if ($request->status === 'failed') {
                $query->where('status', 'like', 'Failed%');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Action Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        /*
        |--------------------------------------------------------------------------
        | From Date
        |--------------------------------------------------------------------------
        */

        if ($request->filled('from_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->from_date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | To Date
        |--------------------------------------------------------------------------
        */

        if ($request->filled('to_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->to_date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric Pagination
        |--------------------------------------------------------------------------
        */

        $loginActivities = $query
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Action List
        |--------------------------------------------------------------------------
        */

        $actions = LoginActivity::where('user_id', $user->id)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('login-history', compact(
            'loginActivities',
            'actions'
        ));
    }

    /**
     * Export current user's login history as CSV.
     */
    public function exportLoginHistory(Request $request)
    {
        $user = $request->user();

        $activities = LoginActivity::where('user_id', $user->id)
            ->latest('created_at')
            ->get();

        $filename = 'login-history-' . now()->format('Y-m-d-H-i-s') . '.csv';

        return response()->streamDownload(function () use ($activities) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Email',
                'Action',
                'Status',
                'IP Address',
                'Browser / Device',
                'Date & Time',
            ]);

            foreach ($activities as $activity) {
                fputcsv($handle, [
                    $activity->id,
                    $activity->email,
                    $activity->action,
                    $activity->status,
                    $activity->ip_address,
                    $activity->user_agent,
                    optional($activity->created_at)
                        ->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);

        }, $filename);
    }

    /**
     * Clear current user's login history.
     */
    public function clearLoginHistory(Request $request)
    {
        $user = $request->user();

        LoginActivity::where('user_id', $user->id)->delete();

        return redirect()
            ->route('login.history')
            ->with(
                'success',
                'Your login history has been cleared successfully.'
            );
    }
}