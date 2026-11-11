<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'attendance_day_id',
        'student_id',
        'session_number',
        'status',
        'note',
        'is_customized',
    ];

    protected $casts = [
        'is_customized'  => 'boolean',
        'session_number' => 'integer',
    ];

    public function attendanceDay()
    {
        return $this->belongsTo(AttendanceDay::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public static function statuses(): array
    {
        return [
            'present'   => 'حاضر',
            'absent'    => 'غایب',
            'late'      => 'تاخیر',
            'excused'   => 'با اجازه',
            'left'      => 'ترک کلاس',
            'cancelled' => 'برگزار نشد',
        ];
    }

    public static function statusEmoji(?string $status): string
    {
        return match($status) {
            'present'   => '✅',
            'absent'    => '❌',
            'late'      => '⏰',
            'excused'   => '📝',
            'left'      => '🚪',
            'cancelled' => '🚫',
            default     => '➖',
        };
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'present'   => 'success',
            'absent'    => 'danger',
            'late'      => 'warning',
            'excused'   => 'info',
            'left'      => 'secondary',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }
}
