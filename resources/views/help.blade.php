<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>راهنمای ورود به سامانه — myclass</title>
    <!-- استفاده از فایل بوت‌استرپ لوکال که قبلاً دانلود کردیم -->
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}?v={{ filemtime(public_path('css/vazir.css')) }}" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Vazir', Tahoma, Arial, sans-serif;
            padding-bottom: 50px;
        }
        .help-container {
            max-width: 750px;
            margin: 40px auto;
            padding: 0 15px;
        }
        .help-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 30px;
            border-top: 5px solid #0d6efd;
        }
        .step-number {
            display: inline-block;
            width: 32px;
            height: 32px;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: bold;
            margin-left: 10px;
        }
        .step-box {
            background: #f8f9fa;
            border-right: 4px solid #0d6efd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .step-box h5 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #212529;
        }
        .step-box p {
            margin-bottom: 0;
            line-height: 1.8;
            color: #495057;
            font-size: 0.95rem;
        }
        .faq-box {
            background: #fff3cd;
            border-right: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <div class="help-container">
        
        <div class="text-center mb-4">
            <h2 class="fw-bold">🎓 راهنمای ورود به سامانه کلاس</h2>
            <p class="text-muted">ویژه دانش‌آموزان عزیز</p>
        </div>

        <div class="help-card">
            
            <div class="step-box">
                <h5><span class="step-number">۱</span> دریافت اطلاعات ورود</h5>
                <p>
                    معلم شما اطلاعات ورود (شامل <strong>ایمیل</strong> و <strong>رمز اولیه</strong>) را از طریق پیام خصوصی در شبکه شاد برای شما ارسال کرده است. 
                    <br>
                    ایمیل شما معمولاً به شکل <code>s001@myclass.ir</code> یا مشابه آن است.
                </p>
            </div>

            <div class="step-box">
                <h5><span class="step-number">۲</span> وارد شدن به سایت</h5>
                <p>
                    روی دکمه «ورود» در صفحه اول کلیک کنید. در صفحه‌ای که باز می‌شود، ایمیل و رمز اولیه‌ای که معلم به شما داده است را وارد کنید.
                </p>
            </div>

            <div class="step-box">
                <h5><span class="step-number">۳</span> تغییر رمز عبور (مهم)</h5>
                <p>
                    در همان اولین ورود، سیستم از شما می‌خواهد که یک <strong>رمز جدید</strong> برای خودتان انتخاب کنید. 
                    لطفاً رمز جدید را طوری انتخاب کنید که هم خودتان راحت به یاد بیاورید و هم امن باشد (حداقل ۸ کاراکتر، شامل حروف و اعداد).
                </p>
            </div>

            <div class="step-box">
                <h5><span class="step-number">۴</span> استفاده از امکانات</h5>
                <p>
                    بعد از تغییر رمز، به پنل شخصی خودتان هدایت می‌شوید. از آنجا می‌توانید حضور و غیاب خود را ببینید، در آزمون‌ها شرکت کنید و به تمرین‌ها پاسخ دهید.
                </p>
            </div>

            <div class="faq-box">
                <h5 class="fw-bold">❓ رمز عبورم را فراموش کردم، چه کنم؟</h5>
                <p class="mb-0 mt-2 small" style="line-height: 1.8;">
                    اگر رمز خود را فراموش کردید، به هیچ عنوان حساب جدید نسازید. 
                    حتماً در شبکه شاد به معلم خود پیام دهید تا از طریق پنل مدیریت، رمز شما را بازنشانی کند و رمز جدید را به شما تحویل دهد.
                </p>
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-5">رفتن به صفحه ورود</a>
            </div>

        </div>
    </div>

</body>
</html>
