<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>بازیابی رمز عبور</title>
</head>
<body style="background-color: #f4f6f9; font-family: 'Vazir', Tahoma, Arial, sans-serif; padding: 20px; text-align: right; direction: rtl; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; border-top: 5px solid #0d6efd; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        
        <div style="text-align: center; margin-bottom: 20px;">
            <!-- لوگوی شما -->
            <!-- <img src="{{ asset('docs/logo.png') }}" alt="MyClass" style="max-height: 80px; border-radius: 10px;"> -->
            <h2 style="color: #212529; margin-top: 15px;">بازیابی رمز عبور</h2>
        </div>

        <p style="font-size: 16px; color: #495057; line-height: 1.8;">سلام {{ $user->name }} عزیز،</p>
        
        <p style="font-size: 16px; color: #495057; line-height: 1.8;">
            شما این ایمیل را دریافت کردید زیرا درخواست بازیابی رمز عبور برای حساب کاربری شما در سامانه <strong>MyClass</strong> ثبت شده است.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #0d6efd; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; font-weight: bold; display: inline-block;">
                تغییر رمز عبور
            </a>
        </div>

        <p style="font-size: 14px; color: #6c757d; line-height: 1.8;">
            این لینک بازیابی تا <strong>۶۰ دقیقه</strong> دیگر منقضی می‌شود.
        </p>

        <p style="font-size: 14px; color: #6c757d; line-height: 1.8;">
            اگر شما این درخواست را نداده‌اید، نیازی به انجام کاری نیست و می‌توانید این ایمیل را نادیده بگیرید. حساب شما امن است.
        </p>

        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #adb5bd; text-align: center; margin-bottom: 0;">
            با تشکر، تیم پشتیبانی MyClass<br>
            <a href="https://myclass.secad.ir" style="color: #adb5bd;">myclass.secad.ir</a>
        </p>
    </div>
</body>
</html>