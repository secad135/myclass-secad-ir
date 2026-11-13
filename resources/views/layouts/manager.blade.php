<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل ناظر') — myclass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .navbar-manager { background: #198754; padding: 15px 0; }
        .navbar-manager .container { flex-wrap: nowrap; gap: 16px; }
        .navbar-manager a { color: white; text-decoration: none; font-weight: bold; white-space: nowrap; }
        .navbar-manager .manager-brand { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
        .navbar-manager .manager-user-area { flex: 0 0 auto; flex-wrap: nowrap; }
        .navbar-manager .manager-user-name { display: inline-block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
        .manager-content { padding: 24px; max-width: 1200px; margin: 0 auto; }
        @media (max-width: 767.98px) {
            .navbar-manager .manager-user-name { max-width: 120px; }
            .navbar-manager .container > .manager-brand { max-width: 55vw; }
        }
    </style>
    @yield('head')
</head>
<body>
    <nav class="navbar-manager">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('manager.dashboard') }}" class="manager-brand">🎓 پنل ناظر مدرسه ({{ auth()->user()->school->name }})</a>
            <div class="manager-user-area d-flex gap-3 align-items-center">
                <a href="{{ route('manager.profile') }}" class="manager-user-name small" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</a>
                <form action="{{ route('logout') }}" method="POST">@csrf <button class="btn btn-sm btn-outline-light">خروج</button></form>
            </div>
        </div>
    </nav>
    <div class="manager-content">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @yield('content')
    </div>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
