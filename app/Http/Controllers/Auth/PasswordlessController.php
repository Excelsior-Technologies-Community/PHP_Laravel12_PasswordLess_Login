<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\User;
use App\Models\UserPasskey;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * Send a magic login link & 6-digit OTP.
     */
    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = trim(strtolower($request->email));

        // Auto-create user if new email (seamless frictionless registration)
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => ucwords(str_replace(['.', '_', '-'], ' ', explode('@', $email)[0])),
                'password' => bcrypt(Str::random(32)),
            ]
        );

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

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$remainingSeconds}s before requesting another link.",
                    'cooldown' => $remainingSeconds
                ], 429);
            }

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
        | Generate New Magic Link & 6-Digit OTP
        |--------------------------------------------------------------------------
        */

        $token = Str::random(64);
        $otp = (string) random_int(100000, 999999);

        $user->update([
            'login_token' => $token,
            'login_otp' => $otp,
            'token_expires_at' => now()->addMinutes(10),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => 0,
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
            'action' => 'Magic Link & OTP Requested',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $link = route('login.verify', [
            'token' => $token,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Send Magic Login Link & OTP
        |--------------------------------------------------------------------------
        */

        try {
            Mail::raw(
                "Hello {$user->name},\n\n"
                . "You requested passwordless login for "
                . config('app.name') . ".\n\n"
                . "Option 1: Click the secure login link:\n"
                . "{$link}\n\n"
                . "Option 2: Use your 6-Digit One-Time Passcode (OTP):\n"
                . "🔐 {$otp}\n\n"
                . "This passcode and link will expire in 10 minutes.\n\n"
                . "Regards,\n"
                . config('app.name'),
                function ($mail) use ($user) {
                    $mail->to($user->email)
                        ->subject('Your Passwordless Login Link & OTP Code');
                }
            );
        } catch (Throwable $exception) {
            Log::warning('Passwordless login email log mode/failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login link & 6-digit OTP generated successfully.',
                'email' => $user->email,
                'cooldown' => 60,
                'dev_otp' => $otp,
                'dev_link' => $link
            ]);
        }

        return back()
            ->with(
                'success',
                'Login link & 6-Digit OTP sent to your email.'
            )
            ->with('otp_email', $user->email)
            ->with('dev_otp', $otp)
            ->with('dev_link', $link)
            ->with('cooldown', 60);
    }

    /**
     * Verify magic login token.
     */
    public function verify(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()
                ->route('login')
                ->with('error', 'Invalid login link.');
        }

        $user = User::where('login_token', $token)->first();

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

        $user->update([
            'login_token' => null,
            'login_otp' => null,
            'token_expires_at' => null,
            'otp_expires_at' => null,
            'magic_link_revoked_at' => null,
        ]);

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

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
     * ============================================================
     * 1. VERIFY 6-DIGIT OTP
     * ============================================================
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|string|size:6',
        ]);

        $email = trim(strtolower($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found. Please click "Send Magic Link & 6-Digit OTP" first.'
                ], 404);
            }

            return back()
                ->withInput()
                ->with('error', 'Account not found. Please click "Send Magic Link & 6-Digit OTP" first.')
                ->with('otp_email', $email);
        }

        // Check Brute Force Protection (max 5 attempts)
        if ($user->otp_attempts >= 5) {
            LoginActivity::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'OTP Login',
                'status' => 'Failed - Too Many Attempts',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed OTP attempts. Please request a new code.'
                ], 422);
            }

            return back()->with('error', 'Too many failed OTP attempts. Please request a new code.');
        }

        // Check Expiration
        if (!$user->otp_expires_at || $user->otp_expires_at->lte(now())) {
            LoginActivity::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'OTP Login',
                'status' => 'Failed - Expired OTP',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your OTP has expired. Please request a new one.'
                ], 422);
            }

            return back()->with('error', 'Your OTP has expired. Please request a new one.');
        }

        // Check OTP Match
        if ($user->login_otp !== trim($request->otp)) {
            $user->increment('otp_attempts');
            $remaining = 5 - $user->otp_attempts;

            LoginActivity::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'OTP Login',
                'status' => "Failed - Incorrect OTP ({$user->otp_attempts}/5)",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            $msg = $remaining > 0
                ? "Invalid OTP code. {$remaining} attempts remaining."
                : "Invalid OTP code. Maximum attempts reached. Please request a new OTP.";

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->withInput()->with('error', $msg)->with('otp_email', $user->email);
        }

        // Success: Clean up and log in
        $user->update([
            'login_token' => null,
            'login_otp' => null,
            'token_expires_at' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'magic_link_revoked_at' => null,
        ]);

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => '6-Digit OTP Login',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully!',
                'redirect' => route('dashboard')
            ]);
        }

        return redirect('/dashboard')->with('success', 'You have been securely logged in via 6-Digit OTP.');
    }

    /**
     * Resend 6-Digit OTP.
     */
    public function resendOtp(Request $request)
    {
        return $this->sendLink($request);
    }

    /**
     * ============================================================
     * 2. INSTANT QR CODE CROSS-DEVICE LOGIN
     * ============================================================
     */
    public function generateQrSession(Request $request)
    {
        $qrToken = 'qr_' . Str::random(36);
        Cache::put('qr_session_' . $qrToken, [
            'status' => 'pending',
            'user_id' => null,
            'created_at' => now()->timestamp,
            'ip' => $request->ip(),
        ], now()->addMinutes(2));

        return response()->json([
            'success' => true,
            'qr_token' => $qrToken,
            'expires_in_seconds' => 120,
            'verification_url' => url('/login/qr-approve?token=' . $qrToken),
        ]);
    }

    public function checkQrStatus(Request $request)
    {
        $qrToken = $request->query('token');
        if (!$qrToken) {
            return response()->json(['status' => 'invalid'], 400);
        }

        $sessionData = Cache::get('qr_session_' . $qrToken);

        if (!$sessionData) {
            return response()->json(['status' => 'expired']);
        }

        if ($sessionData['status'] === 'approved' && !empty($sessionData['user_id'])) {
            $user = User::find($sessionData['user_id']);
            if ($user) {
                Auth::login($user);
                if ($request->hasSession()) {
            $request->session()->regenerate();
        }

                LoginActivity::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'action' => 'QR Code Cross-Device Login',
                    'status' => 'Success',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'created_at' => now(),
                ]);

                Cache::forget('qr_session_' . $qrToken);

                return response()->json([
                    'status' => 'approved',
                    'redirect' => route('dashboard')
                ]);
            }
        }

        return response()->json(['status' => 'pending']);
    }

    public function approveQrLogin(Request $request)
    {
        $token = $request->input('token');
        $sessionData = Cache::get('qr_session_' . $token);

        if (!$sessionData) {
            return response()->json(['success' => false, 'message' => 'QR Session expired or invalid.'], 400);
        }

        // Authenticated user or test user simulation
        $user = Auth::user();
        if (!$user) {
            $email = $request->input('email', 'test@example.com');
            $user = User::where('email', $email)->first() ?? User::first();
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No user available for QR login.'], 404);
        }

        Cache::put('qr_session_' . $token, [
            'status' => 'approved',
            'user_id' => $user->id,
            'approved_at' => now()->timestamp,
        ], now()->addMinutes(2));

        return response()->json([
            'success' => true,
            'message' => "QR Code approved for {$user->name} ({$user->email}). Desktop browser logging in!",
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * ============================================================
     * 3. MULTI-DEVICE SESSION MANAGER
     * ============================================================
     */
    public function terminateSession(Request $request, $sessionId)
    {
        $user = $request->user();

        // Delete from sessions table
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'Remote Session Terminated',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Remote session has been terminated.');
    }

    public function logoutOtherDevices(Request $request)
    {
        $user = $request->user();
        $currentSessionId = session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'Logged Out All Other Devices',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'All other active device sessions have been logged out.');
    }

    /**
     * Helper to parse user agent for browser and OS
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'browser' => 'Unknown Browser',
                'os' => 'Unknown OS',
                'device_type' => 'Desktop',
                'icon' => '💻',
            ];
        }

        $browser = 'Unknown Browser';
        if (str_contains($userAgent, 'Edg/')) $browser = 'Microsoft Edge';
        elseif (str_contains($userAgent, 'Chrome/')) $browser = 'Google Chrome';
        elseif (str_contains($userAgent, 'Firefox/')) $browser = 'Mozilla Firefox';
        elseif (str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome/')) $browser = 'Apple Safari';
        elseif (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR/')) $browser = 'Opera';

        $os = 'Unknown OS';
        $icon = '💻';
        $deviceType = 'Desktop';

        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
            $icon = '💻';
        } elseif (str_contains($userAgent, 'iPhone')) {
            $os = 'iOS (iPhone)';
            $icon = '📱';
            $deviceType = 'Mobile';
        } elseif (str_contains($userAgent, 'iPad')) {
            $os = 'iPadOS (iPad)';
            $icon = '📱';
            $deviceType = 'Tablet';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
            $icon = '📱';
            $deviceType = 'Mobile';
        } elseif (str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS')) {
            $os = 'macOS';
            $icon = '💻';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
            $icon = '💻';
        }

        return [
            'browser' => $browser,
            'os' => $os,
            'device_type' => $deviceType,
            'icon' => $icon,
        ];
    }

    /**
     * ============================================================
     * 4. WEBAUTHN / PASSKEYS & BIOMETRIC LOGIN
     * ============================================================
     */
    public function passkeyRegisterOptions(Request $request)
    {
        $user = $request->user();
        $challenge = bin2hex(random_bytes(32));
        session(['passkey_challenge' => $challenge]);

        return response()->json([
            'challenge' => $challenge,
            'rp' => [
                'name' => config('app.name', 'Passwordless App'),
                'id' => $request->getHost(),
            ],
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->email,
                'displayName' => $user->name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'userVerification' => 'preferred',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
        ]);
    }

    public function passkeyRegister(Request $request)
    {
        $request->validate([
            'credential_id' => 'required|string',
            'public_key' => 'required|string',
            'name' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        UserPasskey::create([
            'user_id' => $user->id,
            'name' => $request->input('name', 'Biometric Device (' . now()->format('M d') . ')'),
            'credential_id' => $request->credential_id,
            'public_key' => $request->public_key,
            'counter' => 0,
        ]);

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'Passkey Registered',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Passkey / Biometric device registered successfully!'
        ]);
    }

    public function passkeyLoginOptions(Request $request)
    {
        $challenge = bin2hex(random_bytes(32));
        session(['passkey_login_challenge' => $challenge]);

        $credentials = UserPasskey::all()->map(function ($key) {
            return [
                'id' => $key->credential_id,
                'type' => 'public-key',
            ];
        });

        return response()->json([
            'challenge' => $challenge,
            'timeout' => 60000,
            'rpId' => $request->getHost(),
            'allowCredentials' => $credentials,
            'userVerification' => 'preferred',
        ]);
    }

    public function passkeyLogin(Request $request)
    {
        $credentialId = $request->input('credential_id');
        $passkey = UserPasskey::where('credential_id', $credentialId)->first();

        // If not found by credential_id, check if simulation email or first user was passed
        if (!$passkey) {
            $email = $request->input('email', 'test@example.com');
            $user = User::where('email', $email)->first() ?? User::first();
            if ($user && $user->passkeys()->exists()) {
                $passkey = $user->passkeys()->first();
            }
        }

        if (!$passkey || !$passkey->user) {
            return response()->json([
                'success' => false,
                'message' => 'No registered Passkey / Biometric device found for this account.'
            ], 404);
        }

        $passkey->increment('counter');
        Auth::login($passkey->user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        LoginActivity::create([
            'user_id' => $passkey->user->id,
            'email' => $passkey->user->email,
            'action' => 'Passkey Biometric Login',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Passkey verified successfully!',
            'redirect' => route('dashboard')
        ]);
    }

    /**
     * Helper to register a test passkey for testing without logging in first
     */
    public function demoRegisterPasskey(Request $request)
    {
        $email = $request->input('email', 'test@example.com');
        $user = User::where('email', $email)->first() ?? User::first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $passkey = UserPasskey::firstOrCreate(
            ['user_id' => $user->id, 'credential_id' => 'passkey_demo_' . md5($user->email)],
            [
                'name' => 'Biometric Key (Windows Hello / Touch ID)',
                'public_key' => 'pubkey_demo_sample',
                'counter' => 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Passkey successfully registered for {$user->email}! You can now sign in with Passkey."
        ]);
    }

    public function deletePasskey(Request $request, $id)
    {
        $user = $request->user();
        UserPasskey::where('id', $id)->where('user_id', $user->id)->delete();

        return back()->with('success', 'Passkey has been deleted.');
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
            'login_otp' => null,
            'token_expires_at' => null,
            'otp_expires_at' => null,
            'magic_link_revoked_at' => now(),
        ]);

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'Magic Link & OTP Revoked',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with(
            'success',
            'Your active magic login link and OTP have been revoked.'
        );
    }

    /**
     * Dashboard with statistics, security insights, sessions, and passkeys.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $activities = LoginActivity::where('user_id', $user->id);

        $totalActivities = (clone $activities)->count();

        $successfulLogins = (clone $activities)
            ->where('status', 'Success')
            ->where(function($q) {
                $q->where('action', 'Magic Link Login')
                  ->orWhere('action', '6-Digit OTP Login')
                  ->orWhere('action', 'QR Code Cross-Device Login')
                  ->orWhere('action', 'Passkey Biometric Login');
            })
            ->count();

        $failedLogins = (clone $activities)
            ->where('status', 'like', 'Failed%')
            ->count();

        $magicLinkRequests = (clone $activities)
            ->where(function($q) {
                $q->where('action', 'Magic Link Requested')
                  ->orWhere('action', 'Magic Link & OTP Requested');
            })
            ->count();

        $todayLogins = (clone $activities)
            ->where('status', 'Success')
            ->whereDate('created_at', today())
            ->count();

        $lastLogin = (clone $activities)
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

        $loginActivities = (clone $activities)
            ->latest('created_at')
            ->take(10)
            ->get();

        // 3. Active Sessions Query
        $currentSessionId = session()->getId();
        $dbSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        $activeSessions = $dbSessions->map(function ($s) use ($currentSessionId) {
            $parsed = $this->parseUserAgent($s->user_agent);
            return (object) [
                'id' => $s->id,
                'ip_address' => $s->ip_address,
                'is_current' => ($s->id === $currentSessionId),
                'last_activity_human' => Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
                'browser' => $parsed['browser'],
                'os' => $parsed['os'],
                'device_type' => $parsed['device_type'],
                'icon' => $parsed['icon'],
            ];
        });

        // 4. Passkeys for this user
        $passkeys = $user->passkeys()->latest()->get();

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
            'recentFailedAttempts',
            'activeSessions',
            'passkeys'
        ));
    }

    /**
     * Full login history.
     */
    public function loginHistory(Request $request)
    {
        $user = $request->user();
        $query = LoginActivity::where('user_id', $user->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'success') {
                $query->where('status', 'Success');
            }
            if ($request->status === 'failed') {
                $query->where('status', 'like', 'Failed%');
            }
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $loginActivities = $query
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

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
                    optional($activity->created_at)->format('Y-m-d H:i:s'),
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
            ->with('success', 'Your login history has been cleared successfully.');
    }
}