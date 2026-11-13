<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عدم دسترسی (403) — MyClass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}?v={{ filemtime(public_path('css/vazir.css')) }}" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Vazir', Tahoma, Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px; }
        .error-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 40px 30px; text-align: center; max-width: 500px; width: 100%; border-top: 5px solid #dc3545; }
        .error-code { font-size: 5rem; font-weight: bold; color: #dc3545; line-height: 1; margin-bottom: 20px; }
        .error-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 15px; color: #212529; }
        .error-desc { color: #6c757d; margin-bottom: 30px; line-height: 1.7; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">403</div>
        <div class="error-title">شما اجازه دسترسی به این بخش را ندارید!</div>
        <div class="error-desc">
            این صفحه مخصوص کاربران خاصی است و شما مجوز ورود به آن را ندارید.
        </div>
        <a href="{{ url('/') }}" class="btn btn-danger px-4 py-2">بازگشت به صفحه اصلی</a>
        <button onclick="history.back()" class="btn btn-outline-secondary px-4 py-2 ms-2">بازگشت به عقب</button>
    </div>
</body>
</html>