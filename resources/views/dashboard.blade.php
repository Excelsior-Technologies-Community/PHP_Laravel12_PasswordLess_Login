<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #0f172a;
            color: #e5e7eb;
        }

        .navbar {
            background: #020617;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h2 {
            margin: 0;
            color: #38bdf8;
        }

        .navbar a {
            color: #f87171;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            padding: 40px;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: #020617;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        .card h3 {
            margin-top: 0;
            color: #38bdf8;
        }

        .welcome {
            margin-bottom: 30px;
            font-size: 18px;
        }

        .badge {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            background: #16a34a;
            color: white;
            border-radius: 6px;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <h2>Passwordless App</h2>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button style="background:none;border:none;color:#f87171;cursor:pointer;font-weight:bold;">
                Logout
            </button>
        </form>
    </div>

    <!-- Content -->
    <div class="container">
        <div class="welcome">
            👋 Welcome, <strong>{{ auth()->user()->name }}</strong>  
            <div class="badge">Logged in via Magic Link</div>
        </div>

        <div class="card-grid">
            <div class="card">
                <h3>User Info</h3>
                <p><b>Email:</b> {{ auth()->user()->email }}</p>
                <p><b>User ID:</b> {{ auth()->user()->id }}</p>
            </div>

            <div class="card">
                <h3>Login Type</h3>
                <p>Passwordless Authentication</p>
                <p>Magic Link (Email)</p>
            </div>

            <div class="card">
                <h3>Security</h3>
                <p>✔ One-time token</p>
                <p>✔ Token expiry</p>
                <p>✔ Session based login</p>
            </div>

            <div class="card">
                <h3>Next Steps</h3>
                <p>• OTP Login</p>
                <p>• API Auth</p>
                <p>• User Profile</p>
            </div>
        </div>
    </div>

</body>
</html>
