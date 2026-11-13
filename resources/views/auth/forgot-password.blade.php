<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فراموشی رمز عبور — MyClass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}?v={{ filemtime(public_path('css/vazir.css')) }}" rel="stylesheet">
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
        <h4 class="text-center mb-3 fw-bold">فراموشی رمز عبور</h4>
        
        <p class="text-muted small text-center mb-4">
            ایمیل خود را وارد کنید تا لینک بازیابی برای شما ارسال شود.
        </p>

        <!-- پیام وضعیت (مثلاً بعد از ارسال ایمیل) -->
        @if (session('status'))
            <div class="alert alert-success small">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            
            <div class="mb-3">
                <label class="form-label">ایمیل</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus dir="ltr">
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">ارسال لینک بازیابی</button>
        </form>

        <!-- هشدار برای دانش‌آموزان با ایمیل سیستماتیک -->
        <div class="mt-4 p-3 bg-warning bg-opacity-10 border-right border-warning" style="border-right: 4px solid #ffc107 !important; border-radius: 6px;">
            <strong class="text-dark small d-block mb-1">توجه دانش‌آموزان:</strong> 
            <span class="text-muted small" style="line-height: 1.6; display: block;">
                اگر ایمیل شما به شکل <code>s001@myclass.ir</code> است، امکان دریافت ایمیل وجود ندارد. لطفاً در شبکه شاد به معلم خود پیام دهید.
            </span>
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="small text-muted text-decoration-none">← بازگشت به صفحه ورود</a>
        </div>
    </div>

</body>
</html>