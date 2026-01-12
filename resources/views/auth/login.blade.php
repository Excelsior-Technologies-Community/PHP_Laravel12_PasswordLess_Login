<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Passwordless Login</title>

    <style>
        body {
            margin: 0;
            height: 100vh;
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
            width: 350px;
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
            text-align: center;
        }

        .login-box h2 {
            margin-bottom: 10px;
            color: #38bdf8;
        }

        .login-box p {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 25px;
        }

        .login-box input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: none;
            outline: none;
            font-size: 14px;
        }

        .login-box button {
            width: 100%;
            padding: 12px;
            background: #38bdf8;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .login-box button:hover {
            background: #0ea5e9;
        }

        .alert-success {
            background: #16a34a;
            color: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .alert-error {
            background: #dc2626;
            color: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
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
    <h2>🔐 Passwordless Login</h2>
    <p>No password required. We’ll email you a secure login link.</p>

    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.send') }}">
        @csrf
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Login Link</button>
    </form>

    <div class="footer-text">
        Secure • One-time link • Expires in 10 minutes
    </div>
</div>

</body>
</html>
