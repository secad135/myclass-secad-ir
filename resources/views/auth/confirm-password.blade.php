<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأیید رمز عبور — MyClass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Vazir', Tahoma, Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px; }
        .auth-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 30px; border-top: 5px solid #0d6efd; width: 100%; max-width: 420px; }
        .brand-link { display: block; text-align: center; font-size: 1.5rem; font-weight: bold; color: #0d6efd; text-decoration: none; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <a href="/" class="brand-link">🎓 MyClass</a>
        <h4 class="text-center mb-3 fw-bold">تأیید هویت</h4>
        <p class="text-muted small text-center mb-4">
            این بخش امن است. لطفاً برای ادامه، رمز عبور خود را وارد کنید.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label">رمز عبور</label>
                <input type="password" name="password" class="form-control" required autofocus autocomplete="current-password" dir="ltr">
                @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">تأیید و ادامه</button>
        </form>
    </div>
</body>
</html>