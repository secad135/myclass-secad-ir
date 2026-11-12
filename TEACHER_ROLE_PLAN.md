# طرح پیاده‌سازی نقش «معلم» — myclass

> این فایل مرجع اصلی کار است. اگر جلسه قطع شد یا توکن تمام شد، از همین فایل ادامه بده.
> شاخه کاری: `feature/teacher-role` (از `main`)
> وضعیت کلی: **در حال پیاده‌سازی — فاز ۰ تکمیل شد؛ آماده شروع فاز ۱.**

---

## ۰. قانون کاری و نحوه ادامه بعد از قطعی

1. شاخه را چک کن: `git branch --show-current`
2. وضعیت فایل‌ها: `git status --short`
3. بخش «گزارش پیشرفت» (بخش ۱۳) را بخوان.
4. اولین مورد ⬜ را ادامه بده.
5. بعد از هر مرحله: تست کن، وضعیت را در جدول پیشرفت به ✅ تغییر بده و commit بزن.
6. هیچ‌وقت مستقیم روی `main` کار نکن. merge فقط بعد از تأیید کاربر.

نام commitهای پیشنهادی در هر فاز آمده است.

---

## ۱. تصمیم‌های تأییدشده کاربر

| موضوع | تصمیم |
|---|---|
| شاخه | `feature/teacher-role` از `main`، push شود، بعد از تأیید merge به `main` |
| فازبندی | بله |
| حساب معلم | مستقل با `is_teacher = true`، بدون چندنقشی |
| وابستگی به مدرسه | معلم می‌تواند در چند مدرسه درس بدهد؛ `school_id` برای معلم اجباری نیست |
| فیلدهای معلم | نام، ایمیل، رمز (رمز توسط سیستم تولید می‌شود) |
| رمز اولیه | تولید خودکار + نمایش به ادمین |
| تغییر رمز در اولین ورود | بله (`must_change_password = true`) |
| ریست رمز توسط ادمین | بله |
| پروفایل معلم | بله (نام، ایمیل، رمز) |
| CRUD معلم | فقط ادمین |
| حذف معلم | اگر به کلاس/درس وصل است یا آزمون/تمرین دارد، ممنوع |
| اتصال معلم | به ازای هر `class_subject` یک معلم (`class_subjects.teacher_id`) |
| چند معلم برای یک درس | خیر |
| چند کلاس/چند مدرسه | بله، از همان ابتدا |
| محل تخصیص | در صفحه کلاس، کنار هر درس (dropdown معلم) |
| معلم کلاس فعلی (ادمین) | با کنترل ادمین قابل تغییر/خالی شدن؛ پاک‌سازی خودکار انجام نمی‌شود |
| معلم خودش را تخصیص دهد | خیر |
| پنل | جداگانه `/teacher` با ظاهر شبیه ادمین |
| معماری | کنترلرهای `Teacher` + استفاده مجدد از ویوهای ادمین با layout/route شرطی |
| امنیت | هم در منو، هم در کنترلر و کوئری‌ها |
| دامنه داده | معلم فقط کلاس‌های خودش، ناظر مدرسه خودش، ادمین همه |
| مالکیت آزمون/تمرین | بر اساس معلم فعلی `class_subject`؛ معلم جدید سوابق قبلی را می‌بیند |
| `created_by` | برای `exams` و `exercises` اضافه شود |
| لاگ فعالیت معلم | بله |
| ایمیل | فقط نام کاربری |
| migration روی هاست | فایل SQL آماده برای phpMyAdmin |
| تست | همه معیارهای بخش ۱۰ |

---

## ۲. معماری نهایی

### نقش‌ها
- `is_admin` → دسترسی کامل (بدون تغییر رفتار فعلی)
- `is_school_manager` → فقط گزارش‌های مدرسه خودش (بدون تغییر)
- `is_teacher` → فقط داده‌های آموزشی خودش
- `is_student` → پنل دانش‌آموز (بدون تغییر)

### مدل دسترسی معلم
- **کلاس‌ها:** کلاس‌هایی که حداقل یک `class_subject` با `teacher_id = معلم` دارند.
- **حضور/غیاب، امتیاز، آزمون، تمرین:** فقط `class_subject`هایی که `teacher_id = معلم` است.
- **دانش‌آموزان:** دانش‌آموزان ثبت‌نام‌شده در کلاس‌های معلم.
- **پروفایل دانش‌آموز:** فقط داده‌های کلاس‌های معلم (همه درس‌های همان کلاس‌ها، نه کلاس‌های دیگر).
- **مباحث و سوالات:** فقط `subject`هایی که معلم در آن‌ها `class_subject` دارد.
- **گزارش کلاس:** فقط کلاس‌های معلم.

### امنیت
- middleware `teacher` برای همه مسیرهای `/teacher`
- بررسی مالکیت در **کنترلر و کوئری**، نه فقط مخفی کردن دکمه
- `abort_unless(..., 403)` برای دسترسی مستقیم به ID دیگران
- routeهای `/admin` و `/manager` برای معلم قابل دسترسی نیستند
- هیچ تغییری در رفتار ادمین/ناظر/دانش‌آموز ایجاد نشود مگر افزودن فیلدهای جدید

### ساختار پنل
- route prefix: `/teacher` و name prefix: `teacher.`
- کنترلرها: `App\Http\Controllers\Teacher\*`
- layout: `resources/views/layouts/teacher.blade.php` (کپی سبک‌شده از layout ادمین با منوی معلم)
- ویوهای مشترک: استفاده مجدد از `resources/views/admin/*` با یک متغیر:
  ```blade
  @php $panelPrefix = auth()->user()->is_teacher ? 'teacher' : 'admin'; @endphp
  @extends(auth()->user()->is_teacher ? 'layouts.teacher' : 'layouts.admin')
  ```
  در ویوهای مشترک با ناظر، منطق `$isManager` هم حفظ و گسترش داده می‌شود.
- در ویوهای مشترک، دکمه‌های فقط‌ادمین با `@if(!auth()->user()->is_teacher)` مخفی می‌شوند.
- routeها با `route($panelPrefix.'.exams.index', ...)` ساخته می‌شوند.

### ورود و رمز
- `AuthenticatedSessionController@store`: معلم → `teacher.dashboard`
- `/dashboard`: معلم → `teacher.dashboard`
- `EnsurePasswordChanged`: برای معلم به `teacher.profile` هدایت شود و routeهای پروفایل معلم مجاز باشند.
- پروفایل معلم: `teacher.profile` / `teacher.profile.update` / `teacher.profile.password`

---

## ۳. تغییرات دیتابیس

### migrationهای لوکال
1. `add_teacher_id_to_class_subjects_table`
   - `class_subjects.teacher_id` → `foreignId nullable`, `nullOnDelete`, بعد از `subject_id`
2. `add_created_by_to_exams_and_exercises_tables`
   - `exams.created_by` → `foreignId nullable`, `nullOnDelete`, بعد از `class_subject_id`
   - `exercises.created_by` → `foreignId nullable`, `nullOnDelete`, بعد از `class_subject_id`

هر دو migration با `Schema::hasColumn()` محافظت شوند تا اجرای دوباره خطا ندهد.

### SQL دستی برای هاست (phpMyAdmin)
> قبل از اجرا از دیتابیس Backup بگیر. ترتیب: SQL → Pull کد → پاک‌سازی cache.

```sql
-- ۱. بررسی وجود ستون‌ها (اگر نتیجه داد، آن بخش را اجرا نکن)
SHOW COLUMNS FROM `class_subjects` LIKE 'teacher_id';
SHOW COLUMNS FROM `exams` LIKE 'created_by';
SHOW COLUMNS FROM `exercises` LIKE 'created_by';

-- ۲. class_subjects
ALTER TABLE `class_subjects`
  ADD COLUMN `teacher_id` BIGINT UNSIGNED NULL AFTER `subject_id`,
  ADD CONSTRAINT `class_subjects_teacher_id_foreign`
    FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ۳. exams
ALTER TABLE `exams`
  ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `class_subject_id`,
  ADD CONSTRAINT `exams_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ۴. exercises
ALTER TABLE `exercises`
  ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `class_subject_id`,
  ADD CONSTRAINT `exercises_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
```

فایل آماده: `database/sql/teacher_role_migration.sql` (در فاز ۳ ساخته می‌شود).

---

## ۴. فایل‌های جدید و تغییریافته

### جدید
- `database/migrations/*_add_teacher_id_to_class_subjects_table.php`
- `database/migrations/*_add_created_by_to_exams_and_exercises_tables.php`
- `app/Http/Middleware/EnsureUserIsTeacher.php`
- `app/Http/Controllers/Admin/TeacherController.php`
- `app/Http/Controllers/Teacher/BaseController.php` (یا trait برای بررسی مالکیت)
- `app/Http/Controllers/Teacher/DashboardController.php`
- `app/Http/Controllers/Teacher/ProfileController.php`
- `app/Http/Controllers/Teacher/SchoolClassController.php`
- `app/Http/Controllers/Teacher/StudentController.php`
- `app/Http/Controllers/Teacher/AttendanceController.php`
- `app/Http/Controllers/Teacher/ExamController.php`
- `app/Http/Controllers/Teacher/ExerciseController.php`
- `app/Http/Controllers/Teacher/TopicController.php`
- `app/Http/Controllers/Teacher/QuestionController.php`
- `resources/views/layouts/teacher.blade.php`
- `resources/views/teacher/dashboard.blade.php`
- `resources/views/teacher/profile/edit.blade.php`
- `resources/views/admin/teachers/index.blade.php`
- `resources/views/admin/teachers/create.blade.php`
- `resources/views/admin/teachers/edit.blade.php`
- `resources/views/admin/teachers/_form.blade.php`
- `database/sql/teacher_role_migration.sql`
- `TEACHER_ROLE_PLAN.md` (کپی همین فایل در ریشه پروژه در شروع پیاده‌سازی)

### تغییریافته
- `routes/web.php`
- `bootstrap/app.php` (alias middleware `teacher`)
- `app/Http/Middleware/EnsurePasswordChanged.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Models/User.php`
- `app/Models/ClassSubject.php`
- `app/Models/Exam.php`
- `app/Models/Exercise.php`
- `app/Models/Topic.php` / `Question.php` (در صورت نیاز scope)
- `app/Http/Controllers/Admin/DashboardController.php` (فیلتر فعالیت‌ها به دانش‌آموز)
- `app/Services/ActivityLogger.php` (پشتیبانی معلم)
- `app/Http/Controllers/Admin/SchoolClassController.php`
- `resources/views/layouts/admin.blade.php` (لینک معلم‌ها)
- `resources/views/admin/classes/show.blade.php`
- `resources/views/admin/classes/create.blade.php`
- `resources/views/admin/classes/edit.blade.php`
- ویوهای مشترک: `admin/classes/index`, `students/index`, `students/show`, `classes/report`, `classes/full-report`, `attendance/show`, `exams/*`, `exercises/*`, `questions/*`, `topics/*`

---

## ۵. فاز ۰ — آماده‌سازی

- ✅ جمع‌آوری نیازمندی‌ها و تصمیم‌ها
- ✅ بررسی کد فعلی (نقش‌ها، routes، layoutها، سرویس گزارش)
- ✅ ساخت شاخه:
  ```bash
  git checkout -b feature/teacher-role
  git push -u origin feature/teacher-role
  ```
- ✅ کپی این فایل به `TEACHER_ROLE_PLAN.md` در ریشه پروژه
- ✅ Backup دیتابیس لوکال: `/tmp/opencode/myclass-before-teacher.sql`
- ⬜ بررسی ستون‌های هاست با SQL بخش ۳ (سمت کاربر روی cPanel)

**commit:** `docs(teacher): add implementation plan`

---

## ۶. فاز ۱ — پایه، حساب معلم، تخصیص، پنل، حضور/امتیاز

### ۱.۶ migration و مدل‌ها
- [x] migration `class_subjects.teacher_id`
- [x] migration `exams.created_by` و `exercises.created_by`
- [x] `User`: روابط `classSubjectsAsTeacher`, `homeroomClasses`, `createdExams`, `createdExercises`
- [x] `ClassSubject`: `teacher_id` در fillable + `teacher()` + `scopeForTeacher`
- [x] `Exam` / `Exercise`: `created_by` در fillable + `creator()`
- [x] `SchoolClass`: `scopeForTeacher` در صورت نیاز
- [x] `php artisan migrate` لوکال + تست ستون‌ها

**commit:** `feat(teacher): add teacher schema and model relations`

### ۲.۶ middleware و ورود
- [x] `EnsureUserIsTeacher` + alias `teacher` در `bootstrap/app.php`
- [x] `EnsurePasswordChanged`: مسیر معلم به `teacher.profile`
- [x] `AuthenticatedSessionController`: ریدایرکت معلم
- [x] `/dashboard`: ریدایرکت معلم
- [x] تست: معلم بدون تغییر رمز → پروفایل؛ بعد از تغییر → داشبورد

**commit:** `feat(teacher): add teacher auth guard and redirects`

### ۳.۶ CRUD معلم در ادمین
- [x] `Admin\TeacherController` (index/create/store/edit/update/destroy/resetPassword)
- [x] routeهای `admin.teachers.*` (قبل از resource برای reset-password)
- [x] views `admin/teachers/*`
- [x] لینک «معلم‌ها» در سایدبار ادمین، گروه «کلاس و افراد»
- [x] store: تولید رمز با `StudentCredentialService`، `is_teacher=true`, `initial_password`, `must_change_password=true`
- [x] resetPassword: رمز جدید + `must_change_password=true`
- [x] destroy: اگر `classSubjectsAsTeacher` یا `homeroomClasses` یا `createdExams` یا `createdExercises` دارد → ممنوع با پیام
- [x] تست: ساخت/ویرایش/ریست/حذف معلم

**commit:** `feat(teacher): add admin teacher CRUD`

### ۴.۶ تخصیص معلم به درس
- [x] route `PUT admin/classes/{class}/subjects/{classSubject}/teacher`
- [x] `Admin\SchoolClassController@updateSubjectTeacher`
- [x] اعتبارسنجی: `teacher_id` باید متعلق به کاربر `is_teacher` باشد
- [x] در `admin/classes/show.blade.php`: ستون «معلم» و dropdown برای هر درس
- [x] در فرم افزودن درس هم dropdown اختیاری معلم
- [x] در `classes/create` و `classes/edit`: dropdown معلم کلاس فقط `User::teachers()`؛ اگر مقدار فعلی غیرمعلم است، به‌صورت گزینه «کاربر فعلی (غیرمعلم)» نگه داشته شود تا پاک نشود
- [x] نمایش نام معلم هر درس در جدول
- [x] تست: تخصیص/تغییر/حذف معلم درس

**commit:** `feat(teacher): assign teachers to class subjects`

### ۵.۶ اسکلت پنل معلم
- [x] `layouts/teacher.blade.php` با منوی: داشبورد، کلاس‌های من، دانش‌آموزان، آزمون‌ها، تمرین‌ها، مباحث، بانک سوالات، پروفایل
- [x] route group `/teacher` با `auth`, `teacher`, `password.changed`
- [x] `Teacher\BaseController` با helperهای:
  - `ownClassSubjectIds()`
  - `ownClassIds()`
  - `ownSubjectIds()`
  - `assertOwnsClassSubject()`
  - `assertOwnsExam()`
  - `assertOwnsExercise()`
  - `assertOwnsStudent()`
- [x] `Teacher\DashboardController` + `teacher/dashboard.blade.php`
- [x] `Teacher\ProfileController` + view (نام/ایمیل/رمز)
- [x] تست رندر داشبورد و پروفایل + تغییر رمز

**commit:** `feat(teacher): add teacher panel shell and profile`

### ۶.۶ کلاس‌ها و دانش‌آموزان معلم
- [x] `Teacher\SchoolClassController@index/show` فقط کلاس‌های خودش
- [x] `Teacher\StudentController@index/show` فقط دانش‌آموزان کلاس‌های خودش
- [x] استفاده از `StudentReportService` با فیلتر `classSubjectIds` کلاس‌های معلم
- [x] گسترش متدهای سرویس در صورت نیاز:
  - `getPointsSummary(..., ?array $onlyClassSubjectIds = null)`
  - `getAbsenceDetails(..., ?array $onlyClassSubjectIds = null)`
  - `getGroupedAttendanceByClassSubject(..., ?array $onlyClassSubjectIds = null)`
  - `getSubjectSummary(..., ?array $onlyClassSubjectIds = null)`
- [x] در `students/show` برچسب «وضعیت در کلاس‌های شما» و حذف لینک‌های غیرمجاز
- [x] در `classes/show` لینک حضور فقط برای درس‌های خود معلم + نمایش معلم هر درس
- [x] مخفی کردن CRUD ادمین در ویوهای مشترک برای معلم
- [x] تست: معلم کلاس دیگر → 403

**commit:** `feat(teacher): scope classes and students to teacher`

### ۷.۶ حضور/غیاب و امتیاز
- [x] `Teacher\AttendanceController`: show/start/save/destroyDay/points.add/points.delete
- [x] بررسی `classSubject.teacher_id === auth()->id()`
- [x] حذف امتیاز: فقط امتیازی که `created_by === auth()->id()` است (یا ادمین)
- [x] route prefix شرطی در `attendance/show.blade.php`
- [x] لاگ فعالیت برای ثبت حضور/امتیاز (بعد از آماده شدن ActivityLogger در فاز ۲ هم قابل انجام است)
- [x] تست: ثبت حضور، افزودن امتیاز، حذف امتیاز، دسترسی غیرمجاز

**commit:** `feat(teacher): add attendance and points management`

---

## ۷. فاز ۲ — آزمون، تمرین، سوال، مبحث، گزارش

### ۱.۷ آزمون‌ها
- [ ] `Teacher\ExamController`: index/create/store/edit/update/destroy/results/attemptDetail/approveScore
- [ ] فیلتر index به `class_subject.teacher_id`
- [ ] create/edit فقط `class_subject`های خودش
- [ ] store: `created_by = auth()->id()`
- [ ] validateExam: `class_subject_id` باید متعلق به معلم باشد؛ مباحث از همان درس
- [ ] approveScore: فقط attempt مربوط به آزمون خودش
- [ ] حذف تلاش آزمون برای معلم **ممنوع** (route ندارد)
- [ ] route prefix شرطی در `admin/exams/*` و مخفی کردن حذف تلاش
- [ ] تست: CRUD آزمون خودش، 403 برای آزمون دیگران، ثبت نمره معلم

**commit:** `feat(teacher): add teacher exam management`

### ۲.۷ تمرین‌ها
- [ ] `Teacher\ExerciseController`: index/create/store/edit/update/destroy/submissions/grade
- [ ] فیلتر به `class_subject`های خودش
- [ ] store: `created_by = auth()->id()`
- [ ] grade: فقط تمرین خودش؛ `graded_by = auth()->id()`
- [ ] route prefix شرطی در `admin/exercises/*`
- [ ] تست: CRUD تمرین، تصحیح پاسخ، 403

**commit:** `feat(teacher): add teacher exercise management`

### ۳.۷ مباحث و بانک سوالات
- [ ] `Teacher\TopicController`: index/create/store/edit/update/destroy
- [ ] `$subjectIds = ownSubjectIds()`؛ همه کوئری‌ها و اعتبارسنجی‌ها محدود به آن
- [ ] `Teacher\QuestionController`: index/create/store/edit/update/destroy/importForm/import/downloadTemplate
- [ ] topic_id در store/update/import باید در مباحث درس‌های معلم باشد
- [ ] route prefix شرطی در `admin/topics/*` و `admin/questions/*`
- [ ] توجه: محتوای مبحث/سوال در سطح «درس» مشترک است؛ معلم هم‌درس می‌تواند سوالات را ببیند/ویرایش کند. (اگر بعداً جداسازی سازنده لازم شد، `created_by` اضافه شود.)
- [ ] تست: معلم درس دیگر → 403

**commit:** `feat(teacher): add scoped topics and question bank`

### ۴.۷ گزارش‌ها
- [ ] `Teacher\SchoolClassController@report` و `reportFull` برای کلاس‌های خودش
- [ ] استفاده از `admin/classes/report` و `admin/classes/full-report` با route/layout شرطی
- [ ] تست: گزارش کلاس خودش 200، کلاس دیگر 403

**commit:** `feat(teacher): add teacher class reports`

### ۵.۷ لاگ فعالیت
- [ ] `ActivityLogger`: پذیرش معلم علاوه بر دانش‌آموز
- [ ] `Admin\DashboardController`: فیلتر فعالیت‌های اخیر به دانش‌آموزان (`whereHas user is_student`)
- [ ] لاگ: حضور/غیاب، امتیاز، ساخت/ویرایش/حذف آزمون، تمرین، تصحیح، سوال/مبحث
- [ ] تست: لاگ معلم در جدول ثبت شود ولی در ویجت دانش‌آموزان ظاهر نشود

**commit:** `feat(teacher): log teacher activities`

---

## ۸. فاز ۳ — امنیت، تست نهایی، استقرار

- [ ] بازبینی تمام routeهای `/teacher` برای مالکیت
- [ ] تست دسترسی مستقیم با ID کلاس/درس/دانش‌آموز/آزمون/تمرین دیگران → 403
- [ ] تست معلم برای `/admin` و `/manager` → 403
- [ ] تست ادمین و ناظر و دانش‌آموز (بدون regression)
- [ ] `php -l` روی همه فایل‌های PHP تغییر‌یافته
- [ ] `php artisan route:list --name=teacher`
- [ ] `php artisan view:cache` و رندر همه صفحات معلم
- [ ] `git diff --check`
- [ ] ساخت `database/sql/teacher_role_migration.sql`
- [ ] به‌روزرسانی `TEACHER_ROLE_PLAN.md` در ریشه پروژه
- [ ] merge به `main` بعد از تأیید کاربر + push
- [ ] استقرار هاست طبق بخش ۱۱
- [ ] تست پس از استقرار

**commit نهایی:** `test(teacher): harden access and add deployment notes`

---

## ۹. ماتریس دسترسی نهایی

| بخش | ادمین | ناظر | معلم |
|---|---|---|---|
| داشبورد | کامل | مدرسه خودش | کلاس‌های خودش |
| مدارس/سال/پایه/دروس | CRUD | فقط مشاهده | بدون دسترسی |
| مباحث | CRUD | - | CRUD فقط درس‌های خودش |
| بانک سوالات | CRUD | - | CRUD فقط درس‌های خودش |
| کلاس‌ها | CRUD + ثبت‌نام | مشاهده مدرسه | مشاهده کلاس‌های خودش |
| دانش‌آموزان | CRUD | مشاهده مدرسه | مشاهده دانش‌آموزان کلاس‌های خودش |
| ریست رمز دانش‌آموز | بله | نه | نه |
| حضور/غیاب | همه | - | فقط درس‌های خودش |
| امتیاز | همه | - | فقط درس‌های خودش؛ حذف فقط امتیاز خودش |
| آزمون | CRUD همه | - | CRUD آزمون‌های خودش |
| حذف تلاش آزمون | بله | نه | نه |
| نمره معلم | بله | - | بله برای آزمون خودش |
| تمرین | CRUD همه | - | CRUD تمرین‌های خودش |
| تصحیح تمرین | بله | - | بله برای تمرین خودش |
| گزارش کلاس/کامل | همه | مدرسه | کلاس‌های خودش |
| دسته‌های امتیاز | CRUD | - | مشاهده |
| لاگ ورود | بله | نه | نه |
| مدیریت معلم‌ها/ناظرها | بله | نه | نه |
| پروفایل | بله | بله | بله |

---

## ۱۰. تست‌های پذیرش

- [ ] ادمین معلم می‌سازد؛ رمز تولیدشده نمایش داده می‌شود.
- [ ] معلم با رمز اولیه وارد می‌شود و مجبور به تغییر رمز است.
- [ ] بعد از تغییر رمز، به داشبورد معلم می‌رود.
- [ ] ادمین رمز معلم را ریست می‌کند؛ معلم دوباره مجبور به تغییر می‌شود.
- [ ] ایمیل تکراری رد می‌شود.
- [ ] حذف معلمِ متصل/دارای محتوا ممنوع است.
- [ ] ادمین معلم را به درس تخصیص می‌دهد؛ معلم فقط همان درس را می‌بیند.
- [ ] معلم چند کلاس/چند مدرسه را هم‌زمان می‌بیند.
- [ ] معلم به کلاس/درس/دانش‌آموز/آزمون/تمرین دیگران 403 می‌گیرد.
- [ ] معلم به `/admin` و `/manager` دسترسی ندارد.
- [ ] معلم فقط برای درس خودش حضور و امتیاز ثبت می‌کند.
- [ ] معلم نمی‌تواند امتیاز ثبت‌شده توسط دیگران را حذف کند.
- [ ] معلم فقط آزمون/تمرین درس خودش را CRUD می‌کند.
- [ ] معلم نمی‌تواند تلاش آزمون را حذف کند.
- [ ] معلم نمره معلم آزمون خودش را ثبت می‌کند.
- [ ] معلم سوال/مبحث فقط برای درس خودش می‌سازد.
- [ ] گزارش کلاس و گزارش کامل فقط برای کلاس‌های معلم کار می‌کند.
- [ ] ادمین مثل قبل به همه‌چیز دسترسی دارد.
- [ ] ناظر مثل قبل فقط گزارش مدرسه خودش را می‌بیند.
- [ ] دانش‌آموز بدون تغییر کار می‌کند.
- [ ] migration لوکال بدون خطا و idempotent است.
- [ ] Pull هاست + SQL + پاک‌سازی cache بدون خطا.

---

## ۱۱. استقرار روی هاست (بدون ترمینال)

1. **Backup** کامل دیتابیس از cPanel.
2. در phpMyAdmin، SQL بخش ۳ را اجرا کن (فقط بخش‌هایی که ستون وجود ندارد).
3. تأیید ستون‌ها:
   ```sql
   SHOW COLUMNS FROM class_subjects LIKE 'teacher_id';
   SHOW COLUMNS FROM exams LIKE 'created_by';
   SHOW COLUMNS FROM exercises LIKE 'created_by';
   ```
4. بعد از merge به `main`، در cPanel → Git Version Control → **Pull**.
5. در File Manager این دو پوشه را خالی کن (فایل‌ها را پاک کن، خود پوشه نه):
   - `storage/framework/views/`
   - `bootstrap/cache/` (اگر فایل route/config cache وجود دارد)
6. وارد ادمین شو → «معلم‌ها» → ساخت معلم.
7. معلم را به درس‌ها تخصیص بده.
8. با حساب معلم تست کن.
9. اگر خطای 500 دیدی: `storage/logs/laravel.log` آخرین خطا را بخوان.

**ترتیب مهم:** SQL قبل از Pull، چون کد جدید به ستون‌های جدید نیاز دارد.

---

## ۱۲. ریسک‌ها و راهکار

| ریسک | راهکار |
|---|---|
| خراب شدن رفتار ادمین/ناظر | شاخه جدا، تست regression، تغییرات فقط افزایشی |
| فراموش شدن بررسی مالکیت | helperهای BaseController + تست 403 برای هر منبع |
| نمایش لینک‌های ادمین به معلم | استفاده از `$panelPrefix` + `@if(!is_teacher)` در ویوهای مشترک |
| اشتباه در migration روی هاست | SQL آماده + Backup + بررسی ستون قبل از ALTER |
| قفل شدن معلم در تغییر رمز اجباری | تست کامل `EnsurePasswordChanged` قبل از merge |
| دیده شدن داده معلمان دیگر در پروفایل دانش‌آموز | فیلتر `classSubjectIds` بر اساس کلاس‌های معلم |
| تغییر/حذف معلم و از دست رفتن سوابق | `nullOnDelete` + ممنوعیت حذف معلم متصل |
| محتوای مشترک سوالات در سطح درس | پذیرفته‌شده؛ در صورت نیاز بعداً `created_by` برای سوال/مبحث |

---

## ۱۳. گزارش پیشرفت (این جدول را بعد از هر مرحله به‌روز کن)

| مرحله | وضعیت | commit | یادداشت |
|---|---|---|---|
| بررسی کد و جمع‌آوری نیازمندی‌ها | ✅ | - | نقش‌ها، routes، layoutها، سرویس گزارش بررسی شد |
| نوشتن طرح و پرسش‌نامه | ✅ | - | همین فایل |
| ساخت شاخه `feature/teacher-role` | ✅ | - | `feature/teacher-role` از `main` ساخته و push شد |
| کپی طرح به `TEACHER_ROLE_PLAN.md` | ✅ | - | نسخه پروژه ساخته شد |
| migrationها و مدل‌ها | ✅ | `70b119b` | `class_subjects.teacher_id` + `exams/exercises.created_by` |
| middleware و ورود معلم | ✅ | - | |
| CRUD معلم در ادمین | ✅ | - | |
| تخصیص معلم به درس | ✅ | - | |
| layout و اسکلت پنل معلم | ✅ | - | layout معلم + route group + auth/teacher/password.changed |
| داشبورد و پروفایل معلم | ✅ | - | داشبورد اختصاصی + پروفایل تغییر رمز |
| کلاس‌ها و دانش‌آموزان معلم | ✅ | - | scope کلاس/دانش‌آموز + فیلتر سرویس گزارش |
| حضور/غیاب و امتیاز معلم | ✅ | - | فقط درس‌های خود معلم + حذف امتیاز محدود به سازنده |
| آزمون‌های معلم | ⬜ | - | |
| تمرین‌های معلم | ⬜ | - | |
| مباحث و بانک سوالات معلم | ⬜ | - | |
| گزارش‌های معلم | ⬜ | - | |
| لاگ فعالیت معلم | ⬜ | - | |
| تست نهایی و امنیت | ⬜ | - | |
| SQL هاست + استقرار | ⬜ | - | |
| merge به main | ⬜ | - | فقط بعد از تأیید |

---

## ۱۴. دستورهای سریع

```bash
# شاخه
git checkout main && git pull
git checkout -b feature/teacher-role
git push -u origin feature/teacher-role

# migration
php artisan make:migration add_teacher_id_to_class_subjects_table
php artisan make:migration add_created_by_to_exams_and_exercises_tables
php artisan migrate

# بررسی
php -l app/Http/Controllers/Teacher/ExamController.php
php artisan route:list --name=teacher
php artisan view:clear && php artisan view:cache
git diff --check

# commit
git add -A
git commit -m "feat(teacher): ..."
git push
```
