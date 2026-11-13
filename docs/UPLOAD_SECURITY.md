# امنیت آپلود فایل

> برای اجرا روی هاست اشتراکی و بدون ترمینال، فایل `docs/HOST_DEPLOYMENT.md` را ببینید.

هر فایل آپلودشده قبل از ذخیره از این مراحل عبور می‌کند:

1. **کنترل پسوند و MIME واقعی** — پسوند باید در allow-list همان بخش باشد و MIME تشخیص‌داده‌شده با `fileinfo` با پسوند هم‌خوانی داشته باشد.
2. **بررسی امضای بایت‌ها (magic bytes)** — ساختار داخلی فایل باید با پسوند مطابقت داشته باشد.
3. **تشخیص پلی‌گلات و کد جاسازی‌شده** — جست‌وجوی `<?php`، `<script>`، `<iframe>`، ELF/PE و مشابه آن داخل تصاویر.
4. **تحلیل PDF و RTF** — رد کردن جاوااسکریپت، `OpenAction`، فایل جاسازی‌شده و آبجکت OLE.
5. **بازرسی آرشیوها** — جلوگیری از zip bomb، zip-slip، فایل اجرایی داخل آرشیو و ماکرو/قالب خارجی در اسناد Office.
6. **اسکن بدافزار چندموتوره** — VirusTotal (فقط هش SHA-256)، ClamAV و YARA.
7. **بازنویسی تصاویر با GD** — حذف EXIF/داده‌های الحاقی، کوچک‌سازی ابعاد و فشرده‌سازی برای صرفه‌جویی فضای هاست.
8. **ذخیره در دیسک خصوصی + دانلود امضاشده** — فایل‌ها هرگز inline سرو نمی‌شوند و با هدرهای `nosniff` و `CSP: sandbox` به‌صورت attachment دانلود می‌شوند.

## راه‌اندازی اسکنرها

### ClamAV (توصیه‌شده)

اگر امکان نصب ClamAV روی هاست وجود ندارد، به‌جای آن VirusTotal را فعال کنید (بخش پایین). بررسی‌های داخلی برنامه در هر حالت فعال هستند.

```bash
sudo apt install clamav clamav-daemon
sudo systemctl enable --now clamav-daemon
```

به‌روزرسانی امضاها باید خودکار باشد؛ برای ClamAV دیمن معمولاً `clamav-freshclam` نصب می‌شود. برای حالت standalone:

```bash
sudo freshclam
# cron: 0 */6 * * * /usr/bin/freshclam --quiet
```

سپس در `.env`:

```env
CLAMAV_ENABLED=true
CLAMAV_MODE=auto
CLAMAV_BINARY=
UPLOAD_SCAN_REQUIRED=false
```

اگر `UPLOAD_SCAN_REQUIRED=true` باشد و هیچ اسکنری در دسترس نباشد، آپلود رد می‌شود. این گزینه را فقط وقتی فعال کنید که ClamAV روی هاست نصب و پایدار است.

### YARA (اختیاری)

```bash
sudo apt install yara
```

```env
YARA_ENABLED=true
YARA_BINARY=/usr/bin/yara
YARA_RULES_PATH=/etc/yara/rules
```

### VirusTotal (اختیاری)

فقط هش SHA-256 فایل ارسال می‌شود، نه خود فایل. کلید API را در `.env` قرار دهید:

```env
VIRUSTOTAL_ENABLED=true
VIRUSTOTAL_API_KEY=...
```

## بهینه‌سازی تصاویر

```env
UPLOAD_IMAGE_OPTIMIZE=true
UPLOAD_IMAGE_MAX_WIDTH=2000
UPLOAD_IMAGE_MAX_HEIGHT=2000
UPLOAD_IMAGE_FORMAT=auto   # یا webp
```

تصاویر متحرک GIF بدون تغییر نگه داشته می‌شوند تا انیمیشن از بین نرود.

## اجرا

```bash
php artisan migrate
```

جدول `upload_scans` سابقه اسکن، هش فایل و لیست سیاه فایل‌های مخرب را نگه می‌دارد. فایلی که یک‌بار مخرب تشخیص داده شود، در آپلودهای بعدی فوراً رد می‌شود.

## تست

```bash
php artisan test --filter=UploadSecurityTest
```
