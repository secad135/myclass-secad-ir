<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود به سامانه — MyClass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Vazir', Tahoma, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .auth-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
            border-top: 5px solid #0d6efd;
            width: 100%;
            max-width: 420px;
        }
        .brand-link {
            display: block;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
            text-decoration: none;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <a href="/" class="brand-link">🎓 MyClass</a>
        <h4 class="text-center mb-4 fw-bold">ورود به حساب کاربری</h4>

        <!-- نمایش پیام وضعیت (بعد از تغییر رمز) -->
        @if (session('status'))
            <div class="alert alert-success small">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="mb-3">
                <label class="form-label">ایمیل (نام کاربری)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus dir="ltr">
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">رمز عبور</label>
                <input type="password" name="password" class="form-control" required dir="ltr">
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label small">مرا به خاطر بسپار</label>
                </div>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="small text-decoration-none">فراموشی رمز؟</a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">ورود</button>
        </form>

        <div class="text-center mt-4">
            <a href="/" class="small text-muted text-decoration-none">← بازگشت به صفحه اصلی</a>
        </div>
    </div>

</body>
</html>