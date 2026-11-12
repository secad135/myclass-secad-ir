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
        .navbar-manager a { color: white; text-decoration: none; font-weight: bold; }
        .manager-content { padding: 24px; max-width: 1200px; margin: 0 auto; }
    </style>
    @yield('head')
</head>
<body>
    <nav class="navbar-manager">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('manager.dashboard') }}">🎓 پنل ناظر مدرسه ({{ auth()->user()->school->name }})</a>
            <div class="d-flex gap-3 align-items-center">
                <a href="{{ route('manager.profile') }}" class="small">👤 پروفایل من</a>
                <span class="text-white small">{{ auth()->user()->name }}</span>
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
