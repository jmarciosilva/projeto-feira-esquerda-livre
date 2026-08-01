<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Ava\AvaEnrollmentResource;
use App\Models\Ava\AvaEnrollment;
use App\Models\Ava\AvaLesson;
use App\Models\Ava\AvaLessonProgress;
use App\Services\AvaCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AprendizadoController extends Controller
{
    /** GET /api/v1/aprendizado */
    public function index(Request $request): AnonymousResourceCollection
    {
        $enrollments = AvaEnrollment::where('user_id', $request->user()->id)
            ->with('course.product')
            ->orderByDesc('last_accessed_at')
            ->get();

        return AvaEnrollmentResource::collection($enrollments);
    }

    /** GET /api/v1/aprendizado/{enrollment} */
    public function show(Request $request, AvaEnrollment $enrollment): JsonResponse
    {
        $this->authorizeEnrollment($request, $enrollment);
        abort_unless($enrollment->isAccessible(), 403, 'Seu acesso a este curso não está ativo.');

        $course = $enrollment->course->load([
            'product',
            'modules' => fn ($q) => $q->orderBy('sort_order'),
            'modules.lessons' => fn ($q) => $q->orderBy('sort_order'),
            'modules.lessons.materials' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $completedLessonIds = $enrollment->progress()->whereNotNull('completed_at')->pluck('lesson_id')->all();

        $enrollment->update(['last_accessed_at' => now()]);

        return response()->json([
            'enrollment' => new AvaEnrollmentResource($enrollment),
            'modules' => $course->modules->map(fn ($module) => [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'lessons' => $module->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'content_type' => $lesson->content_type,
                    'embed_url' => $lesson->embedUrl(),
                    'text_content' => $lesson->text_content,
                    'duration_label' => $lesson->durationLabel(),
                    'is_completed' => in_array($lesson->id, $completedLessonIds, true),
                    'materials' => $lesson->materials->map(fn ($material) => [
                        'id' => $material->id,
                        'title' => $material->title,
                        'file_type' => $material->file_type,
                        'file_size_label' => $material->fileSizeLabel(),
                        'download_url' => $material->temporaryUrl(),
                    ]),
                ]),
            ]),
        ]);
    }

    /** POST /api/v1/aprendizado/{enrollment}/aulas/{lesson}/concluir */
    public function concluirAula(Request $request, AvaEnrollment $enrollment, AvaLesson $lesson): JsonResponse
    {
        $this->authorizeEnrollment($request, $enrollment);
        abort_unless($enrollment->isAccessible(), 403, 'Seu acesso a este curso não está ativo.');

        $lessonBelongsToCourse = AvaLesson::whereHas('module', fn ($q) => $q->where('course_id', $enrollment->course_id))
            ->whereKey($lesson->id)
            ->exists();

        abort_unless($lessonBelongsToCourse, 404);

        AvaLessonProgress::updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
            ['started_at' => now(), 'completed_at' => now()]
        );

        $enrollment->updateCompletionPercent();

        return response()->json(['enrollment' => new AvaEnrollmentResource($enrollment->refresh())]);
    }

    /** GET /api/v1/aprendizado/{enrollment}/certificado */
    public function certificado(Request $request, AvaEnrollment $enrollment, AvaCertificateService $service): StreamedResponse
    {
        $this->authorizeEnrollment($request, $enrollment);

        abort_unless($enrollment->isCompleted(), 403, 'Certificado disponível apenas após concluir 100% do curso.');

        if (! $enrollment->certificate_path || ! Storage::exists($enrollment->certificate_path)) {
            $service->generate($enrollment);
            $enrollment->refresh();
        }

        return Storage::download(
            $enrollment->certificate_path,
            'Certificado - '.$enrollment->course->product->name.'.pdf'
        );
    }

    private function authorizeEnrollment(Request $request, AvaEnrollment $enrollment): void
    {
        abort_unless($enrollment->user_id === $request->user()->id, 403);
    }
}
