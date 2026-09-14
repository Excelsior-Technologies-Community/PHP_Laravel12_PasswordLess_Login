<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Passwordless Login</title>

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

            background: linear-gradient(135deg, #020617, #0f172a);

            font-family: Arial, Helvetica, sans-serif;

            color: #e5e7eb;
        }

        .login-box {
            background: #020617;

            padding: 40px;

            width: 400px;
            max-width: 92%;

            border-radius: 14px;

            box-shadow:
                0 15px 40px rgba(0, 0, 0, 0.5);

            text-align: center;
        }

        .login-icon {
            font-size: 45px;

            margin-bottom: 5px;
        }

        .login-box h2 {
            margin: 0 0 10px;

            color: #38bdf8;
        }

        .login-box p {
            font-size: 14px;

            color: #94a3b8;

            line-height: 1.6;

            margin-bottom: 25px;
        }

        .login-box input {
            width: 100%;

            padding: 13px;

            margin-bottom: 15px;

            border-radius: 7px;

            border: 1px solid #334155;

            outline: none;

            font-size: 14px;
        }

        .login-box input:focus {
            border-color: #38bdf8;
        }

        .login-box button {
            width: 100%;

            padding: 13px;

            background: #38bdf8;

            color: #020617;

            border: none;

            border-radius: 7px;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.2s;
        }

        .login-box button:hover {
            background: #0ea5e9;
        }

        .login-box button:disabled {
            background: #475569;

            color: #cbd5e1;

            cursor: not-allowed;
        }

        .alert-success {
            background: #166534;

            color: #dcfce7;

            padding: 12px;

            border-radius: 7px;

            margin-bottom: 15px;

            font-size: 14px;
        }

        .alert-error {
            background: #991b1b;

            color: #fee2e2;

            padding: 12px;

            border-radius: 7px;

            margin-bottom: 15px;

            font-size: 14px;
        }

        .cooldown-box {
            margin-top: 15px;

            padding: 10px;

            background: #172554;

            color: #bfdbfe;

            border-radius: 7px;

            font-size: 13px;
        }

        .security-info {
            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 8px;

            margin-top: 25px;
        }

        .security-item {
            background: #0f172a;

            border-radius: 7px;

            padding: 10px 5px;

            font-size: 11px;

            color: #94a3b8;
        }

        .security-item strong {
            display: block;

            color: #38bdf8;

            margin-bottom: 4px;
        }

        .footer-text {
            margin-top: 20px;

            font-size: 12px;

            color: #64748b;
        }
    </style>
</head>

<body>

    <div class="login-box">

        <div class="login-icon">
            🔐
        </div>

        <h2>Passwordless Login</h2>

        <p>
            No password required.<br>
            We'll send a secure one-time login link to your email.
        </p>

        {{-- Success Message --}}
        @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
        <div class="alert-error">
            {{ session('error') }}
        </div>
        @endif

        {{-- Validation Errors --}}
        @if($errors->any())
        <div class="alert-error">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST"
            action="{{ route('login.send') }}"
            id="loginForm">

            @csrf

            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Enter your email"
                required
                autocomplete="email">

            <button
                type="submit"
                id="sendButton">
                📧 Send Login Link
            </button>

        </form>

        {{-- Cooldown --}}
        @if(session('cooldown'))

        <div class="cooldown-box" id="cooldownBox">

            Please wait
            <strong>
                <span id="countdown">
                    {{ session('cooldown') }}
                </span>
            </strong>
            seconds before requesting another link.

        </div>

        @endif

        <div class="security-info">

            <div class="security-item">
                <strong>🔗</strong>
                Magic Link
            </div>

            <div class="security-item">
                <strong>⏱️</strong>
                10 Minutes
            </div>

            <div class="security-item">
                <strong>🛡️</strong>
                One Time
            </div>

        </div>

        <div class="footer-text">
            Secure passwordless authentication
        </div>

    </div>

    <script>
        const countdownElement = document.getElementById('countdown');

        const sendButton = document.getElementById('sendButton');

        const loginForm = document.getElementById('loginForm');

        @if(session('cooldown'))

        let remaining = {
            {
                session('cooldown')
            }
        };

        if (countdownElement) {
            sendButton.disabled = true;

            sendButton.innerText =
                `Wait ${remaining}s`;

            const timer = setInterval(() => {

                remaining--;

                if (remaining > 0) {

                    countdownElement.innerText = remaining;

                    sendButton.innerText =
                        `Wait ${remaining}s`;

                } else {

                    clearInterval(timer);

                    sendButton.disabled = false;

                    sendButton.innerText =
                        '📧 Send Login Link';

                    const cooldownBox =
                        document.getElementById('cooldownBox');

                    if (cooldownBox) {
                        cooldownBox.style.display = 'none';
                    }
                }

            }, 1000);
        }

        @endif
    </script>

</body>

</html>