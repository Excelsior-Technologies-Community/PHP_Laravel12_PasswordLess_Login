<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login History</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

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

        .nav-links {
            display: flex;

            gap: 20px;

            align-items: center;
        }

        .nav-links a {
            color: #38bdf8;

            text-decoration: none;

            font-weight: bold;

            font-size: 14px;
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
            max-width: 1400px;

            margin: auto;

            padding: 35px;
        }

        .header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

            gap: 15px;
        }

        .header h1 {
            margin: 0;

            color: #38bdf8;
        }

        .button {
            display: inline-block;

            padding: 10px 15px;

            border-radius: 6px;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;

            border: none;

            cursor: pointer;
        }

        .button-blue {
            background: #0284c7;

            color: white;
        }

        .button-red {
            background: #991b1b;

            color: white;
        }

        .filter-box {
            background: #020617;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 25px;

            box-shadow:
                0 10px 25px rgba(0,0,0,0.35);
        }

        .filter-grid {
            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(180px, 1fr));

            gap: 15px;
        }

        label {
            display: block;

            margin-bottom: 6px;

            color: #94a3b8;

            font-size: 12px;

            font-weight: bold;
        }

        input,
        select {
            width: 100%;

            padding: 11px;

            background: #0f172a;

            color: #e5e7eb;

            border: 1px solid #334155;

            border-radius: 6px;

            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #38bdf8;
        }

        .filter-actions {
            margin-top: 15px;

            display: flex;

            gap: 10px;

            flex-wrap: wrap;
        }

        .table-section {
            background: #020617;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 10px 25px rgba(0,0,0,0.35);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;

            border-collapse: collapse;

            min-width: 1000px;
        }

        th {
            text-align: left;

            padding: 13px;

            background: #0f172a;

            color: #38bdf8;

            font-size: 13px;
        }

        td {
            padding: 13px;

            border-bottom:
                1px solid #1e293b;

            font-size: 13px;

            color: #cbd5e1;
        }

        .success {
            color: #4ade80;

            font-weight: bold;
        }

        .failed {
            color: #f87171;

            font-weight: bold;
        }

        .empty {
            padding: 30px;

            text-align: center;

            color: #64748b;
        }

        .pagination {
            display: flex;

            justify-content: center;

            gap: 7px;

            margin-top: 25px;

            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            min-width: 36px;

            height: 36px;

            display: flex;

            justify-content: center;

            align-items: center;

            border-radius: 6px;

            text-decoration: none;

            background: #0f172a;

            border: 1px solid #334155;

            color: #cbd5e1;

            font-size: 13px;
        }

        .pagination .active {
            background: #0284c7;

            color: white;

            border-color: #0284c7;
        }

        .alert-success {
            background: #166534;

            color: #dcfce7;

            padding: 13px 18px;

            border-radius: 8px;

            margin-bottom: 20px;
        }

        .result-count {
            color: #94a3b8;

            font-size: 13px;

            margin-bottom: 15px;
        }

        @media(max-width: 700px) {

            .navbar {
                flex-direction: column;

                gap: 15px;

                padding: 15px;
            }

            .container {
                padding: 20px;
            }

            .header {
                flex-direction: column;

                align-items: flex-start;
            }

        }

    </style>

</head>

<body>

<div class="navbar">

    <h2>
        Passwordless App
    </h2>

    <div class="nav-links">

        <a href="{{ route('dashboard') }}">
            Dashboard
        </a>

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

</div>


<div class="container">

    @if(session('success'))

        <div class="alert-success">
            {{ session('success') }}
        </div>

    @endif


    <div class="header">

        <h1>
            📋 Full Login History
        </h1>

        <div>

            <a
                href="{{ route('login.history.export') }}"
                class="button button-blue"
            >
                📥 Export CSV
            </a>

        </div>

    </div>


    {{-- Search and Filters --}}

    <div class="filter-box">

        <form
            method="GET"
            action="{{ route('login.history') }}"
        >

            <div class="filter-grid">

                <div>

                    <label>
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Email / IP / action / browser"
                    >

                </div>


                <div>

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="success"
                            @selected(request('status') === 'success')
                        >
                            Success
                        </option>

                        <option
                            value="failed"
                            @selected(request('status') === 'failed')
                        >
                            Failed
                        </option>

                    </select>

                </div>


                <div>

                    <label>
                        Action
                    </label>

                    <select name="action">

                        <option value="">
                            All Actions
                        </option>

                        @foreach($actions as $action)

                            <option
                                value="{{ $action }}"
                                @selected(request('action') === $action)
                            >
                                {{ $action }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label>
                        From Date
                    </label>

                    <input
                        type="date"
                        name="from_date"
                        value="{{ request('from_date') }}"
                    >

                </div>


                <div>

                    <label>
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="{{ request('to_date') }}"
                    >

                </div>

            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="button button-blue"
                >
                    🔎 Apply Filters
                </button>

                <a
                    href="{{ route('login.history') }}"
                    class="button button-blue"
                >
                    🔄 Reset
                </a>

            </div>

        </form>

    </div>


    {{-- Results --}}

    <div class="table-section">

        <div class="result-count">

            Showing
            <strong>
                {{ $loginActivities->firstItem() ?? 0 }}
            </strong>

            to

            <strong>
                {{ $loginActivities->lastItem() ?? 0 }}
            </strong>

            of

            <strong>
                {{ $loginActivities->total() }}
            </strong>

            activities

        </div>


        @if($loginActivities->count() > 0)

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Email
                            </th>

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
                                    {{ $activity->id }}
                                </td>

                                <td>
                                    {{ $activity->email ?? 'Unknown' }}
                                </td>

                                <td>
                                    {{ $activity->action }}
                                </td>

                                <td>

                                    @if(str_contains(
                                        strtolower($activity->status),
                                        'failed'
                                    ))

                                        <span class="failed">
                                            {{ $activity->status }}
                                        </span>

                                    @else

                                        <span class="success">
                                            {{ $activity->status }}
                                        </span>

                                    @endif

                                </td>

                                <td>
                                    {{ $activity->ip_address ?? 'Unknown' }}
                                </td>

                                <td>
                                    {{ \Illuminate\Support\Str::limit(
                                        $activity->user_agent ?? 'Unknown',
                                        80
                                    ) }}
                                </td>

                                <td>
                                    {{ $activity->created_at?->format(
                                        'd M Y, h:i A'
                                    ) }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Numeric-only Pagination --}}

            @if($loginActivities->lastPage() > 1)

                <div class="pagination">

                    @for(
                        $page = 1;
                        $page <= $loginActivities->lastPage();
                        $page++
                    )

                        @if($page == $loginActivities->currentPage())

                            <span class="active">
                                {{ $page }}
                            </span>

                        @else

                            <a
                                href="{{ $loginActivities->url($page) }}"
                            >
                                {{ $page }}
                            </a>

                        @endif

                    @endfor

                </div>

            @endif

        @else

            <div class="empty">
                No login activity found for the selected filters.
            </div>

        @endif

    </div>


    {{-- Clear History --}}

    <div
        class="table-section"
        style="margin-top:25px;"
    >

        <h3 style="color:#f87171;">
            🗑️ Delete Login History
        </h3>

        <p style="color:#94a3b8;">
            This permanently deletes your login activity history.
        </p>

        <form
            method="POST"
            action="{{ route('login.history.clear') }}"
            onsubmit="return confirm('Are you sure you want to permanently delete your login history?')"
        >

            @csrf

            @method('DELETE')

            <button
                type="submit"
                class="button button-red"
            >
                🗑️ Clear My History
            </button>

        </form>

    </div>

</div>

</body>

</html>