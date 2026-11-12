<!DOCTYPE html>
<html dir="rtl" lang="fa">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل مدیریت') — myclass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}" rel="stylesheet">
    <link href="{{ asset('css/fonts/fira-code.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/jalalidatepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/prism-tomorrow.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/prism-line-numbers.min.css') }}">
    <style>
        html {
            scrollbar-gutter: stable;
            overflow-y: scroll;
        }

        body {
            background: #f4f6f9;
        }

        .admin-sidebar {
            width: 250px;
            height: 100vh;
            max-height: 100vh;
            background: #212529;
            color: white;
            position: fixed;
            top: 0;
            right: 0;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1050;
            transition: transform 0.25s ease;
            -webkit-overflow-scrolling: touch;

            /* اسکرول‌بار در سمت راست */
            direction: ltr;

            scrollbar-width: thin;
            scrollbar-color: #495057 transparent;
        }

        /* برگرداندن جهت RTL برای محتوا */
        .admin-sidebar>* {
            direction: rtl;
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: #495057;
            border-radius: 3px;
        }

        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: #6c757d;
        }

        ّ .admin-sidebar .brand {
            padding: 18px 20px;
            border-bottom: 1px solid #343a40;
            text-align: center;
            font-weight: bold;
            font-size: 1.15rem;
        }

        .admin-sidebar .brand a {
            color: white;
            text-decoration: none;
        }

        .admin-sidebar .nav-link {
            color: #adb5bd;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        /* ============ منوی آکاردئونی ============ */
        .admin-sidebar .nav-parent {
            color: #adb5bd;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            text-decoration: none;
            cursor: pointer;
            border-top: 1px solid #2c3034;
            transition: background 0.15s;
        }

        .admin-sidebar .nav-parent:hover {
            background: #2c3034;
            color: white;
        }

        .admin-sidebar .nav-parent .chevron {
            font-size: 0.75rem;
            transition: transform 0.25s;
            color: #6c757d;
        }

        .admin-sidebar .nav-parent:not(.collapsed) .chevron {
            transform: rotate(180deg);
            color: #0d6efd;
        }

        .admin-sidebar .nav-parent:not(.collapsed) {
            color: white;
            background: #2c3034;
        }

        .admin-sidebar .sub-link {
            padding: 9px 20px 9px 35px;
            font-size: 0.87rem;
            color: #adb5bd;
            text-decoration: none;
            display: block;
            transition: all 0.15s;
            border-right: 3px solid transparent;
        }

        .admin-sidebar .sub-link:hover {
            background: #2c3034;
            color: white;
            padding-right: 38px;
        }

        .admin-sidebar .sub-link.active {
            background: #0d6efd;
            color: white;
            border-right: 3px solid #0a58ca;
            font-weight: 600;
        }

        .admin-sidebar .nav-link:hover {
            background: #2c3034;
            color: white;
        }

        .admin-sidebar .nav-link.active {
            background: #0d6efd;
            color: white;
        }

        .admin-sidebar .nav-section {
            padding: 14px 20px 4px;
            font-size: 0.72rem;
            color: #6c757d;
            letter-spacing: 0.5px;
        }

        .admin-content {
            margin-right: 250px;
        }

        .admin-topbar {
            background: white;
            padding: 12px 24px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 500;
        }

        .admin-main {
            padding: 24px;
        }

        .admin-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }

        .admin-backdrop.show {
            display: block;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(100%);
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .admin-content {
                margin-right: 0;
            }

            .admin-topbar {
                padding: 10px 16px;
            }

            .admin-main {
                padding: 16px;
            }

            .topbar-title {
                font-size: 0.95rem;
                max-width: 55vw;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }

        @media print {

            .admin-sidebar,
            .admin-topbar,
            .no-print,
            .admin-backdrop {
                display: none !important;
            }

            .admin-content {
                margin-right: 0;
            }

            .admin-main {
                padding: 0;
            }
        }

        /* Prism (code blocks) */
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
                white-space: pre-wrap !important;
                /* اجازه شکستن خط */
                word-break: break-all !important;
                /* شکستن کلمات طولانی */
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
            pre,
            code {
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
                min-width: 0 !important;
                /* جلوگیری از min-width صفحه‌ای */
                border: none !important;
                overflow: visible !important;
                white-space: normal !important;
                /* خنثی کردن قانون nowrap قبلی */
            }

            table.mobile-cards thead {
                display: none;
            }

            table.mobile-cards tbody {
                display: block;
                width: 100%;
            }

            table.mobile-cards tr {
                display: block;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                margin-bottom: 15px;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                background: #fff;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
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
                white-space: normal !important;
                /* اجازه شکستن خط */
                word-break: break-word;
                /* شکستن کلمات طولانی */
                overflow-wrap: anywhere;
            }

            table.mobile-cards td:last-child {
                border-bottom: none !important;
            }

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

            table.mobile-cards td[data-label="عملیات"]::before {
                display: none;
            }

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

    <div class="admin-sidebar no-print" id="adminSidebar">
        <div class="brand">
            <a href="{{ route('admin.dashboard') }}">🎓 myclass</a>
        </div>
        <nav class="nav flex-column pb-4">

            @php
            $activeGroups = [
            'basic' => request()->routeIs('admin.schools.*', 'admin.academic-years.*', 'admin.grades.*', 'admin.subjects.*', 'admin.topics.*', 'admin.questions.*'),
            'people' => request()->routeIs('admin.classes.*', 'admin.students.*', 'admin.teachers.*', 'admin.managers.*'),
            'tasks' => request()->routeIs('admin.exams.*', 'admin.exercises.*'),
            'reports' => request()->routeIs('admin.point-categories.*', 'admin.login-logs.*'),
            ];
            @endphp

            <!-- داشبورد (لینک مستقیم) -->
            <a href="{{ route('admin.dashboard') }}"
                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                🏠 داشبورد
            </a>

            <!-- گروه ۱: اطلاعات پایه -->
            <a href="#group-basic" data-bs-toggle="collapse" role="button"
                class="nav-parent {{ $activeGroups['basic'] ? '' : 'collapsed' }}"
                aria-expanded="{{ $activeGroups['basic'] ? 'true' : 'false' }}">
                <span>📚 اطلاعات پایه</span>
                <span class="chevron">▾</span>
            </a>
            <div class="collapse {{ $activeGroups['basic'] ? 'show' : '' }}" id="group-basic">
                <a href="{{ route('admin.schools.index') }}" class="sub-link {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}">🏫 مدارس</a>
                <a href="{{ route('admin.academic-years.index') }}" class="sub-link {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}">📅 سال‌های تحصیلی</a>
                <a href="{{ route('admin.grades.index') }}" class="sub-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">🎓 پایه‌های تحصیلی</a>
                <a href="{{ route('admin.subjects.index') }}" class="sub-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">📖 دروس</a>
                <a href="{{ route('admin.topics.index') }}" class="sub-link {{ request()->routeIs('admin.topics.*') ? 'active' : '' }}">🏷️ مباحث</a>
                <a href="{{ route('admin.questions.index') }}" class="sub-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">❓ بانک سوالات</a>
            </div>

            <!-- گروه ۲: کلاس و افراد -->
            <a href="#group-people" data-bs-toggle="collapse" role="button"
                class="nav-parent {{ $activeGroups['people'] ? '' : 'collapsed' }}"
                aria-expanded="{{ $activeGroups['people'] ? 'true' : 'false' }}">
                <span>👥 کلاس و افراد</span>
                <span class="chevron">▾</span>
            </a>
            <div class="collapse {{ $activeGroups['people'] ? 'show' : '' }}" id="group-people">
                <a href="{{ route('admin.classes.index') }}" class="sub-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}">🏛️ کلاس‌ها</a>
                <a href="{{ route('admin.students.index') }}" class="sub-link {{ request()->routeIs('admin.students.index', 'admin.students.create', 'admin.students.edit', 'admin.students.show') ? 'active' : '' }}">👨‍🎓 دانش‌آموزان</a>
                <a href="{{ route('admin.students.credentials') }}" class="sub-link {{ request()->routeIs('admin.students.credentials') ? 'active' : '' }}">🔑 برگه اطلاعات ورود</a>
                <a href="{{ route('admin.teachers.index') }}" class="sub-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">👨‍🏫 معلم‌ها</a>
                <a href="{{ route('admin.managers.index') }}" class="sub-link {{ request()->routeIs('admin.managers.*') ? 'active' : '' }}">🏫 مسئولین مدارس</a>
            </div>

            <!-- گروه ۳: آزمون و تمرین -->
            <a href="#group-tasks" data-bs-toggle="collapse" role="button"
                class="nav-parent {{ $activeGroups['tasks'] ? '' : 'collapsed' }}"
                aria-expanded="{{ $activeGroups['tasks'] ? 'true' : 'false' }}">
                <span>📝 آزمون و تمرین</span>
                <span class="chevron">▾</span>
            </a>
            <div class="collapse {{ $activeGroups['tasks'] ? 'show' : '' }}" id="group-tasks">
                <a href="{{ route('admin.exams.index') }}" class="sub-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}">📝 آزمون‌ها</a>
                <a href="{{ route('admin.exercises.index') }}" class="sub-link {{ request()->routeIs('admin.exercises.*') ? 'active' : '' }}">📚 تمرین‌ها</a>
            </div>

            <!-- گروه ۴: گزارش و امتیاز -->
            <a href="#group-reports" data-bs-toggle="collapse" role="button"
                class="nav-parent {{ $activeGroups['reports'] ? '' : 'collapsed' }}"
                aria-expanded="{{ $activeGroups['reports'] ? 'true' : 'false' }}">
                <span>📊 گزارش و امتیاز</span>
                <span class="chevron">▾</span>
            </a>
            <div class="collapse {{ $activeGroups['reports'] ? 'show' : '' }}" id="group-reports">
                <a href="{{ route('admin.point-categories.index') }}" class="sub-link {{ request()->routeIs('admin.point-categories.*') ? 'active' : '' }}">⭐ دسته‌های امتیاز</a>
                <a href="{{ route('admin.login-logs.index') }}" class="sub-link {{ request()->routeIs('admin.login-logs.*') ? 'active' : '' }}">🔐 لاگ ورود کاربران</a>
            </div>

            <!-- حساب کاربری (لینک مستقیم) -->
            <div class="nav-section">حساب کاربری</div>
            <a href="{{ route('admin.profile') }}"
                class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                👤 پروفایل من
            </a>

        </nav>
    </div>

    <div class="admin-backdrop no-print" id="adminBackdrop"></div>

    <div class="admin-content">
        <div class="admin-topbar no-print">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button" aria-label="منو">
                    ☰
                </button>
                <div class="topbar-title"><strong>@yield('title', 'پنل مدیریت')</strong></div>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <a href="{{ route('admin.profile') }}" class="text-muted small text-decoration-none">
                    👤 {{ auth()->user()->name }}
                </a>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger">خروج</button>
                </form>
            </div>
        </div>

        <div class="admin-main">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
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
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/jalalidatepicker.min.js') }}"></script>
    <!-- Prism به صورت محلی از پوشه public بارگذاری می‌شود -->
    <script src="{{ asset('js/prism.min.js') }}"></script>
    <script src="{{ asset('js/prism-markup-templating.min.js') }}"></script>
    <script src="{{ asset('js/prism-php.min.js') }}"></script>
    <script src="{{ asset('js/prism-php-extras.min.js') }}"></script>
    <script src="{{ asset('js/prism-sql.min.js') }}"></script>
    <script src="{{ asset('js/prism-javascript.min.js') }}"></script>
    <script src="{{ asset('js/prism-line-numbers.min.js') }}"></script>
    <script>
        if (typeof jalaliDatepicker !== 'undefined') jalaliDatepicker.startWatch();

        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('adminBackdrop').classList.toggle('show');
        });
        document.getElementById('adminBackdrop')?.addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.remove('open');
            this.classList.remove('show');
        });
    </script>

    <script>
        // تبدیل خودکار همه جدول‌ها به کارت در موبایل
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('table.table').forEach(function(table) {
                if (table.classList.contains('mobile-as-cards')) return;

                table.classList.add('mobile-cards');

                var headRow = table.querySelector('thead tr:last-child');
                var headers = headRow ?
                    Array.prototype.slice.call(headRow.querySelectorAll('th')) :
                    [];

                function headerLabel(th) {
                    var clone = th.cloneNode(true);
                    Array.prototype.forEach.call(
                        clone.querySelectorAll('button, small, br, .btn'),
                        function(el) {
                            el.remove();
                        }
                    );
                    return (clone.textContent || '').replace(/\s+/g, ' ').trim();
                }

                var labels = headers.map(headerLabel);

                table.querySelectorAll('tbody tr').forEach(function(row) {
                    var cells = row.querySelectorAll('td');
                    if (cells.length === 1 && cells[0].hasAttribute('colspan')) return;

                    Array.prototype.forEach.call(cells, function(cell, index) {
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
    @stack('scripts')
</body>

</html>