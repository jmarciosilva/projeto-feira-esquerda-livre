<?php

namespace App\Http\Resources\Api\V1\Ava;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Ava\AvaEnrollment */
class AvaEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'enrolled_at' => $this->enrolled_at,
            'expires_at' => $this->expires_at,
            'completed_at' => $this->completed_at,
            'completion_percent' => (float) $this->completion_percent,
            'is_accessible' => $this->isAccessible(),
            'has_certificate' => $this->courseHasCertificate() && $this->isCompleted(),
            'course' => [
                'id' => $this->course->id,
                'title' => $this->course->product?->name,
                'level' => $this->course->level,
                'level_label' => $this->course->levelLabel(),
                'estimated_hours' => $this->course->estimated_hours,
                'certificate_enabled' => (bool) $this->course->certificate_enabled,
                'total_lessons' => $this->course->totalLessons(),
            ],
        ];
    }

    private function courseHasCertificate(): bool
    {
        return (bool) $this->course->certificate_enabled;
    }
}
