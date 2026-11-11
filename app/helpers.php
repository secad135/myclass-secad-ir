<?php

if (!function_exists('normalize_digits')) {
    /**
     * تبدیل ارقام فارسی/عربی به لاتین
     */
    function normalize_digits(string $s): string
    {
        return str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $s
        );
    }
}

if (!function_exists('jdate')) {
    /**
     * تبدیل تاریخ میلادی به شمسی (نمایش)
     *
     * @param mixed  $date    Carbon|DateTime|string|null
     * @param string $pattern الگوی ICU: 'yyyy/MM/dd' یا 'yyyy/MM/dd HH:mm'
     */
    function jdate($date, string $pattern = 'yyyy/MM/dd'): string
    {
        if (!$date) {
            return '';
        }

        try {
            // همیشه با منطقه‌زمانی سرور کار می‌کنیم تا نمایش به ساعت کاربر وابسته نباشد
            $timezone = config('app.timezone', date_default_timezone_get());

            if (!$date instanceof DateTimeInterface) {
                $date = new DateTime((string) $date, new DateTimeZone($timezone));
            } else {
                $date = (clone $date)->setTimezone(new DateTimeZone($timezone));
            }

            $formatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                $timezone,
                IntlDateFormatter::TRADITIONAL,
                $pattern
            );

            return $formatter->format($date);
        } catch (\Exception $e) {
            return '';
        }
    }
}

if (!function_exists('jdatetime')) {
    function jdatetime($date, string $pattern = 'yyyy/MM/dd HH:mm'): string
    {
        return jdate($date, $pattern);
    }
}

if (!function_exists('to_gregorian')) {
    function to_gregorian(?string $jalaliDate): ?string
    {
        if (!$jalaliDate) {
            return null;
        }

        $jalaliDate = normalize_digits($jalaliDate);

        // اگر شبیه Y-m-d است، بر اساس بازه سال تشخیص بده
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $jalaliDate, $m)) {
            $year = (int) $m[1];
            if ($year >= 1900 && $year <= 2100) {
                return $jalaliDate; // میلادی است
            }
        }

        $jalaliDate = str_replace('-', '/', $jalaliDate);

        try {
            $persianFormatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                'Asia/Tehran',
                IntlDateFormatter::TRADITIONAL,
                'yyyy/MM/dd'
            );

            $timestamp = $persianFormatter->parse($jalaliDate);
            if ($timestamp === false) {
                return null;
            }

            // ← این تغییر کلیدی: با همان timezone تهران فرمت کن، نه با date()
            $gregorianFormatter = new IntlDateFormatter(
                'en_US@calendar=gregorian',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                'Asia/Tehran',
                IntlDateFormatter::TRADITIONAL,
                'yyyy-MM-dd'
            );

            return $gregorianFormatter->format($timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }
}
