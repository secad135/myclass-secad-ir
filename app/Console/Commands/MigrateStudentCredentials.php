<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StudentCredentialService;
use Illuminate\Console\Command;

class MigrateStudentCredentials extends Command
{
    protected $signature = 'students:migrate-credentials {--force : بدون تأیید اجرا شود}';
    protected $description = 'تبدیل ایمیل و رمز دانش‌آموزان فعلی به الگوی سیستماتیک';

    public function handle()
    {
        $service  = new StudentCredentialService();
        $students = User::students()->orderBy('id')->get();

        if ($students->isEmpty()) {
            $this->info('هیچ دانش‌آموزی وجود ندارد.');
            return 0;
        }

        $this->warn("این کامند برای {$students->count()} دانش‌آموز ایمیل و رمز جدید تولید می‌کند.");
        $this->warn('رمز فعلی همه باطل می‌شود و باید اطلاعات جدید را به دانش‌آموزان بدهید.');

        if (!$this->option('force')) {
            if (!$this->confirm('ادامه می‌دهید؟')) {
                $this->info('لغو شد.');
                return 0;
            }
        }

        $bar = $this->output->createProgressBar($students->count());
        $bar->start();

        foreach ($students as $student) {
            $newPassword = $service->generatePassword();

            $student->update([
                'email'                => $service->generateEmail($student->id),
                'password'             => $newPassword,
                'initial_password'     => $newPassword,
                'must_change_password' => true,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ {$students->count()} دانش‌آموز به‌روزرسانی شدند.");
        $this->info('اطلاعات جدید را در صفحه «برگه اطلاعات ورود» ببینید: ' . url('/admin/students/credentials'));

        return 0;
    }
}