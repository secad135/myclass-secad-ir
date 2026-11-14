<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RankingWeight;
use App\Models\User;
use Illuminate\Http\Request;

class RankingWeightController extends Controller
{
    public function index()
    {
        $teachers = User::teachers()
            ->orderBy('name')
            ->get()
            ->map(function (User $teacher) {
                $percents = RankingWeight::percentsForTeacher($teacher->id);

                return [
                    'teacher' => $teacher,
                    'total' => array_sum($percents),
                    'override_count' => RankingWeight::query()
                        ->where('teacher_id', $teacher->id)
                        ->count(),
                ];
            });

        $defaults = RankingWeight::defaultPercents();

        return view('admin.ranking-weights.index', compact('teachers', 'defaults'));
    }

    public function show(User $teacher)
    {
        $this->assertTeacher($teacher);

        $components = RankingWeight::COMPONENTS;
        $percents = RankingWeight::percentsForTeacher($teacher->id);
        $defaults = RankingWeight::defaultPercents();
        $overrides = RankingWeight::query()
            ->where('teacher_id', $teacher->id)
            ->get()
            ->keyBy('component');
        $total = array_sum($percents);

        return view('admin.ranking-weights.show', compact(
            'teacher',
            'components',
            'percents',
            'defaults',
            'overrides',
            'total'
        ));
    }

    public function update(Request $request, User $teacher, string $component)
    {
        $this->assertTeacher($teacher);
        $this->assertComponent($component);

        $data = $request->validate([
            'weight' => ['required', 'numeric', 'between:0,100'],
        ]);

        $percents = RankingWeight::percentsForTeacher($teacher->id);
        $percents[$component] = (float) $data['weight'];

        if (array_sum($percents) <= 0) {
            return back()
                ->withInput()
                ->withErrors(['weight' => 'مجموع وزن مؤلفه‌ها باید بیشتر از صفر باشد.']);
        }

        RankingWeight::updateOrCreate(
            ['teacher_id' => $teacher->id, 'component' => $component],
            ['weight' => $data['weight']]
        );

        return redirect()
            ->route('admin.ranking-weights.show', $teacher)
            ->with('success', 'وزن مؤلفه «' . RankingWeight::label($component) . '» به‌روزرسانی شد.');
    }

    public function destroy(User $teacher, string $component)
    {
        $this->assertTeacher($teacher);
        $this->assertComponent($component);

        RankingWeight::query()
            ->where('teacher_id', $teacher->id)
            ->where('component', $component)
            ->delete();

        return redirect()
            ->route('admin.ranking-weights.show', $teacher)
            ->with('success', 'وزن مؤلفه «' . RankingWeight::label($component) . '» حذف شد و به مقدار پیش‌فرض بازگشت.');
    }

    private function assertTeacher(User $teacher): void
    {
        abort_unless($teacher->is_teacher, 404);
    }

    private function assertComponent(string $component): void
    {
        abort_unless(array_key_exists($component, RankingWeight::COMPONENTS), 404);
    }
}
