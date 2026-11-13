<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $fillable = [
        'class_subject_id', 'created_by', 'title', 'description', 'attachment_path',
        'deadline_at', 'max_score', 'allow_late_submission', 'is_active',
        'requires_code', 'allow_file_upload',
    ];

    protected $casts = [
        'deadline_at'            => 'datetime',
        'allow_late_submission'  => 'boolean',
        'is_active'              => 'boolean',
        'requires_code'          => 'boolean',
        'allow_file_upload'      => 'boolean',
        'max_score'              => 'float',
    ];

    public function classSubject()
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(ExerciseSubmission::class);
    }

    public function isExpired(): bool
    {
        return $this->deadline_at && now()->gt($this->deadline_at);
    }

    public function isAcceptingSubmissions(): bool
    {
        if (!$this->is_active) return false;
        if (!$this->isExpired()) return true;
        return $this->allow_late_submission;
    }

    /**
     * آیا مهلت گذشته و ارسال دیرهنگام هم مجاز نیست؟
     * در این حالت دانش‌آموزی که پاسخ نداده، نمره صفر می‌گیرد.
     */
    public function isMissed(): bool
    {
        return $this->isExpired() && !$this->allow_late_submission;
    }

    public function statusLabel(): array
    {
        if (!$this->is_active) {
            return ['label' => 'غیرفعال', 'color' => 'secondary'];
        }
        if ($this->isExpired()) {
            return $this->allow_late_submission
                ? ['label' => 'مهلت گذشته (ارسال با تأخیر مجاز)', 'color' => 'warning']
                : ['label' => 'پایان مهلت', 'color' => 'danger'];
        }
        return ['label' => 'دریافت پاسخ', 'color' => 'success'];
    }
}
