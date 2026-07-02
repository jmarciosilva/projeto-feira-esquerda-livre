<?php

namespace App\Models\Ava;

use App\Enums\AvaEnrollmentStatus;
use App\Models\OrderSplit;
use App\Models\User;
use App\Services\AvaCertificateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvaEnrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'order_split_id',
        'status',
        'enrolled_at',
        'expires_at',
        'completed_at',
        'completion_percent',
        'certificate_path',
        'last_accessed_at',
    ];

    protected $casts = [
        'status'             => AvaEnrollmentStatus::class,
        'enrolled_at'        => 'datetime',
        'expires_at'         => 'datetime',
        'completed_at'       => 'datetime',
        'last_accessed_at'   => 'datetime',
        'completion_percent' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AvaCourse::class, 'course_id');
    }

    public function orderSplit(): BelongsTo
    {
        return $this->belongsTo(OrderSplit::class, 'order_split_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(AvaLessonProgress::class, 'enrollment_id');
    }

    public function isAccessible(): bool
    {
        if (! $this->status->isAccessible()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function updateCompletionPercent(): void
    {
        $totalLessons = $this->course->totalLessons();

        if ($totalLessons === 0) {
            return;
        }

        $wasCompleted = $this->isCompleted();

        $completedCount = $this->progress()->whereNotNull('completed_at')->count();
        $percent = round(($completedCount / $totalLessons) * 100, 2);

        $this->update([
            'completion_percent' => $percent,
            'completed_at'       => $percent >= 100 ? now() : null,
            'last_accessed_at'   => now(),
        ]);

        // Gera certificado na primeira vez que atinge 100%
        if (! $wasCompleted && $percent >= 100) {
            try {
                app(AvaCertificateService::class)->generate($this);
            } catch (\Throwable) {
                // Não impede o progresso se a geração falhar
            }
        }
    }
}
