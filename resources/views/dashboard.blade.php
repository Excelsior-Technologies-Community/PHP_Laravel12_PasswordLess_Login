<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Passwordless Dashboard</title>

    <style>

        * {
            box-sizing: border-box;
        }

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

            box-shadow:
                0 3px 15px rgba(0,0,0,0.3);
        }

        .navbar h2 {
            margin: 0;

            color: #38bdf8;
        }

        .logout-button {
            background: none;

            border: none;

            color: #f87171;

            cursor: pointer;

            font-weight: bold;

            font-size: 14px;
        }

        .container {
            padding: 35px;

            max-width: 1300px;

            margin: auto;
        }

        .alert-success {
            background: #166534;

            color: #dcfce7;

            padding: 13px 18px;

            border-radius: 8px;

            margin-bottom: 20px;
        }

        .alert-error {
            background: #991b1b;

            color: #fee2e2;

            padding: 13px 18px;

            border-radius: 8px;

            margin-bottom: 20px;
        }

        .welcome {
            margin-bottom: 30px;

            font-size: 20px;
        }

        .badge {
            display: inline-block;

            margin-top: 10px;

            padding: 6px 12px;

            background: #166534;

            color: white;

            border-radius: 6px;

            font-size: 12px;
        }

        .card-grid {
            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(250px, 1fr));

            gap: 20px;

            margin-bottom: 30px;
        }

        .card {
            background: #020617;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 10px 25px rgba(0,0,0,0.35);
        }

        .card h3 {
            margin-top: 0;

            color: #38bdf8;
        }

        .card p {
            color: #cbd5e1;

            line-height: 1.6;
        }

        .status-active {
            color: #4ade80;

            font-weight: bold;
        }

        .status-expired {
            color: #f87171;

            font-weight: bold;
        }

        .status-revoked {
            color: #fbbf24;

            font-weight: bold;
        }

        .revoke-button {
            margin-top: 15px;

            padding: 10px 15px;

            background: #dc2626;

            color: white;

            border: none;

            border-radius: 6px;

            cursor: pointer;

            font-weight: bold;
        }

        .revoke-button:hover {
            background: #b91c1c;
        }

        .activity-section {
            background: #020617;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 10px 25px rgba(0,0,0,0.35);
        }

        .activity-section h3 {
            margin-top: 0;

            color: #38bdf8;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;

            border-collapse: collapse;

            min-width: 800px;
        }

        th {
            text-align: left;

            padding: 12px;

            background: #0f172a;

            color: #38bdf8;

            font-size: 13px;
        }

        td {
            padding: 12px;

            border-bottom: 1px solid #1e293b;

            font-size: 13px;

            color: #cbd5e1;
        }

        .success-status {
            color: #4ade80;

            font-weight: bold;
        }

        .failed-status {
            color: #f87171;

            font-weight: bold;
        }

        .empty-message {
            color: #64748b;

            padding: 20px 0;
        }

        .security-list {
            padding-left: 18px;
        }

        .security-list li {
            margin-bottom: 8px;

            color: #cbd5e1;
        }

    </style>

</head>

<body>

    {{-- Navbar --}}

    <div class="navbar">

        <h2>
            Passwordless App
        </h2>

        <form
            method="POST"
            action="{{ route('logout') }}"
        >
            @csrf

            <button
                type="submit"
                class="logout-button"
            >
                Logout
            </button>

        </form>

    </div>


    <div class="container">

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


        {{-- Welcome --}}

        <div class="welcome">

            👋 Welcome,

            <strong>
                {{ auth()->user()->name }}
            </strong>

            <br>

            <span class="badge">
                Logged in via Magic Link
            </span>

        </div>


        {{-- Main Cards --}}

        <div class="card-grid">


            {{-- User Information --}}

            <div class="card">

                <h3>
                    👤 User Info
                </h3>

                <p>
                    <b>Name:</b>
                    {{ auth()->user()->name }}
                </p>

                <p>
                    <b>Email:</b>
                    {{ auth()->user()->email }}
                </p>

                <p>
                    <b>User ID:</b>
                    {{ auth()->user()->id }}
                </p>

            </div>


            {{-- Login Type --}}

            <div class="card">

                <h3>
                    🔐 Login Type
                </h3>

                <p>
                    Passwordless Authentication
                </p>

                <p>
                    Magic Link Authentication
                </p>

                <p>
                    No password required
                </p>

            </div>


            {{-- Security --}}

            <div class="card">

                <h3>
                    🛡️ Security
                </h3>

                <ul class="security-list">

                    <li>
                        One-time login token
                    </li>

                    <li>
                        10-minute token expiry
                    </li>

                    <li>
                        Session-based authentication
                    </li>

                    <li>
                        Login activity tracking
                    </li>

                </ul>

            </div>


            {{-- Active Magic Link --}}

            <div class="card">

                <h3>
                    🔗 Magic Link Status
                </h3>

                @if(
                    auth()->user()->login_token &&
                    auth()->user()->token_expires_at &&
                    auth()->user()->token_expires_at->gt(now()) &&
                    !auth()->user()->magic_link_revoked_at
                )

                    <p class="status-active">
                        ● Active
                    </p>

                    <p>
                        Expires:
                        <strong>
                            {{ auth()->user()->token_expires_at->format('d M Y, h:i A') }}
                        </strong>
                    </p>

                    <form
                        method="POST"
                        action="{{ route('magic-link.revoke') }}"
                        onsubmit="return confirm('Are you sure you want to revoke your active magic login link?')"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="revoke-button"
                        >
                            Revoke Active Link
                        </button>

                    </form>

                @elseif(auth()->user()->magic_link_revoked_at)

                    <p class="status-revoked">
                        ● Revoked
                    </p>

                    <p>
                        Your previous magic link has been revoked.
                    </p>

                @else

                    <p class="status-expired">
                        ● No Active Link
                    </p>

                    <p>
                        Your previous magic link has expired or was already used.
                    </p>

                @endif

            </div>

        </div>


        {{-- Login Activity --}}

        <div class="activity-section">

            <h3>
                🛡️ Recent Login Activity
            </h3>

            @if($loginActivities->count() > 0)

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Action
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    IP Address
                                </th>

                                <th>
                                    Browser / Device
                                </th>

                                <th>
                                    Date & Time
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($loginActivities as $activity)

                                <tr>

                                    <td>
                                        {{ $activity->action }}
                                    </td>

                                    <td>

                                        @if(str_contains(strtolower($activity->status), 'failed'))

                                            <span class="failed-status">
                                                {{ $activity->status }}
                                            </span>

                                        @else

                                            <span class="success-status">
                                                {{ $activity->status }}
                                            </span>

                                        @endif

                                    </td>

                                    <td>
                                        {{ $activity->ip_address ?? 'Unknown' }}
                                    </td>

                                    <td>
                                        {{ \Illuminate\Support\Str::limit($activity->user_agent ?? 'Unknown', 70) }}
                                    </td>

                                    <td>
                                        {{ $activity->created_at->format('d M Y, h:i A') }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="empty-message">
                    No login activity recorded yet.
                </div>

            @endif

        </div>

    </div>

</body>

</html>