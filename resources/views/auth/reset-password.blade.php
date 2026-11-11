<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعیین رمز عبور جدید — MyClass</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: Tahoma, Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px; }
        .auth-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 30px; border-top: 5px solid #0d6efd; width: 100%; max-width: 420px; }
        .brand-link { display: block; text-align: center; font-size: 1.5rem; font-weight: bold; color: #0d6efd; text-decoration: none; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <a href="/" class="brand-link">🎓 MyClass</a>
        <h4 class="text-center mb-4 fw-bold">تعیین رمز عبور جدید</h4>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-3">
                <label class="form-label">ایمیل</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $request->email) }}" required readonly dir="ltr">
                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">رمز عبور جدید</label>
                <input type="password" name="password" class="form-control" required dir="ltr">
                @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">تکرار رمز عبور جدید</label>
                <input type="password" name="password_confirmation" class="form-control" required dir="ltr">
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">ثبت رمز جدید</button>
        </form>
    </div>
</body>
</html>