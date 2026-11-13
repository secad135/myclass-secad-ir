<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>صفحه پیدا نشد (404) — MyClass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}?v={{ filemtime(public_path('css/vazir.css')) }}" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Vazir', Tahoma, Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px; }
        .error-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 40px 30px; text-align: center; max-width: 500px; width: 100%; border-top: 5px solid #0d6efd; }
        .error-code { font-size: 5rem; font-weight: bold; color: #0d6efd; line-height: 1; margin-bottom: 20px; }
        .error-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 15px; color: #212529; }
        .error-desc { color: #6c757d; margin-bottom: 30px; line-height: 1.7; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">404</div>
        <div class="error-title">صفحه‌ای که دنبال آن بودید پیدا نشد!</div>
        <div class="error-desc">
            ممکن است آدرس را اشتباه وارد کرده باشید، یا این صفحه حذف شده باشد.
        </div>
        <a href="{{ url('/') }}" class="btn btn-primary px-4 py-2">بازگشت به صفحه اصلی</a>
        <button onclick="history.back()" class="btn btn-outline-secondary px-4 py-2 ms-2">بازگشت به عقب</button>
    </div>
</body>
</html>