<?php

namespace App\Livewire\Cliente\Ava;

use App\Models\Ava\AvaEnrollment;
use App\Models\Ava\AvaLesson;
use App\Models\Ava\AvaLessonProgress;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CursoPlayer extends Component
{
    public AvaEnrollment $enrollment;
    public ?int $activeLessonId = null;

    public function mount(AvaEnrollment $enrollment): void
    {
        if ($enrollment->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $enrollment->isAccessible()) {
            abort(403, 'Seu acesso a este curso não está ativo.');
        }

        $this->enrollment = $enrollment;

        $firstLesson = $enrollment->course->modules->first()?->lessons->first();
        $this->activeLessonId = $firstLesson?->id;

        $enrollment->update(['last_accessed_at' => now()]);
    }

    public function selectLesson(int $lessonId): void
    {
        $lesson = $this->getLessonWithAccess($lessonId);

        if ($lesson) {
            $this->activeLessonId = $lessonId;

            // Registra início se ainda não existe progresso
            AvaLessonProgress::firstOrCreate(
                [
                    'enrollment_id' => $this->enrollment->id,
                    'lesson_id'     => $lessonId,
                ],
                ['started_at' => now()]
            );
        }
    }

    public function markComplete(): void
    {
        if (! $this->activeLessonId) {
            return;
        }

        AvaLessonProgress::updateOrCreate(
            [
                'enrollment_id' => $this->enrollment->id,
                'lesson_id'     => $this->activeLessonId,
            ],
            [
                'started_at'   => now(),
                'completed_at' => now(),
            ]
        );

        $this->enrollment->updateCompletionPercent();
        $this->enrollment->refresh();

        $this->goToNextLesson();
    }

    private function goToNextLesson(): void
    {
        $allLessons = $this->enrollment->course->modules
            ->flatMap(fn ($module) => $module->lessons);

        $currentIdx = $allLessons->search(fn ($l) => $l->id === $this->activeLessonId);

        if ($currentIdx !== false && $currentIdx < $allLessons->count() - 1) {
            $this->activeLessonId = $allLessons[$currentIdx + 1]->id;
        }
    }

    private function getLessonWithAccess(int $lessonId): ?AvaLesson
    {
        return AvaLesson::whereHas('module.course.enrollments', function ($q) {
            $q->where('id', $this->enrollment->id);
        })->find($lessonId);
    }

    public function render(): View
    {
        $course = $this->enrollment->course->load([
            'modules'                   => fn ($q) => $q->orderBy('sort_order'),
            'modules.lessons'           => fn ($q) => $q->orderBy('sort_order'),
            'modules.lessons.materials' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $activeLesson = $this->activeLessonId
            ? AvaLesson::with('materials')->find($this->activeLessonId)
            : null;

        $completedLessonIds = $this->enrollment->progress()
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->toArray();

        return view('livewire.cliente.ava.curso-player', [
            'course'             => $course,
            'activeLesson'       => $activeLesson,
            'completedLessonIds' => $completedLessonIds,
        ])->layout('cliente.layouts.app', ['title' => $course->product->name]);
    }
}
