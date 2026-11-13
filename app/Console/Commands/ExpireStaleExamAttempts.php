<?php

namespace App\Console\Commands;

use App\Services\ExamService;
use Illuminate\Console\Command;

class ExpireStaleExamAttempts extends Command
{
    protected $signature = 'exams:expire-stale';

    protected $description = 'بستن خودکار تلاش‌های آزمونی که زمانشان به پایان رسیده است';

    public function handle(ExamService $service): int
    {
        $service->expireStaleAttempts();

        $this->info('تلاش‌های منقضی بررسی و بسته شدند.');

        return self::SUCCESS;
    }
}
