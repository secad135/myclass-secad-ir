<?php

namespace App\Http\Controllers\Teacher;

use App\Models\RankingWeight;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RankingWeightController extends BaseController
{
    public function index()
    {
        $teacherId = $this->teacherId();
        $percents = RankingWeight::percentsForTeacher($teacherId);
        $overrides = RankingWeight::query()
            ->where('teacher_id', $teacherId)
            ->get()
            ->keyBy('component');

        $components = RankingWeight::COMPONENTS;
        $defaults = RankingWeight::defaultPercents();
        $missingComponents = array_diff(array_keys($components), $overrides->keys()->all());
        $total = array_sum($percents);

        return view('teacher.ranking-weights.index', compact(
            'components',
            'percents',
            'defaults',
            'overrides',
            'missingComponents',
            'total'
        ));
    }

    public function create(Request $request)
    {
        $teacherId = $this->teacherId();
        $components = RankingWeight::COMPONENTS;

        $overriddenComponents = RankingWeight::query()
            ->where('teacher_id', $teacherId)
            ->pluck('component')
            ->all();

        $availableComponents = array_diff(array_keys($components), $overriddenComponents);

        if ($availableComponents === []) {
            return redirect()
                ->route('teacher.ranking-weights.index')
                ->with('error', 'برای همه مؤلفه‌ها وزن سفارشی ثبت شده است. برای تغییر، از دکمه ویرایش استفاده کنید.');
        }

        $selectedComponent = (string) $request->query('component', reset($availableComponents));

        if (! in_array($selectedComponent, $availableComponents, true)) {
            $selectedComponent = reset($availableComponents);
        }

        $defaults = RankingWeight::defaultPercents();

        return view('teacher.ranking-weights.create', compact(
            'components',
            'availableComponents',
            'selectedComponent',
            'defaults'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'component' => ['required', Rule::in(array_keys(RankingWeight::COMPONENTS))],
            'weight'    => ['required', 'numeric', 'between:0,100'],
        ]);

        $teacherId = $this->teacherId();

        $alreadyExists = RankingWeight::query()
            ->where('teacher_id', $teacherId)
            ->where('component', $data['component'])
            ->exists();

        if ($alreadyExists) {
            return back()
                ->withInput()
                ->withErrors(['component' => 'برای این مؤلفه قبلاً وزن سفارشی ثبت شده است.']);
        }

        $percents = RankingWeight::percentsForTeacher($teacherId);
        $percents[$data['component']] = (float) $data['weight'];

        if (array_sum($percents) <= 0) {
            return back()
                ->withInput()
                ->withErrors(['weight' => 'مجموع وزن مؤلفه‌ها باید بیشتر از صفر باشد.']);
        }

        RankingWeight::create([
            'teacher_id' => $teacherId,
            'component'  => $data['component'],
            'weight'     => $data['weight'],
        ]);

        return redirect()
            ->route('teacher.ranking-weights.index')
            ->with('success', 'وزن مؤلفه «' . RankingWeight::label($data['component']) . '» ثبت شد.');
    }

    public function edit(RankingWeight $rankingWeight)
    {
        $this->assertOwnsWeight($rankingWeight);

        $components = RankingWeight::COMPONENTS;
        $defaults = RankingWeight::defaultPercents();

        return view('teacher.ranking-weights.edit', compact(
            'rankingWeight',
            'components',
            'defaults'
        ));
    }

    public function update(Request $request, RankingWeight $rankingWeight)
    {
        $this->assertOwnsWeight($rankingWeight);

        $data = $request->validate([
            'weight' => ['required', 'numeric', 'between:0,100'],
        ]);

        $percents = RankingWeight::percentsForTeacher($this->teacherId());
        $percents[$rankingWeight->component] = (float) $data['weight'];

        if (array_sum($percents) <= 0) {
            return back()
                ->withInput()
                ->withErrors(['weight' => 'مجموع وزن مؤلفه‌ها باید بیشتر از صفر باشد.']);
        }

        $rankingWeight->update(['weight' => $data['weight']]);

        return redirect()
            ->route('teacher.ranking-weights.index')
            ->with('success', 'وزن مؤلفه «' . RankingWeight::label($rankingWeight->component) . '» به‌روزرسانی شد.');
    }

    public function destroy(RankingWeight $rankingWeight)
    {
        $this->assertOwnsWeight($rankingWeight);

        $label = RankingWeight::label($rankingWeight->component);
        $rankingWeight->delete();

        return redirect()
            ->route('teacher.ranking-weights.index')
            ->with('success', 'وزن مؤلفه «' . $label . '» حذف شد و به مقدار پیش‌فرض بازگشت.');
    }

    private function assertOwnsWeight(RankingWeight $rankingWeight): void
    {
        abort_unless((int) $rankingWeight->teacher_id === $this->teacherId(), 403);
    }
}
