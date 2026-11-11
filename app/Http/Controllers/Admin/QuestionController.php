<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $topicId = $request->input('topic_id');
        $topics  = Topic::with('subject')->orderBy('name')->get();

        $questions = Question::with(['topic.subject', 'options'])
            ->when($topicId, fn($q) => $q->where('topic_id', $topicId))
            ->latest()
            ->paginate(15);

        return view('admin.questions.index', compact('questions', 'topics', 'topicId'));
    }

    public function create(Request $request)
    {
        $topics = Topic::with('subject')->orderBy('name')->get();
        $selectedTopic = $request->input('topic_id');

        return view('admin.questions.create', compact('topics', 'selectedTopic'));
    }

    public function store(Request $request)
    {
        $data = $this->validateQuestion($request);

        DB::transaction(function () use ($data, $request) {
            $question = Question::create([
                'topic_id'     => $data['topic_id'],
                'text'         => $data['text'],
                'code_snippet' => $data['code_snippet'] ?? null,
                'difficulty'   => $data['difficulty'],
                'is_active'    => $request->boolean('is_active', true),
            ]);

            foreach ($data['options'] as $i => $optText) {
                $question->options()->create([
                    'text'       => $optText,
                    'is_correct' => (int) $data['correct_option'] === $i,
                    'order'      => $i,
                ]);
            }
        });

        return redirect()->route('admin.questions.index', ['topic_id' => $data['topic_id']])
            ->with('success', 'سوال اضافه شد.');
    }

    public function edit(Question $question)
    {
        $question->load('options');
        $topics = Topic::with('subject')->orderBy('name')->get();

        return view('admin.questions.edit', compact('question', 'topics'));
    }

    public function update(Request $request, Question $question)
    {
        $data = $this->validateQuestion($request);

        DB::transaction(function () use ($data, $request, $question) {
            $question->update([
                'topic_id'     => $data['topic_id'],
                'text'         => $data['text'],
                'code_snippet' => $data['code_snippet'] ?? null,
                'difficulty'   => $data['difficulty'],
                'is_active'    => $request->boolean('is_active'),
            ]);

            // حذف گزینه‌های قبلی و ساخت مجدد
            $question->options()->delete();
            foreach ($data['options'] as $i => $optText) {
                $question->options()->create([
                    'text'       => $optText,
                    'is_correct' => (int) $data['correct_option'] === $i,
                    'order'      => $i,
                ]);
            }
        });

        return redirect()->route('admin.questions.index')
            ->with('success', 'سوال ویرایش شد.');
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return redirect()->route('admin.questions.index')
            ->with('success', 'سوال حذف شد.');
    }

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'topic_id'        => 'required|exists:topics,id',
            'text'            => 'required|string|min:5',
            'code_snippet'    => 'nullable|string|max:5000',
            'difficulty'      => 'required|in:easy,medium,hard',
            'options'         => 'required|array|size:4',
            'options.*'       => 'required|string|max:2000',
            'correct_option'  => 'required|integer|between:0,3',
            'is_active'       => 'nullable|boolean',
        ], [
            'text.required'       => 'متن سوال الزامی است.',
            'options.size'        => 'باید دقیقاً ۴ گزینه وارد کنید.',
            'options.*.required'  => 'همه گزینه‌ها باید پر شوند.',
            'correct_option.between' => 'گزینه صحیح را انتخاب کنید.',
        ]);
    }
    public function importForm()
    {
        $topics = Topic::with('subject')->orderBy('name')->get();
        return view('admin.questions.import', compact('topics'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'topic_id'  => 'required|exists:topics,id',
            'json_file' => 'nullable|file|mimes:json,txt|max:5120',
            'json_text' => 'nullable|string',
        ], [
            'topic_id.required' => 'مبحث را انتخاب کنید.',
        ]);

        if (!$request->hasFile('json_file') && !$request->filled('json_text')) {
            return back()->withInput()->with('error', 'یا فایل JSON آپلود کنید یا متن JSON را وارد کنید.');
        }

        $jsonContent = $request->hasFile('json_file')
            ? file_get_contents($request->file('json_file')->getRealPath())
            : $request->json_text;

        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withInput()->with('error', 'خطا در خواندن JSON: ' . json_last_error_msg());
        }

        // پشتیبانی از دو فرمت: {"questions": [...]} یا [...]
        $questions = $data['questions'] ?? (is_array($data) && isset($data[0]) ? $data : null);

        if (!is_array($questions) || empty($questions)) {
            return back()->withInput()->with('error', 'فرمت JSON نامعتبر است. باید شامل کلید "questions" با آرایه‌ای از سوالات باشد.');
        }

        // اعتبارسنجی همه قبل از ذخیره
        $errors = [];
        $validQuestions = [];

        foreach ($questions as $idx => $q) {
            $num = $idx + 1;
            $normalized = $this->normalizeImportedQuestion($q);

            if ($normalized === null) {
                $errors[] = "سوال {$num}: داده‌ها نامعتبر است (متن خالی، یا گزینه‌ها نه دقیقاً ۴ مورد، یا پاسخ صحیح مشخص نیست).";
                continue;
            }

            $validQuestions[] = $normalized;
        }

        if (!empty($errors)) {
            return back()->withInput()->with('import_errors', $errors);
        }

        $created = 0;
        DB::transaction(function () use ($validQuestions, $request, &$created) {
            foreach ($validQuestions as $q) {
                $question = Question::create([
                    'topic_id'     => $request->topic_id,
                    'text'         => $q['text'],
                    'code_snippet' => $q['code_snippet'],
                    'difficulty'   => $q['difficulty'],
                    'is_active'    => true,
                ]);

                foreach ($q['options'] as $i => $opt) {
                    $question->options()->create([
                        'text'       => $opt['text'],
                        'is_correct' => $opt['is_correct'],
                        'order'      => $i,
                    ]);
                }

                $created++;
            }
        });

        return redirect()->route('admin.questions.index', ['topic_id' => $request->topic_id])
            ->with('success', "{$created} سوال با موفقیت وارد شد.");
    }

    public function downloadTemplate()
    {
        $template = [
            'questions' => [
                [
                    'text'         => 'متن سوال نمونه (جایگزین کنید)',
                    'code_snippet' => null,
                    'difficulty'   => 'easy',
                    'options'      => [
                        ['text' => 'گزینه صحیح', 'is_correct' => true],
                        ['text' => 'گزینه دوم', 'is_correct' => false],
                        ['text' => 'گزینه سوم', 'is_correct' => false],
                        ['text' => 'گزینه چهارم', 'is_correct' => false],
                    ],
                ],
                [
                    'text'         => 'سوال با کد (نمونه)',
                    'code_snippet' => "for (\$i = 0; \$i < 5; \$i++) {\n    echo \$i;\n}",
                    'difficulty'   => 'medium',
                    'options'      => [
                        ['text' => '012345', 'is_correct' => false],
                        ['text' => '01234', 'is_correct' => true],
                        ['text' => '12345', 'is_correct' => false],
                        ['text' => '1234', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        return response()->json($template, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ->header('Content-Disposition', 'attachment; filename="questions-template.json"');
    }

    /**
     * نرمال‌سازی سوال import شده — پشتیبانی از دو فرمت:
     * ۱) options: [{text, is_correct}, ...]
     * ۲) options: ["a","b","c","d"], correct: 0
     */
    private function normalizeImportedQuestion($q): ?array
    {
        if (!is_array($q) || empty($q['text'])) {
            return null;
        }

        $options = $q['options'] ?? null;
        if (!is_array($options) || count($options) !== 4) {
            return null;
        }

        // تشخیص فرمت
        $hasIsCorrectField = false;
        foreach ($options as $opt) {
            if (is_array($opt) && array_key_exists('is_correct', $opt)) {
                $hasIsCorrectField = true;
                break;
            }
        }

        $correctIndex = $q['correct'] ?? $q['correct_option'] ?? $q['answer'] ?? null;

        $normalizedOptions = [];
        $correctCount = 0;

        foreach ($options as $i => $opt) {
            $text = is_array($opt) ? (string) ($opt['text'] ?? '') : (string) $opt;
            if ($text === '') {
                return null;
            }

            if ($hasIsCorrectField) {
                $isCorrect = is_array($opt) && !empty($opt['is_correct']);
            } else {
                $isCorrect = ($correctIndex !== null && (int) $correctIndex === $i);
            }

            if ($isCorrect) {
                $correctCount++;
            }

            $normalizedOptions[] = ['text' => $text, 'is_correct' => $isCorrect];
        }

        if ($correctCount !== 1) {
            return null;
        }

        $difficulty = $q['difficulty'] ?? 'medium';
        if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
            $difficulty = 'medium';
        }

        return [
            'text'         => (string) $q['text'],
            'code_snippet' => $q['code_snippet'] ?? null,
            'difficulty'   => $difficulty,
            'options'      => $normalizedOptions,
        ];
    }
}
