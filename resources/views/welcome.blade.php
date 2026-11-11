<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>سامانه مدیریت کلاس — MyClass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Vazir', Tahoma, Arial, sans-serif;
            min-height: 100vh;
        }
        .navbar-custom {
            background: #0d6efd;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-custom .brand {
            color: white;
            font-size: 1.4rem;
            font-weight: bold;
            text-decoration: none;
        }
        .hero-section {
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
            border-top: 5px solid #0d6efd;
        }
        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border-right: 4px solid #0d6efd;
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-3px);
        }
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .feature-title {
            font-size: 1.05rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #212529;
        }
        .feature-desc {
            font-size: 0.85rem;
            color: #6c757d;
            line-height: 1.6;
            margin: 0;
        }
    </style>
</head>
<body>

    <!-- هدر سایت -->
    <nav class="navbar-custom">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="/" class="brand">🎓 MyClass</a>
            <a href="{{ route('help') }}" class="btn btn-outline-light btn-sm">راهنمای ورود</a>
        </div>
    </nav>

    <!-- بخش اصلی -->
    <div class="container hero-section">
        <div class="row g-4 align-items-center">
            
            <!-- سمت راست: فرم ورود -->
            <div class="col-lg-5">
                <div class="login-card">
                    <h3 class="text-center mb-4 fw-bold">ورود به سامانه</h3>
                    
                    <!-- نمایش خطاها -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <!-- نمایش پیام موفقیت (مثلاً بعد از تغییر رمز) -->
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">ایمیل (نام کاربری)</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus dir="ltr">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رمز عبور</label>
                            <input type="password" name="password" class="form-control" required dir="ltr">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="remember" id="remember" class="form-check-input">
                                <label for="remember" class="form-check-label small">مرا به خاطر بسپار</label>
                            </div>
                            <a href="{{ route('password.request') }}" class="small text-decoration-none">فراموشی رمز؟</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">ورود</button>
                    </form>
                    
                    <div class="text-center mt-3 small text-muted">
                        اکانت ندارید؟ رمز و ایمیل توسط معلم به شما داده می‌شود.
                    </div>
                </div>
            </div>

            <!-- سمت چپ: توضیحات امکانات -->
            <div class="col-lg-7">
                <h2 class="fw-bold mb-3">سامانه جامع مدیریت آموزشی</h2>
                <p class="text-muted mb-4" style="line-height: 1.8;">
                    این سامانه به منظور تسهیل فرآیندهای آموزشی و ارتباط مؤثر بین معلم و دانش‌آموزان طراحی شده است. امکانات این سیستم شامل موارد زیر می‌باشد:
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">📅</div>
                            <div class="feature-title">ثبت حضور و غیاب</div>
                            <div class="feature-desc">ثبت دقیق حضور، غیبت و تاخیر در جلسات کلاس به همراه یادداشت‌های اختصاصی برای هر روز.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">⭐</div>
                            <div class="feature-title">امتیازدهی و رفتار</div>
                            <div class="feature-desc">ثبت امتیازات مثبت و منفی برای فعالیت‌های کلاسی، تکالیف و رفتارهای انضباطی.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">📝</div>
                            <div class="feature-title">آزمون‌های آنلاین</div>
                            <div class="feature-desc">برگزاری آزمون‌های چهارگزینه‌ای با تایمر، تصادفی‌سازی سوالات و نمایش فوری نتایج.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">📚</div>
                            <div class="feature-title">تمرین و تکلیف</div>
                            <div class="feature-desc">ارسال تمرین‌های کدنویسی و متنی توسط دانش‌آموزان و تصحیح آنلاین توسط معلم.</div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="feature-card" style="border-right-color: #198754;">
                            <div class="d-flex align-items-center">
                                <div class="feature-icon ms-3 mb-0">📊</div>
                                <div>
                                    <div class="feature-title">گزارش‌گیری جامع</div>
                                    <div class="feature-desc">مشاهده کارنامه فردی، رتبه‌بندی کلاس، درصد حضور و تحلیل عملکرد تحصیلی و رفتاری هر دانش‌آموز.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>