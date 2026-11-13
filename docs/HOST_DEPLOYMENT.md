# راهنمای اجرای امنیت آپلود روی هاست

این راهنما برای هاست اشتراکی (cPanel) و بدون دسترسی به ترمینال نوشته شده است.

## این تغییرات چه کاری می‌کنند؟

- فایل آپلودی از نظر پسوند، نوع واقعی، ساختار داخلی و کد جاسازی‌شده بررسی می‌شود.
- فایل‌های خطرناک مثل `shell.php`، عکس حاوی کد PHP، PDF دارای اسکریپت و آرشیو حاوی فایل اجرایی رد می‌شوند.
- عکس‌ها هنگام آپلود کوچک و فشرده می‌شوند تا فضای هاست کمتر مصرف شود.
- فایل‌ها در پوشه خصوصی ذخیره می‌شوند و فقط با لینک موقت قابل دانلود هستند.

## قدم ۱: آپلود فایل‌های پروژه

از File Manager این فایل‌ها/پوشه‌ها را روی هاست آپلود کنید:

- `app/Services/Uploads/`
- `app/Exceptions/UploadRejectedException.php`
- `app/Models/UploadScan.php`
- `app/Http/Controllers/FileDownloadController.php`
- کنترلرهای تغییرکرده در `app/Http/Controllers/Admin`، `Teacher` و `Panel`
- `config/uploads.php`
- `database/migrations/2026_11_13_000005_create_upload_scans_table.php`
- `routes/web.php`
- فایل‌های Blade تغییرکرده در `resources/views/`
- `composer.json` و `composer.lock`
- `docs/UPLOAD_SECURITY.md` و همین فایل

## قدم ۲: تنظیم فایل `.env`

در File Manager فایل `.env` را باز کنید و این خطوط را اضافه کنید:

```env
UPLOAD_DISK=local
UPLOAD_IMAGE_OPTIMIZE=true
UPLOAD_IMAGE_MAX_WIDTH=2000
UPLOAD_IMAGE_MAX_HEIGHT=2000
UPLOAD_IMAGE_FORMAT=auto
UPLOAD_PDF_BLOCK_ACTIVE_CONTENT=true

CLAMAV_ENABLED=true
CLAMAV_MODE=auto
CLAMAV_BINARY=
CLAMAV_TIMEOUT=30
UPLOAD_SCAN_REQUIRED=false

YARA_ENABLED=false
VIRUSTOTAL_ENABLED=false
VIRUSTOTAL_API_KEY=
```

مهم: `UPLOAD_SCAN_REQUIRED` باید `false` بماند. اگر `true` باشد و آنتی‌ویروس در دسترس نباشد، همه آپلودها رد می‌شوند.

## قدم ۳: ساخت پوشه‌ها

- اگر پوشه `storage/app/private` وجود ندارد، آن را بسازید.
- دسترسی پوشه‌های `storage` و `bootstrap/cache` باید قابل نوشتن باشد (معمولاً `755` یا `775`).
- اگر فایل `bootstrap/cache/config.php` وجود دارد، آن را حذف کنید تا تنظیمات جدید خوانده شود.

## قدم ۴: ساخت جدول دیتابیس

در cPanel وارد **phpMyAdmin** شوید، دیتابیس سایت را انتخاب کنید، تب **SQL** را باز کنید و این دستور را اجرا کنید:

```sql
CREATE TABLE `upload_scans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sha256` varchar(64) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `extension` varchar(16) NOT NULL,
  `mime` varchar(128) NOT NULL,
  `size` bigint unsigned NOT NULL,
  `context` varchar(64) NOT NULL,
  `user_id` bigint unsigned NULL,
  `verdict` varchar(16) NOT NULL,
  `reasons` json NULL,
  `engines` json NULL,
  `ip` varchar(45) NULL,
  `created_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `upload_scans_sha256_index` (`sha256`),
  KEY `upload_scans_context_index` (`context`),
  KEY `upload_scans_verdict_index` (`verdict`),
  KEY `upload_scans_sha256_verdict_index` (`sha256`, `verdict`),
  KEY `upload_scans_user_id_foreign` (`user_id`),
  CONSTRAINT `upload_scans_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

اگر خطای foreign key گرفتید، فقط خط `CONSTRAINT ... FOREIGN KEY ...` را حذف کنید.

اگر خطای نوع `json` گرفتید، به‌جای `json` از `longtext` استفاده کنید.

بعد از ساخت جدول، این دستور را هم اجرا کنید تا Laravel بداند migration انجام شده است:

```sql
SET @batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
VALUES ('2026_11_13_000005_create_upload_scans_table', @batch);
```

## قدم ۵: اگر ClamAV نصب نمی‌شود چه کنیم؟

ClamAV یک آنتی‌ویروس است که فایل را با امضاهای به‌روز بررسی می‌کند. روی هاست اشتراکی معمولاً قابل نصب نیست. در این حالت سه گزینه دارید:

### گزینه ۱: استفاده از VirusTotal (پیشنهاد اول)

VirusTotal فایل را با ده‌ها موتور آنتی‌ویروس بررسی می‌کند. برنامه فقط **هش SHA-256** فایل را می‌فرستد، نه خود فایل را.

1. در `virustotal.com` یک حساب رایگان بسازید.
2. از بخش API Key کلید را بردارید.
3. در `.env` این‌ها را تنظیم کنید:

```env
CLAMAV_ENABLED=false
VIRUSTOTAL_ENABLED=true
VIRUSTOTAL_API_KEY=کلید-شما
VIRUSTOTAL_TIMEOUT=10
UPLOAD_SCAN_REQUIRED=true
```

4. فایل `bootstrap/cache/config.php` را حذف کنید.

محدودیت نسخه رایگان حدود ۴ درخواست در دقیقه است. برنامه نتیجه را ۲۴ ساعت ذخیره می‌کند تا از محدودیت عبور نکنید.

در این حالت VirusTotal به‌عنوان اسکنر در دسترس حساب می‌شود و آپلودها فقط وقتی رد می‌شوند که VirusTotal هم پاسخ ندهد.

### گزینه ۲: درخواست از پشتیبانی هاست

از پشتیبانی بخواهید ClamAV و `freshclam` را نصب و به‌روزرسانی خودکار کنند. بعد مسیر باینری را در `.env` بگذارید:

```env
CLAMAV_ENABLED=true
CLAMAV_BINARY=/usr/bin/clamscan
UPLOAD_SCAN_REQUIRED=true
```

### گزینه ۳: ادامه بدون آنتی‌ویروس

اگر هیچ‌کدام ممکن نیست، این تنظیمات را بگذارید:

```env
CLAMAV_ENABLED=false
VIRUSTOTAL_ENABLED=false
UPLOAD_SCAN_REQUIRED=false
```

در این حالت این بررسی‌ها همچنان فعال هستند:

- پسوند خطرناک و پسوند دوگانه
- تطابق نوع واقعی فایل با پسوند
- کد PHP/اسکریپت جاسازی‌شده در عکس
- اسکریپت و فایل جاسازی‌شده در PDF
- ماکرو و قالب خارجی در فایل‌های Office
- فایل اجرایی داخل ZIP و حمله zip bomb

مهم‌تر از آن، فایل‌ها در پوشه عمومی وب‌سرور ذخیره نمی‌شوند و همیشه به‌صورت دانلود (attachment) ارائه می‌شوند. بنابراین حتی اگر فایل مخربی از بررسی رد شود، روی سرور اجرا نمی‌شود.

## قدم ۶: تست نهایی

1. یک عکس معمولی آپلود کنید؛ باید ذخیره شود و حجمش کمتر شود.
2. یک فایل با نام `test.php.jpg` آپلود کنید؛ باید رد شود.
3. یک PDF معمولی و یک PDF دارای JavaScript تست کنید.
4. در phpMyAdmin جدول `upload_scans` را باز کنید؛ باید ردیف‌های `clean` و `infected` دیده شوند.

## اگر خطا گرفتید

- **آپلود کار نمی‌کند:** دسترسی نوشتن `storage/app/private` را بررسی کنید.
- **عکس معمولی با خطای «کد جاسازی‌شده» رد می‌شود:** نسخه جدید `app/Services/Uploads/UploadValidator.php` را آپلود کنید. نسخه قدیمی نشانه‌های کوتاه را داخل داده فشرده JPEG هم جست‌وجو می‌کرد و برای عکس‌های واقعی خطای اشتباه می‌داد.
- **تغییرات `.env` اعمال نمی‌شود:** فایل `bootstrap/cache/config.php` را حذف کنید.
- **خطای جدول `upload_scans`:** مطمئن شوید SQL با موفقیت اجرا شده و نام جدول درست است.
- **خطای VirusTotal:** کلید API و دسترسی خروجی HTTPS هاست را بررسی کنید. خطا در VirusTotal باعث توقف آپلود نمی‌شود و فقط در لاگ ثبت می‌شود.
- **لاگ‌ها:** فایل `storage/logs/laravel.log` را ببینید.

## درباره phpunit

phpunit فقط برای تست است و روی سایت اصلی لازم نیست. اگر نصب شد و خطا دیدید:

- نسخه PHP باید 8.3 یا بالاتر باشد.
- `composer validate` باید `valid` بدهد.
- خطای `could not find driver sqlite` مربوط به تست‌های قدیمی است و روی سایت تولید تأثیری ندارد.
- برای نصب بدون ابزارهای تست از `composer install --no-dev` استفاده کنید.
