<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل من') — myclass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}" rel="stylesheet">
    <link href="{{ asset('css/fonts/fira-code.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/jalalidatepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/prism-tomorrow.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/prism-line-numbers.min.css') }}">
    <style>
        html { scrollbar-gutter: stable; overflow-y: scroll; }
        body { background: #f4f6f9; }

        .panel-navbar {
            background: linear-gradient(135deg, #0d6efd 0%, #084298 100%);
            padding: 0;
            min-height: 58px;
            box-shadow: 0 2px 12px rgba(13, 110, 253, 0.28);
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .panel-navbar .navbar-brand {
            font-size: 1.05rem;
            letter-spacing: 0.2px;
        }
        .panel-user-area {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-user-name {
            color: #fff;
            font-size: 0.85rem;
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            opacity: 0.95;
        }
        .panel-logout-btn {
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.08);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            transition: all 0.15s ease;
        }
        .panel-logout-btn:hover {
            background: #fff;
            color: #0d6efd;
            border-color: #fff;
        }
        .panel-navbar .navbar-toggler {
            padding: 4px 8px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
        }
        .panel-navbar .navbar-toggler:focus {
            box-shadow: none;
        }
        .panel-navbar .nav-link {
            color: white !important;
            padding: 14px 18px;
            display: block;
            font-size: 0.95rem;
            border-radius: 0;
        }
        .panel-navbar .nav-link:hover { background: rgba(255,255,255,0.15); }
        .panel-navbar .nav-link.active { background: rgba(255,255,255,0.25); font-weight: bold; }

        .panel-content {
            padding: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        @media (max-width: 991.98px) {
            .panel-navbar .navbar-collapse {
                background: #0a58ca;
                margin-top: 0;
                border-top: 1px solid rgba(255,255,255,0.2);
                flex-basis: 100%;
            }
            .panel-navbar .nav-link {
                padding: 12px 20px;
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }
            .panel-user-name {
                max-width: 110px;
                font-size: 0.8rem;
            }
            .panel-content { padding: 16px; }
        }

        @media (max-width: 420px) {
            .panel-navbar .navbar-brand {
                font-size: 0.95rem;
            }
            .panel-user-area {
                gap: 6px;
            }
            .panel-user-name {
                max-width: 80px;
                font-size: 0.75rem;
            }
            .panel-logout-btn {
                padding: 3px 9px;
                font-size: 0.75rem;
            }
        }

        @media print {
            .no-print { display: none !important; }
        }

        /* Prism */
        .code-block-wrapper {
            background: #2d2d2d;
            border-radius: 6px;
            overflow: hidden;
            direction: ltr;
        }
        .code-block-label {
            background: #1e1e1e;
            color: #9cdcfe;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-family: monospace;
            border-bottom: 1px solid #3c3c3c;
            direction: ltr;
            text-align: left;
        }
        .code-block-wrapper pre[class*="language-"] {
            margin: 0;
            border-radius: 0;
            font-size: 0.85rem;
            max-height: 500px;
            overflow-y: auto;
        }
        .code-block-wrapper pre[class*="language-"] code {
            font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
            font-size: 0.88rem;
            font-weight: 400;
            font-variant-ligatures: none;
            line-height: 1.55;
        }

        /* ============ اصلاح نمایش کدها در موبایل ============ */
        @media (max-width: 991.98px) {
            .code-block-wrapper {
                max-width: 100% !important;
                overflow-x: hidden !important;
            }
            .code-block-wrapper pre {
                max-width: 100% !important;
                white-space: pre-wrap !important; /* اجازه شکستن خط */
                word-break: break-all !important; /* شکستن کلمات طولانی */
                overflow-wrap: break-word !important;
                font-size: 0.75rem !important;
                padding: 10px !important;
            }
            .code-block-wrapper pre code {
                white-space: pre-wrap !important;
                word-break: break-all !important;
                overflow-wrap: break-word !important;
            }
            /* مخفی کردن شماره خطوط Prism در موبایل برای صرفه‌جویی در فضا */
            pre.line-numbers {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
            pre.line-numbers .line-numbers-rows {
                display: none !important;
            }
            /* اصلاح هر کد دیگری که در صفحه است */
            pre, code {
                white-space: pre-wrap !important;
                word-break: break-all !important;
            }
        }

        /* ============ جدول‌های موبایل — اسکرول سراسری ============ */
        @media (max-width: 991.98px) {
            .table-responsive,
            .table-responsive-sm,
            .table-responsive-md,
            .table-responsive-lg {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            /* هر جدولی که داخل wrapper اسکرول‌دار مخصوص خودش نیست */
            table.table:not(.table-responsive table):not(.attendance-table):not(.mobile-as-cards) {
                display: block;
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap;
            }
        }

        /* ============ تبدیل جدول به کارت در موبایل ============ */
        @media screen and (max-width: 991.98px) {
            table.mobile-cards {
                display: block;
                width: 100%;
                max-width: 100%;
                min-width: 0 !important; /* جلوگیری از min-width صفحه‌ای */
                border: none !important;
                overflow: visible !important;
                white-space: normal !important; /* خنثی کردن قانون nowrap قبلی */
            }
            table.mobile-cards thead { display: none; }
            table.mobile-cards tbody { display: block; width: 100%; }
            
            table.mobile-cards tr {
                display: block;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                margin-bottom: 15px;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                background: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.04);
                padding: 5px 0;
            }
            
            table.mobile-cards td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none !important;
                border-bottom: 1px dashed #e9ecef !important;
                padding: 10px 15px !important;
                text-align: left;
                flex-wrap: wrap;
                gap: 10px;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                box-sizing: border-box;
                white-space: normal !important; /* اجازه شکستن خط */
                word-break: break-word;         /* شکستن کلمات طولانی */
                overflow-wrap: anywhere;
            }
            
            table.mobile-cards td:last-child { border-bottom: none !important; }
            
            table.mobile-cards td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #495057;
                font-size: 0.85rem;
                flex-shrink: 0;
                text-align: right;
            }
            
            table.mobile-cards td[data-label="عملیات"] {
                justify-content: flex-start;
                background: #f8f9fa;
                border-radius: 0 0 8px 8px;
                padding-top: 15px !important;
            }
            table.mobile-cards td[data-label="عملیات"]::before { display: none; }
            table.mobile-cards td[data-label="عملیات"] .btn,
            table.mobile-cards td[data-label="عملیات"] form {
                flex-grow: 1;
                margin: 2px 0;
            }
        }
    </style>
    @yield('head')
</head>
<body>

    @if(!request()->routeIs('panel.exams.show'))
    <nav class="panel-navbar navbar navbar-expand-lg no-print">
        <div class="container-fluid px-3">
            <a href="{{ route('panel.dashboard') }}" class="navbar-brand text-white fw-bold m-0 order-1">
                🎓 پنل من
            </a>

            <div class="panel-user-area order-2 order-lg-3">
                <span class="panel-user-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                    @csrf
                    <button class="btn panel-logout-btn">خروج</button>
                </form>
                <button class="navbar-toggler border-0 text-white d-lg-none" type="button"
                        data-bs-toggle="collapse" data-bs-target="#panelNavContent" aria-label="منو">
                    <span style="font-size: 1.4rem;">☰</span>
                </button>
            </div>

            <div class="collapse navbar-collapse order-3 order-lg-2" id="panelNavContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="{{ route('panel.dashboard') }}" class="nav-link {{ request()->routeIs('panel.dashboard') ? 'active' : '' }}">
                            🏠 داشبورد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('panel.attendance') }}" class="nav-link {{ request()->routeIs('panel.attendance') ? 'active' : '' }}">
                            📅 حضور و غیاب من
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('panel.points') }}" class="nav-link {{ request()->routeIs('panel.points') ? 'active' : '' }}">
                            ⭐ امتیازات من
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('panel.exams.index') }}" class="nav-link {{ request()->routeIs('panel.exams.*') ? 'active' : '' }}">
                            📝 آزمون‌ها
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('panel.exercises.index') }}" class="nav-link {{ request()->routeIs('panel.exercises.*') ? 'active' : '' }}">
                            📚 تمرین‌ها
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('panel.profile.edit') }}" class="nav-link {{ request()->routeIs('panel.profile.*') ? 'active' : '' }}">
                            👤 پروفایل
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @else
    <!-- نوار باریک مخصوص آزمون -->
    <div class="bg-dark text-white p-2 text-center no-print">
        <strong>⚠️ شما در حالت آزمون هستید. لطفاً صفحه را ترک نکنید.</strong>
    </div>
    @endif

    <div class="panel-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/jalalidatepicker.min.js') }}"></script>
    <script src="{{ asset('js/prism.min.js') }}"></script>
    <script src="{{ asset('js/prism-markup-templating.min.js') }}"></script>
    <script src="{{ asset('js/prism-php.min.js') }}"></script>
    <script src="{{ asset('js/prism-php-extras.min.js') }}"></script>
    <script src="{{ asset('js/prism-sql.min.js') }}"></script>
    <script src="{{ asset('js/prism-javascript.min.js') }}"></script>
    <script src="{{ asset('js/prism-line-numbers.min.js') }}"></script>

    @yield('scripts')
    @stack('scripts')

    <script>
        if (typeof jalaliDatepicker !== 'undefined') jalaliDatepicker.startWatch();
    </script>

    <script>
        // تبدیل خودکار همه جدول‌ها به کارت در موبایل
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('table.table').forEach(function (table) {
                if (table.classList.contains('mobile-as-cards')) return;

                table.classList.add('mobile-cards');

                var headRow = table.querySelector('thead tr:last-child');
                var headers = headRow
                    ? Array.prototype.slice.call(headRow.querySelectorAll('th'))
                    : [];

                function headerLabel(th) {
                    var clone = th.cloneNode(true);
                    Array.prototype.forEach.call(
                        clone.querySelectorAll('button, small, br, .btn'),
                        function (el) { el.remove(); }
                    );
                    return (clone.textContent || '').replace(/\s+/g, ' ').trim();
                }

                var labels = headers.map(headerLabel);

                table.querySelectorAll('tbody tr').forEach(function (row) {
                    var cells = row.querySelectorAll('td');
                    if (cells.length === 1 && cells[0].hasAttribute('colspan')) return;

                    Array.prototype.forEach.call(cells, function (cell, index) {
                        if (cell.hasAttribute('data-label')) return;

                        var label = labels[index] || '';
                        if (!label && cell.querySelector('a, button, form, input, select')) {
                            label = 'عملیات';
                        }
                        if (label) cell.setAttribute('data-label', label);
                    });
                });
            });
        });
    </script>
</body>
</html>
