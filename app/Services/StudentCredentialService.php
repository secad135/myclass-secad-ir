<?php

namespace App\Services;

class StudentCredentialService
{
    /**
     * تولید رمز اولیه — فقط حروف و اعداد، بدون کاراکتر خاص
     * حروف شبیه‌به‌هم حذف شده (0/O، 1/l/I)
     */
    public function generatePassword(int $length = 8): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';   // بدون I, O
        $lower   = 'abcdefghjkmnpqrstuvwxyz';    // بدون i, l, o
        $digits  = '23456789';                    // بدون 0, 1
        $all     = $upper . $lower . $digits;

        // مطمئن شو حداقل یک حرف بزرگ، یک کوچک، یک عدد دارد
        $password  = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];

        // بقیه کاراکترها به صورت تصادفی
        for ($i = 3; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // جای‌ها را به هم بزن
        return str_shuffle($password);
    }

    /**
     * ایمیل سیستماتیک بر اساس ID دانش‌آموز
     * مثلاً ID=7 → s007@myclass.ir
     */
    public function generateEmail(int $studentId, string $domain = 'myclass.ir'): string
    {
        return 's' . str_pad((string) $studentId, 3, '0', STR_PAD_LEFT) . '@' . $domain;
    }

    /**
     * آماده کردن متن پیام برای شاد
     */
    /**
     * آماده کردن متن پیام برای شاد
     */
    public function buildShadMessage(string $name, string $email, string $password): string
    {
        $loginUrl = 'https://myclass.secad.ir/login';

        return "دانش‌آموز گرامی، {$name}\n\n"
            . "اطلاعات ورود شما به سامانه آموزشی MyClass به شرح زیر است:\n\n"
            . "آدرس سامانه:\n{$loginUrl}\n\n"
            . "نام کاربری (ایمیل):\n{$email}\n\n"
            . "رمز عبور اولیه:\n{$password}\n\n"
            . "خواهشمند است پس از اولین ورود، نسبت به تغییر رمز عبور خود اقدام فرمایید.";
    }
}
