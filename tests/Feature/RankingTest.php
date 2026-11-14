<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\Grade;
use App\Models\PointEntry;
use App\Models\RankingWeight;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\RankingService;
use App\Services\StudentReportService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;

    private ClassSubject $classSubject;

    private User $teacher;

    private User $ali;

    private User $sara;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

        $school = School::create(['name' => 'مدرسه تست']);
        $year = AcademicYear::create([
            'title' => '۱۴۰۴-۱۴۰۵',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);
        $grade = Grade::create(['name' => 'دهم']);

        $this->teacher = User::create([
            'name' => 'دبیر',
            'email' => 'teacher@example.com',
            'password' => 'secret',
            'is_teacher' => true,
        ]);

        $this->class = SchoolClass::create([
            'name' => 'دهم الف',
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $subject = Subject::create(['name' => 'ریاضی']);

        $this->classSubject = ClassSubject::create([
            'school_class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $this->ali = $this->makeStudent('علی');
        $this->sara = $this->makeStudent('سارا');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_higher_exam_and_exercise_scores_rank_better(): void
    {
        $exam = $this->makeExam();
        $this->attempt($exam, $this->ali, 20);
        $this->attempt($exam, $this->sara, 10);

        $exercise = $this->makeExercise(maxScore: 20);
        $this->submit($exercise, $this->ali, 20);
        $this->submit($exercise, $this->sara, 10);

        $ranking = $this->rank();

        $this->assertSame(1, $ranking['علی']['rank']);
        $this->assertSame(2, $ranking['سارا']['rank']);
        $this->assertGreaterThan($ranking['سارا']['rank_score'], $ranking['علی']['rank_score']);
    }

    public function test_missed_exam_counts_as_zero(): void
    {
        $exam = $this->makeExam();
        $this->attempt($exam, $this->ali, 20);

        $ranking = $this->rank();

        $this->assertEquals(0, $ranking['سارا']['rank_components']['exams']['score']);
        $this->assertSame(1, $ranking['سارا']['rank_components']['exams']['missed']);
        $this->assertEquals(100, $ranking['علی']['rank_components']['exams']['score']);
    }

    public function test_absent_day_lowers_attendance_but_other_statuses_do_not(): void
    {
        $days = [];
        foreach (['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04'] as $date) {
            $days[] = AttendanceDay::create([
                'class_subject_id' => $this->classSubject->id,
                'date' => $date,
                'sessions_count' => 1,
                'is_started' => true,
            ]);
        }

        $this->attend($days[0], $this->ali, 'absent');
        $this->attend($days[1], $this->ali, 'late');
        $this->attend($days[2], $this->ali, 'excused');
        $this->attend($days[3], $this->ali, 'left');

        $this->attend($days[0], $this->sara, 'absent');
        $this->attend($days[1], $this->sara, 'absent');
        $this->attend($days[2], $this->sara, 'present');
        $this->attend($days[3], $this->sara, 'cancelled');

        $ranking = $this->rank();

        $this->assertSame(1, $ranking['علی']['rank_components']['attendance']['absence_days']);
        $this->assertEquals(75, $ranking['علی']['rank_components']['attendance']['score']);
        $this->assertSame(2, $ranking['سارا']['rank_components']['attendance']['absence_days']);
        $this->assertEquals(50, $ranking['سارا']['rank_components']['attendance']['score']);
    }

    public function test_cancelled_day_is_not_counted_as_held(): void
    {
        $day = AttendanceDay::create([
            'class_subject_id' => $this->classSubject->id,
            'date' => '2026-03-01',
            'sessions_count' => 1,
            'is_started' => true,
        ]);

        $this->attend($day, $this->ali, 'cancelled');
        $this->attend($day, $this->sara, 'cancelled');

        $ranking = $this->rank();

        $this->assertSame(0, $ranking['علی']['rank_components']['attendance']['held_days']);
        $this->assertNull($ranking['علی']['rank_components']['attendance']['score']);
    }

    public function test_late_allowed_exercise_without_submission_is_not_missed(): void
    {
        $this->makeExercise(allowLate: true);

        $ranking = $this->rank();

        $this->assertSame(0, $ranking['علی']['rank_components']['exercises']['missed']);
        $this->assertNull($ranking['علی']['rank_components']['exercises']['score']);
    }

    public function test_ungraded_submission_is_excluded_from_average(): void
    {
        $exercise = $this->makeExercise(maxScore: 20);
        $this->submit($exercise, $this->ali, null);

        $ranking = $this->rank();

        $this->assertSame(0, $ranking['علی']['rank_components']['exercises']['counted']);
        $this->assertNull($ranking['علی']['rank_components']['exercises']['score']);
    }

    public function test_component_without_data_redistributes_its_weight(): void
    {
        $exam = $this->makeExam();
        $this->attempt($exam, $this->ali, 20);
        $this->attempt($exam, $this->sara, 20);

        $ranking = $this->rank();

        $this->assertNull($ranking['علی']['rank_components']['exercises']['score']);
        $this->assertNull($ranking['علی']['rank_components']['attendance']['score']);
        // (100 × 0.40 + 50 × 0.15) / (0.40 + 0.15) = 86.36
        $this->assertEquals(86.36, $ranking['علی']['rank_score']);
    }

    public function test_manual_points_are_normalized_class_wise(): void
    {
        $this->addPoint($this->ali, 10);
        $this->addPoint($this->sara, 0);

        $ranking = $this->rank();

        $this->assertEquals(100, $ranking['علی']['rank_components']['manual']['score']);
        $this->assertEquals(0, $ranking['سارا']['rank_components']['manual']['score']);
        $this->assertEquals(100, $ranking['علی']['rank_score']);
        $this->assertEquals(0, $ranking['سارا']['rank_score']);
    }

    public function test_equal_manual_points_yield_neutral_score(): void
    {
        $this->addPoint($this->ali, 5);
        $this->addPoint($this->sara, 5);

        $ranking = $this->rank();

        $this->assertEquals(50, $ranking['علی']['rank_components']['manual']['score']);
        $this->assertEquals(50, $ranking['سارا']['rank_components']['manual']['score']);
    }

    public function test_components_outside_date_range_are_ignored(): void
    {
        $this->makeExam(endAt: '2025-12-20 10:00:00');
        $this->makeExercise(deadline: '2025-12-20 10:00:00');

        $ranking = $this->rank('2026-01-01', '2026-12-31');

        $this->assertSame(0, $ranking['علی']['rank_components']['exams']['counted']);
        $this->assertSame(0, $ranking['علی']['rank_components']['exams']['missed']);
        $this->assertSame(0, $ranking['علی']['rank_components']['exercises']['counted']);
        $this->assertSame(0, $ranking['علی']['rank_components']['exercises']['missed']);
    }

    public function test_teacher_custom_weights_are_used_in_ranking(): void
    {
        $exam = $this->makeExam();
        $this->attempt($exam, $this->ali, 20);
        $this->addPoint($this->sara, 10);

        $defaultRanking = $this->rank('2026-01-01', '2026-12-31');
        $this->assertSame(1, $defaultRanking['علی']['rank']);
        $this->assertSame(2, $defaultRanking['سارا']['rank']);

        RankingWeight::create([
            'teacher_id' => $this->teacher->id,
            'component' => 'exams',
            'weight' => 0,
        ]);
        RankingWeight::create([
            'teacher_id' => $this->teacher->id,
            'component' => 'manual',
            'weight' => 100,
        ]);

        $ranking = $this->rank('2026-01-01', '2026-12-31', $this->teacher->id);

        $this->assertSame(1, $ranking['سارا']['rank']);
        $this->assertSame(2, $ranking['علی']['rank']);
        $this->assertEquals(1, $ranking['سارا']['rank_components']['manual']['weight']);
        $this->assertEquals(0, $ranking['سارا']['rank_components']['exams']['weight']);
    }

    public function test_ranking_without_explicit_teacher_uses_class_teacher_weights(): void
    {
        $exam = $this->makeExam();
        $this->attempt($exam, $this->ali, 20);
        $this->addPoint($this->sara, 10);

        RankingWeight::create([
            'teacher_id' => $this->teacher->id,
            'component' => 'exams',
            'weight' => 0,
        ]);
        RankingWeight::create([
            'teacher_id' => $this->teacher->id,
            'component' => 'manual',
            'weight' => 100,
        ]);

        $ranking = app(StudentReportService::class)
            ->getClassRanking($this->class, '2026-01-01', '2026-12-31')
            ->keyBy(fn (array $row) => $row['student']->name);

        $this->assertSame(1, $ranking['سارا']['rank']);
        $this->assertEquals(0, $ranking['سارا']['rank_components']['exams']['weight']);
        $this->assertEquals(1, $ranking['سارا']['rank_components']['manual']['weight']);
    }

    public function test_teacher_can_open_ranking_weights_page(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.ranking-weights.index'))
            ->assertOk()
            ->assertSee('وزن مؤلفه‌های نمره رتبه');

        $this->actingAs($this->teacher)
            ->get(route('teacher.ranking-weights.create'))
            ->assertOk()
            ->assertSee('ثبت وزن سفارشی مؤلفه');
    }

    public function test_teacher_can_manage_ranking_weights(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.ranking-weights.store'), [
                'component' => 'exams',
                'weight' => 50,
            ])
            ->assertRedirect(route('teacher.ranking-weights.index'));

        $this->assertDatabaseHas('ranking_weights', [
            'teacher_id' => $this->teacher->id,
            'component' => 'exams',
            'weight' => 50,
        ]);

        $weight = RankingWeight::firstOrFail();

        $this->actingAs($this->teacher)
            ->put(route('teacher.ranking-weights.update', $weight), [
                'weight' => 65,
            ])
            ->assertRedirect(route('teacher.ranking-weights.index'));

        $this->assertDatabaseHas('ranking_weights', [
            'id' => $weight->id,
            'weight' => 65,
        ]);

        $this->actingAs($this->teacher)
            ->delete(route('teacher.ranking-weights.destroy', $weight))
            ->assertRedirect(route('teacher.ranking-weights.index'));

        $this->assertDatabaseMissing('ranking_weights', ['id' => $weight->id]);
    }

    public function test_teacher_cannot_manage_another_teachers_weights(): void
    {
        $other = User::create([
            'name' => 'دبیر دیگر',
            'email' => 'other@example.com',
            'password' => 'secret',
            'is_teacher' => true,
        ]);

        $weight = RankingWeight::create([
            'teacher_id' => $other->id,
            'component' => 'exams',
            'weight' => 40,
        ]);

        $this->actingAs($this->teacher)
            ->put(route('teacher.ranking-weights.update', $weight), ['weight' => 70])
            ->assertForbidden();

        $this->actingAs($this->teacher)
            ->delete(route('teacher.ranking-weights.destroy', $weight))
            ->assertForbidden();
    }

    public function test_admin_can_manage_teacher_ranking_weights(): void
    {
        $admin = User::create([
            'name' => 'مدیر',
            'email' => 'admin@example.com',
            'password' => 'secret',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ranking-weights.index'))
            ->assertOk()
            ->assertSee('وزن مؤلفه‌های نمره رتبه')
            ->assertSee($this->teacher->name);

        $this->actingAs($admin)
            ->get(route('admin.ranking-weights.show', $this->teacher))
            ->assertOk()
            ->assertSee('وزن‌های رتبه‌بندی');

        $this->actingAs($admin)
            ->put(route('admin.ranking-weights.update', [$this->teacher, 'exams']), [
                'weight' => 55,
            ])
            ->assertRedirect(route('admin.ranking-weights.show', $this->teacher));

        $this->assertDatabaseHas('ranking_weights', [
            'teacher_id' => $this->teacher->id,
            'component' => 'exams',
            'weight' => 55,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.ranking-weights.destroy', [$this->teacher, 'exams']))
            ->assertRedirect(route('admin.ranking-weights.show', $this->teacher));

        $this->assertDatabaseMissing('ranking_weights', [
            'teacher_id' => $this->teacher->id,
            'component' => 'exams',
        ]);
    }

    public function test_admin_cannot_manage_weights_for_non_teacher(): void
    {
        $admin = User::create([
            'name' => 'مدیر',
            'email' => 'admin2@example.com',
            'password' => 'secret',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ranking-weights.show', $this->ali))
            ->assertNotFound();
    }

    private function rank(string $from = '2026-01-01', string $to = '2026-12-31', ?int $teacherId = null): Collection
    {
        return app(RankingService::class)
            ->rank($this->class, [$this->classSubject->id], $from, $to, $teacherId)
            ->keyBy(fn (array $row) => $row['student']->name);
    }

    private function makeStudent(string $name): User
    {
        $student = User::create([
            'name' => $name,
            'email' => $name.'@example.com',
            'password' => 'secret',
            'is_student' => true,
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'school_class_id' => $this->class->id,
            'status' => 'active',
        ]);

        return $student;
    }

    private function makeExam(string $endAt = '2026-05-01 10:00:00'): Exam
    {
        return Exam::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'title' => 'آزمون تست',
            'question_count' => 1,
            'duration_minutes' => 30,
            'start_at' => '2026-04-30 10:00:00',
            'end_at' => $endAt,
            'max_attempts' => 1,
            'is_active' => true,
        ]);
    }

    private function attempt(Exam $exam, User $student, float $score): ExamAttempt
    {
        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => '2026-04-30 10:00:00',
            'finished_at' => '2026-04-30 10:20:00',
            'total_questions' => 1,
            'total_correct' => 1,
            'score' => $score,
            'status' => 'submitted',
        ]);
    }

    private function makeExercise(
        float $maxScore = 20,
        bool $allowLate = false,
        string $deadline = '2026-05-01 10:00:00'
    ): Exercise {
        return Exercise::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'title' => 'تمرین تست',
            'description' => 'توضیح',
            'deadline_at' => $deadline,
            'max_score' => $maxScore,
            'allow_late_submission' => $allowLate,
            'is_active' => true,
        ]);
    }

    private function submit(Exercise $exercise, User $student, ?float $score): ExerciseSubmission
    {
        return ExerciseSubmission::create([
            'exercise_id' => $exercise->id,
            'student_id' => $student->id,
            'content' => 'پاسخ',
            'submitted_at' => '2026-04-30 10:00:00',
            'is_late' => false,
            'score' => $score,
            'graded_at' => $score === null ? null : '2026-05-02 10:00:00',
            'graded_by' => $score === null ? null : $this->teacher->id,
        ]);
    }

    private function attend(AttendanceDay $day, User $student, string $status): Attendance
    {
        return Attendance::create([
            'attendance_day_id' => $day->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'status' => $status,
        ]);
    }

    private function addPoint(User $student, int $points): PointEntry
    {
        return PointEntry::create([
            'student_id' => $student->id,
            'class_subject_id' => $this->classSubject->id,
            'points' => $points,
            'entry_date' => '2026-04-01',
            'created_by' => $this->teacher->id,
        ]);
    }
}
