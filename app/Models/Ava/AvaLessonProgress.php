<?php

namespace App\Models\Ava;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvaLessonProgress extends Model
{
    protected $fillable = [
        'enrollment_id',
        'lesson_id',
        'started_at',
        'completed_at',
        'watched_seconds',
        'last_position_sec',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AvaEnrollment::class, 'enrollment_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(AvaLesson::class, 'lesson_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
