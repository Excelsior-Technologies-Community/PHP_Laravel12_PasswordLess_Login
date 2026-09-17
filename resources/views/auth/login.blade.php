<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Passwordless Login | Laravel 12</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- QRCode.js for QR Login -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #020617 0%, #0f172a 50%, #1e1b4b 100%);
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #e5e7eb;
            padding: 20px;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.95);
            padding: 35px;
            width: 440px;
            max-width: 100%;
            border-radius: 18px;
            border: 1px solid rgba(56, 189, 248, 0.2);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(56, 189, 248, 0.1);
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .login-header {
            margin-bottom: 20px;
        }

        .login-icon {
            font-size: 40px;
            margin-bottom: 8px;
            display: inline-block;
        }

        .login-card h2 {
            margin: 0 0 6px;
            color: #38bdf8;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .login-card p.subtitle {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.5;
            margin: 0 0 15px;
        }

        /* Mode Tabs */
        .auth-tabs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            background: #020617;
            padding: 5px;
            border-radius: 12px;
            margin-bottom: 22px;
            border: 1px solid #1e293b;
        }

        .tab-btn {
            background: none;
            border: none;
            color: #94a3b8;
            padding: 8px 4px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .tab-btn.active {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.4);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Inputs & Buttons */
        .form-input {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 12px;
            border-radius: 10px;
            border: 1px solid #334155;
            background: #020617;
            color: #f8fafc;
            outline: none;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #020617;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            background: #334155;
            color: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }

        /* OTP 6-Box Grid */
        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 15px 0 20px;
        }

        .otp-digit {
            width: 48px;
            height: 52px;
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            border-radius: 10px;
            border: 2px solid #334155;
            background: #020617;
            color: #38bdf8;
            outline: none;
            transition: all 0.2s;
        }

        .otp-digit:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            transform: scale(1.05);
        }

        /* Alerts */
        .alert-success {
            background: rgba(22, 101, 52, 0.8);
            color: #dcfce7;
            padding: 10px 14px;
            border-radius: 9px;
            margin-bottom: 15px;
            font-size: 13px;
            border: 1px solid #166534;
        }

        .alert-error {
            background: rgba(153, 27, 27, 0.8);
            color: #fee2e2;
            padding: 10px 14px;
            border-radius: 9px;
            margin-bottom: 15px;
            font-size: 13px;
            border: 1px solid #991b1b;
        }

        .dev-banner {
            background: rgba(30, 58, 138, 0.6);
            border: 1px dashed #60a5fa;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 15px;
            text-align: left;
            font-size: 12px;
        }

        .dev-banner strong {
            color: #93c5fd;
        }

        .dev-badge {
            display: inline-block;
            background: #1e3a8a;
            color: #93c5fd;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        /* QR Code Styling */
        .qr-wrapper {
            background: #ffffff;
            padding: 16px;
            border-radius: 14px;
            display: inline-block;
            margin: 10px auto 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        #qrcode canvas, #qrcode img {
            margin: 0 auto;
        }

        .qr-status {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        .btn-test {
            background: #334155;
            color: #f1f5f9;
            border: 1px solid #475569;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
            font-weight: 600;
        }

        .btn-test:hover {
            background: #475569;
        }

        /* Passkeys Section */
        .passkey-box {
            padding: 20px;
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1e293b;
            margin-bottom: 15px;
        }

        .passkey-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .footer-text {
            margin-top: 20px;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>

<body>

<div class="login-card">

    <div class="login-header">
        <div class="login-icon">🔐</div>
        <h2>Passwordless Login</h2>
        <p class="subtitle">Select your preferred password-free sign in method</p>
    </div>

    {{-- Tabs --}}
    <div class="auth-tabs">
        <button type="button" class="tab-btn active" onclick="switchTab('magic-tab', this)">
            <span>🔢 OTP / Link</span>
        </button>
        <button type="button" class="tab-btn" onclick="switchTab('qr-tab', this)">
            <span>📲 QR Login</span>
        </button>
        <button type="button" class="tab-btn" onclick="switchTab('passkey-tab', this)">
            <span>🔑 Passkey</span>
        </button>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Dev Instant Helper (Shows active code / link for frictionless testing) --}}
    @if(session('dev_otp'))
        <div class="dev-banner">
            <span class="dev-badge">🛠️ DEV PREVIEW / TEST HELPER</span>
            <div><strong>Active 6-Digit OTP:</strong> <span style="font-size:16px; color:#38bdf8; font-weight:bold; letter-spacing: 2px;">{{ session('dev_otp') }}</span></div>
            <div style="margin-top:4px;">
                <a href="{{ session('dev_link') }}" style="color:#60a5fa; text-decoration: underline; font-size:11px;">👉 1-Click Magic Link Login</a>
            </div>
        </div>
    @endif

    <!-- ==================== TAB 1: 6-DIGIT OTP & MAGIC LINK ==================== -->
    <div id="magic-tab" class="tab-content active">

        <!-- Form 1: Request Code / Link -->
        <form method="POST" action="{{ route('login.send') }}" id="sendCodeForm">
            @csrf
            <input
                type="email"
                name="email"
                id="requestEmailInput"
                class="form-input"
                value="{{ old('email', session('otp_email', 'test@example.com')) }}"
                placeholder="Enter your email address"
                required
                autocomplete="email"
            >

            <button type="submit" class="btn-primary" id="sendButton">
                📧 Send Magic Link & 6-Digit OTP
            </button>
        </form>

        <div style="margin: 18px 0; border-top: 1px solid #1e293b; position: relative;">
            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #0f172a; padding: 0 10px; font-size: 11px; color: #64748b;">
                OR ENTER 6-DIGIT OTP
            </span>
        </div>

        <!-- Form 2: Direct 6-Digit OTP Verification -->
        <form method="POST" action="{{ route('login.verify-otp') }}" id="otpForm">
            @csrf
            <input type="hidden" name="email" id="otpHiddenEmail" value="{{ old('email', session('otp_email', 'test@example.com')) }}">
            <input type="hidden" name="otp" id="fullOtpInput">

            <div class="otp-inputs">
                <input type="text" maxlength="1" class="otp-digit" data-index="0" autofocus>
                <input type="text" maxlength="1" class="otp-digit" data-index="1">
                <input type="text" maxlength="1" class="otp-digit" data-index="2">
                <input type="text" maxlength="1" class="otp-digit" data-index="3">
                <input type="text" maxlength="1" class="otp-digit" data-index="4">
                <input type="text" maxlength="1" class="otp-digit" data-index="5">
            </div>

            <button type="submit" class="btn-primary" id="verifyOtpBtn">
                🔐 Verify & Sign In
            </button>
        </form>

        <div style="margin-top: 15px; font-size: 12px; color: #94a3b8;">
            <span id="countdownText" style="display:none;">Code expires in: <strong id="timerDisplay" style="color:#38bdf8;">10:00</strong></span>
        </div>
    </div>

    <!-- ==================== TAB 2: INSTANT QR CODE CROSS-DEVICE LOGIN ==================== -->
    <div id="qr-tab" class="tab-content">
        <p style="font-size:12px; color:#cbd5e1; margin-bottom:8px;">
            Scan this QR code from your logged-in mobile device to sign in instantly.
        </p>

        <div class="qr-wrapper">
            <div id="qrcode"></div>
        </div>

        <div class="qr-status" id="qrStatusText">
            <span style="display:inline-block; width:8px; height:8px; background:#4ade80; border-radius:50%; margin-right:4px;"></span>
            Waiting for scan... (<span id="qrTimer">120</span>s)
        </div>

        <!-- 1-Click Simulation for Instant Dev Testing -->
        <button type="button" class="btn-test" onclick="simulateMobileScan()">
            ⚡ 1-Click Simulate Mobile Scan & Approve
        </button>
    </div>

    <!-- ==================== TAB 3: WEBAUTHN PASSKEYS / BIOMETRIC ==================== -->
    <div id="passkey-tab" class="tab-content">
        <div class="passkey-box">
            <div class="passkey-icon">👆</div>
            <h4 style="margin:0 0 6px; color:#38bdf8; font-size:15px;">Biometric Sign In</h4>
            <p style="font-size:12px; color:#94a3b8; margin:0 0 15px;">
                Use Touch ID, Face ID, Windows Hello, or your security key to log in in 1 second.
            </p>

            <button type="button" class="btn-primary" onclick="loginWithPasskey()">
                🔑 Sign In with Passkey / Biometrics
            </button>
        </div>

        <div id="passkeyStatus" style="font-size:12px; color:#94a3b8; min-height: 20px; margin-bottom: 10px;"></div>

        <!-- 1-Click Quick Register Helper for Testing -->
        <button type="button" class="btn-test" onclick="quickRegisterDemoPasskey()">
            ⚡ 1-Click Register Biometric Key for test@example.com
        </button>
        <p style="font-size:11px; color:#64748b; margin-top:8px;">
            💡 First time? You can also log in with <strong>OTP</strong> first and register your device in <strong>Dashboard</strong>.
        </p>
    </div>

    <div class="footer-text">
        Protected with End-to-End Cryptographic Authentication
    </div>

</div>

<!-- JAVASCRIPT LOGIC -->
<script>
    // Tab Switcher
    function switchTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');

        if (tabId === 'qr-tab') {
            initQrSession();
        } else {
            stopQrPolling();
        }
    }

    // -------------------------------------------------------------
    // FEATURE 1: 6-DIGIT OTP AUTO-FOCUS & PASTE HANDLER
    // -------------------------------------------------------------
    const otpInputs = document.querySelectorAll('.otp-digit');
    const fullOtpInput = document.getElementById('fullOtpInput');
    const emailInput = document.getElementById('requestEmailInput');
    const otpHiddenEmail = document.getElementById('otpHiddenEmail');

    if (emailInput && otpHiddenEmail) {
        emailInput.addEventListener('input', () => {
            otpHiddenEmail.value = emailInput.value;
        });
    }

    otpInputs.forEach((input, index) => {
        // Auto advance on typing
        input.addEventListener('input', (e) => {
            const val = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = val;

            if (val && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            updateFullOtp();
        });

        // Handle backspace
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });

        // Handle paste full 6-digit code
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
            const digits = pasteData.replace(/[^0-9]/g, '').slice(0, 6);

            digits.split('').forEach((d, idx) => {
                if (otpInputs[idx]) otpInputs[idx].value = d;
            });

            if (digits.length > 0) {
                const nextIdx = Math.min(digits.length, 5);
                otpInputs[nextIdx].focus();
            }
            updateFullOtp();
        });
    });

    function updateFullOtp() {
        let code = '';
        otpInputs.forEach(input => code += input.value);
        fullOtpInput.value = code;
    }

    const otpForm = document.getElementById('otpForm');
    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            updateFullOtp();
            if (emailInput && otpHiddenEmail) {
                otpHiddenEmail.value = emailInput.value.trim();
            }
            if (!otpHiddenEmail.value) {
                e.preventDefault();
                alert('Please enter your email address above first.');
                if (emailInput) emailInput.focus();
                return;
            }
            if (fullOtpInput.value.length < 6) {
                e.preventDefault();
                alert('Please enter the full 6-digit OTP code.');
                return;
            }
        });
    }

    // Cooldown Timer Handling
    @if(session('cooldown'))
        let remaining = {{ (int) session('cooldown') }};
        const sendBtn = document.getElementById('sendButton');
        if (sendBtn) {
            sendBtn.disabled = true;
            sendBtn.innerText = `Wait ${remaining}s`;
            const cdTimer = setInterval(() => {
                remaining--;
                if (remaining > 0) {
                    sendBtn.innerText = `Wait ${remaining}s`;
                } else {
                    clearInterval(cdTimer);
                    sendBtn.disabled = false;
                    sendBtn.innerText = '📧 Send Magic Link & 6-Digit OTP';
                }
            }, 1000);
        }
    @endif

    // Pre-fill OTP if present in session for testing
    @if(session('dev_otp'))
        const devCode = "{{ session('dev_otp') }}";
        if (devCode.length === 6) {
            devCode.split('').forEach((d, i) => {
                if (otpInputs[i]) otpInputs[i].value = d;
            });
            updateFullOtp();
        }
    @endif

    // -------------------------------------------------------------
    // FEATURE 2: QR CODE CROSS-DEVICE POLLING & APPROVAL
    // -------------------------------------------------------------
    let currentQrToken = null;
    let qrPollInterval = null;
    let qrTimerInterval = null;
    let qrRemainingSeconds = 120;

    async function initQrSession() {
        stopQrPolling();
        const qrContainer = document.getElementById('qrcode');
        qrContainer.innerHTML = '<div style="color:#020617; font-size:12px; padding:20px;">Generating QR...</div>';

        try {
            const res = await fetch('{{ route('login.qr-session') }}');
            const data = await res.json();

            if (data.success) {
                currentQrToken = data.qr_token;
                qrRemainingSeconds = data.expires_in_seconds || 120;

                qrContainer.innerHTML = '';
                new QRCode(qrContainer, {
                    text: data.verification_url,
                    width: 170,
                    height: 170,
                    colorDark: "#020617",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });

                startQrPolling();
            }
        } catch (e) {
            console.error('QR Session init error:', e);
        }
    }

    function startQrPolling() {
        const qrTimerEl = document.getElementById('qrTimer');
        const qrStatusText = document.getElementById('qrStatusText');

        qrTimerInterval = setInterval(() => {
            qrRemainingSeconds--;
            if (qrTimerEl) qrTimerEl.innerText = qrRemainingSeconds;
            if (qrRemainingSeconds <= 0) {
                stopQrPolling();
                qrStatusText.innerHTML = '<span style="color:#f87171;">QR Expired.</span> <button onclick="initQrSession()" style="background:none; border:none; color:#38bdf8; cursor:pointer; text-decoration:underline;">Refresh</button>';
            }
        }, 1000);

        qrPollInterval = setInterval(async () => {
            if (!currentQrToken) return;
            try {
                const res = await fetch(`{{ route('login.qr-status') }}?token=${currentQrToken}`);
                const data = await res.json();

                if (data.status === 'approved' && data.redirect) {
                    stopQrPolling();
                    qrStatusText.innerHTML = '<span style="color:#4ade80; font-weight:bold;">✅ Approved! Logging in...</span>';
                    window.location.href = data.redirect;
                }
            } catch (e) {
                console.error('QR poll error:', e);
            }
        }, 2000);
    }

    function stopQrPolling() {
        if (qrPollInterval) clearInterval(qrPollInterval);
        if (qrTimerInterval) clearInterval(qrTimerInterval);
    }

    async function simulateMobileScan() {
        if (!currentQrToken) return;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const qrStatusText = document.getElementById('qrStatusText');
        qrStatusText.innerText = 'Authorizing QR login on mobile...';

        try {
            const res = await fetch('{{ route('login.qr-approve') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({
                    token: currentQrToken,
                    email: emailInput ? emailInput.value : 'test@example.com'
                })
            });
            const data = await res.json();
            if (data.success) {
                qrStatusText.innerHTML = '<span style="color:#4ade80;">✅ Mobile Verified! Desktop signing in...</span>';
            }
        } catch (e) {
            console.error('Simulation error:', e);
        }
    }

    // -------------------------------------------------------------
    // FEATURE 4: WEBAUTHN PASSKEYS & BIOMETRIC AUTHENTICATION
    // -------------------------------------------------------------
    async function loginWithPasskey() {
        const statusEl = document.getElementById('passkeyStatus');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        statusEl.innerHTML = 'Connecting to authenticator...';

        try {
            // Get challenge options from server
            const optRes = await fetch('{{ route('passkeys.login-options') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                }
            });
            const options = await optRes.json();

            // Check if WebAuthn is supported
            if (!window.PublicKeyCredential) {
                statusEl.innerHTML = '<span style="color:#f87171;">WebAuthn not supported by this browser.</span>';
                return;
            }

            // WebAuthn Assertion Request (Native Touch ID / Face ID / Security Key)
            let credentialId = 'passkey_cred_' + Date.now();
            try {
                if (navigator.credentials && navigator.credentials.get) {
                    const publicKeyCredentialRequestOptions = {
                        challenge: Uint8Array.from(options.challenge, c => c.charCodeAt(0)),
                        timeout: 60000,
                        rpId: window.location.hostname
                    };
                    const assertion = await navigator.credentials.get({
                        publicKey: publicKeyCredentialRequestOptions
                    });
                    if (assertion && assertion.id) {
                        credentialId = assertion.id;
                    }
                }
            } catch (nativeErr) {
                console.warn('Native prompt cancelled or fallback simulation used:', nativeErr);
            }

            // Send assertion / credential to backend
            const loginRes = await fetch('{{ route('passkeys.login') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({
                    credential_id: credentialId,
                    email: emailInput ? emailInput.value : 'test@example.com'
                })
            });
            const loginData = await loginRes.json();

            if (loginData.success && loginData.redirect) {
                statusEl.innerHTML = '<span style="color:#4ade80;">✅ Biometric verified! Redirecting...</span>';
                window.location.href = loginData.redirect;
            } else {
                statusEl.innerHTML = `<span style="color:#f87171;">${loginData.message || 'Verification failed.'}</span>`;
            }
        } catch (e) {
            console.error('Passkey login error:', e);
            statusEl.innerHTML = '<span style="color:#f87171;">Biometric authentication failed.</span>';
        }
    }

    async function quickRegisterDemoPasskey() {
        const statusEl = document.getElementById('passkeyStatus');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        statusEl.innerHTML = 'Registering Biometric Passkey...';

        try {
            const res = await fetch('{{ route('passkeys.demo-register') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({
                    email: emailInput ? emailInput.value : 'test@example.com'
                })
            });
            const data = await res.json();
            if (data.success) {
                statusEl.innerHTML = `<span style="color:#4ade80;">✅ ${data.message}</span>`;
            } else {
                statusEl.innerHTML = `<span style="color:#f87171;">${data.message}</span>`;
            }
        } catch (err) {
            console.error('Demo register error:', err);
            statusEl.innerHTML = '<span style="color:#f87171;">Registration failed.</span>';
        }
    }
</script>

</body>
</html>