<?php

namespace App\Livewire\Lojista\Ava;

use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaLesson;
use App\Models\Ava\AvaLessonMaterial;
use App\Models\Ava\AvaModule;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class CursoBuilder extends Component
{
    use WithFileUploads;
    public AvaCourse $course;

    // ── Configurações do curso ────────────────────────────────────────────────
    public string $level            = 'iniciante';
    public string $estimated_hours  = '';
    public string $access_duration_days = '';
    public bool   $is_drip          = false;
    public bool   $certificate_enabled = true;
    public string $intro_video_url  = '';
    public string $requirements     = '';
    public string $what_youll_learn = '';
    public bool   $is_published     = false;

    // ── Estado de edição de módulo ────────────────────────────────────────────
    public ?int    $editingModuleId   = null;
    public string  $editingModuleTitle = '';
    public string  $editingModuleDescription = '';
    public bool    $showNewModuleForm  = false;
    public string  $newModuleTitle    = '';

    // ── Estado de edição de aula ──────────────────────────────────────────────
    public ?int    $editingLessonId   = null;
    public ?int    $editingLessonModuleId = null;
    public string  $editingLessonTitle = '';
    public string  $editingLessonDescription = '';
    public string  $editingLessonContentType = 'video';
    public string  $editingLessonVideoUrl = '';
    public string  $editingLessonTextContent = '';
    public bool    $editingLessonIsPreview = false;
    public bool    $showNewLessonFormForModule = false;
    public ?int    $newLessonModuleId = null;
    public string  $newLessonTitle    = '';
    public string  $newLessonContentType = 'video';
    public string  $newLessonVideoUrl = '';

    // ── Upload de material ────────────────────────────────────────────────────
    public ?int   $uploadingMaterialForLesson = null;
    public string $materialTitle              = '';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $materialFile = null;

    // ── Aba ativa ─────────────────────────────────────────────────────────────
    public string $activeTab = 'configuracoes';

    public function mount(AvaCourse $course): void
    {
        // Garante que o lojista só acessa seu próprio curso
        $expositor = auth()->user()->expositor;
        if ($course->product->expositor_id !== $expositor->id) {
            abort(403);
        }

        $this->course = $course;
        $this->loadCourseSettings();
    }

    private function loadCourseSettings(): void
    {
        $this->level               = $this->course->level ?? 'iniciante';
        $this->estimated_hours     = $this->course->estimated_hours ? (string) $this->course->estimated_hours : '';
        $this->access_duration_days = $this->course->access_duration_days ? (string) $this->course->access_duration_days : '';
        $this->is_drip             = (bool) $this->course->is_drip;
        $this->certificate_enabled = (bool) $this->course->certificate_enabled;
        $this->intro_video_url     = $this->course->intro_video_url ?? '';
        $this->requirements        = $this->course->requirements ?? '';
        $this->what_youll_learn    = $this->course->what_youll_learn ?? '';
        $this->is_published        = $this->course->isPublished();
    }

    // ── Configurações ─────────────────────────────────────────────────────────

    public function saveSettings(): void
    {
        $this->validate([
            'level'               => 'required|in:iniciante,intermediario,avancado',
            'estimated_hours'     => 'nullable|numeric|min:0.1|max:999',
            'access_duration_days' => 'nullable|integer|min:1|max:3650',
            'intro_video_url'     => 'nullable|url|max:500',
            'requirements'        => 'nullable|string|max:3000',
            'what_youll_learn'    => 'nullable|string|max:3000',
        ]);

        $this->course->update([
            'level'                => $this->level,
            'estimated_hours'      => $this->estimated_hours !== '' ? $this->estimated_hours : null,
            'access_duration_days' => $this->access_duration_days !== '' ? $this->access_duration_days : null,
            'is_drip'              => $this->is_drip,
            'certificate_enabled'  => $this->certificate_enabled,
            'intro_video_url'      => $this->intro_video_url ?: null,
            'requirements'         => $this->requirements ?: null,
            'what_youll_learn'     => $this->what_youll_learn ?: null,
        ]);

        session()->flash('success', 'Configurações salvas.');
    }

    public function togglePublish(): void
    {
        if ($this->course->isPublished()) {
            $this->course->update(['published_at' => null]);
        } else {
            $this->course->update(['published_at' => now()]);
        }

        $this->is_published = $this->course->fresh()->isPublished();
        session()->flash('success', $this->is_published ? 'Curso publicado!' : 'Curso voltou para rascunho.');
    }

    // ── Módulos ───────────────────────────────────────────────────────────────

    public function openNewModuleForm(): void
    {
        $this->showNewModuleForm = true;
        $this->newModuleTitle    = '';
    }

    public function cancelNewModule(): void
    {
        $this->showNewModuleForm = false;
        $this->newModuleTitle    = '';
    }

    public function addModule(): void
    {
        $this->validate(['newModuleTitle' => 'required|string|max:255']);

        $maxOrder = AvaModule::where('course_id', $this->course->id)->max('sort_order') ?? -1;

        AvaModule::create([
            'course_id'  => $this->course->id,
            'title'      => $this->newModuleTitle,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->showNewModuleForm = false;
        $this->newModuleTitle    = '';
    }

    public function startEditModule(int $moduleId): void
    {
        $module = AvaModule::where('course_id', $this->course->id)->findOrFail($moduleId);

        $this->editingModuleId          = $moduleId;
        $this->editingModuleTitle       = $module->title;
        $this->editingModuleDescription = $module->description ?? '';
    }

    public function saveModule(): void
    {
        $this->validate(['editingModuleTitle' => 'required|string|max:255']);

        AvaModule::where('course_id', $this->course->id)
            ->findOrFail($this->editingModuleId)
            ->update([
                'title'       => $this->editingModuleTitle,
                'description' => $this->editingModuleDescription ?: null,
            ]);

        $this->editingModuleId = null;
    }

    public function cancelEditModule(): void
    {
        $this->editingModuleId = null;
    }

    public function deleteModule(int $moduleId): void
    {
        AvaModule::where('course_id', $this->course->id)->findOrFail($moduleId)->delete();
    }

    public function moveModuleUp(int $moduleId): void
    {
        $modules = AvaModule::where('course_id', $this->course->id)->orderBy('sort_order')->get();
        $idx = $modules->search(fn ($m) => $m->id === $moduleId);

        if ($idx > 0) {
            $this->swapSortOrder($modules[$idx], $modules[$idx - 1]);
        }
    }

    public function moveModuleDown(int $moduleId): void
    {
        $modules = AvaModule::where('course_id', $this->course->id)->orderBy('sort_order')->get();
        $idx = $modules->search(fn ($m) => $m->id === $moduleId);

        if ($idx !== false && $idx < $modules->count() - 1) {
            $this->swapSortOrder($modules[$idx], $modules[$idx + 1]);
        }
    }

    private function swapSortOrder(AvaModule $a, AvaModule $b): void
    {
        [$a->sort_order, $b->sort_order] = [$b->sort_order, $a->sort_order];
        $a->save();
        $b->save();
    }

    // ── Aulas ─────────────────────────────────────────────────────────────────

    public function openNewLessonForm(int $moduleId): void
    {
        $this->newLessonModuleId         = $moduleId;
        $this->showNewLessonFormForModule = true;
        $this->newLessonTitle            = '';
        $this->newLessonContentType      = 'video';
        $this->newLessonVideoUrl         = '';
    }

    public function cancelNewLesson(): void
    {
        $this->showNewLessonFormForModule = false;
        $this->newLessonModuleId          = null;
    }

    public function addLesson(): void
    {
        $this->validate([
            'newLessonTitle'       => 'required|string|max:255',
            'newLessonContentType' => 'required|in:video,texto,pdf,audio',
            'newLessonVideoUrl'    => 'nullable|url|max:500',
        ]);

        AvaModule::where('course_id', $this->course->id)->findOrFail($this->newLessonModuleId);

        $maxOrder = AvaLesson::where('module_id', $this->newLessonModuleId)->max('sort_order') ?? -1;

        AvaLesson::create([
            'module_id'    => $this->newLessonModuleId,
            'title'        => $this->newLessonTitle,
            'content_type' => $this->newLessonContentType,
            'video_url'    => $this->newLessonContentType === 'video' ? ($this->newLessonVideoUrl ?: null) : null,
            'video_provider' => $this->detectVideoProvider($this->newLessonVideoUrl),
            'sort_order'   => $maxOrder + 1,
        ]);

        $this->showNewLessonFormForModule = false;
        $this->newLessonModuleId = null;
    }

    public function startEditLesson(int $lessonId): void
    {
        $lesson = AvaLesson::whereHas('module', fn ($q) => $q->where('course_id', $this->course->id))
            ->findOrFail($lessonId);

        $this->editingLessonId          = $lessonId;
        $this->editingLessonModuleId    = $lesson->module_id;
        $this->editingLessonTitle       = $lesson->title;
        $this->editingLessonDescription = $lesson->description ?? '';
        $this->editingLessonContentType = $lesson->content_type;
        $this->editingLessonVideoUrl    = $lesson->video_url ?? '';
        $this->editingLessonTextContent = $lesson->text_content ?? '';
        $this->editingLessonIsPreview   = (bool) $lesson->is_preview;
    }

    public function saveLesson(): void
    {
        $this->validate([
            'editingLessonTitle'       => 'required|string|max:255',
            'editingLessonContentType' => 'required|in:video,texto,pdf,audio',
            'editingLessonVideoUrl'    => 'nullable|url|max:500',
            'editingLessonTextContent' => 'nullable|string',
        ]);

        AvaLesson::whereHas('module', fn ($q) => $q->where('course_id', $this->course->id))
            ->findOrFail($this->editingLessonId)
            ->update([
                'title'          => $this->editingLessonTitle,
                'description'    => $this->editingLessonDescription ?: null,
                'content_type'   => $this->editingLessonContentType,
                'video_url'      => $this->editingLessonContentType === 'video' ? ($this->editingLessonVideoUrl ?: null) : null,
                'video_provider' => $this->detectVideoProvider($this->editingLessonVideoUrl),
                'text_content'   => $this->editingLessonContentType === 'texto' ? ($this->editingLessonTextContent ?: null) : null,
                'is_preview'     => $this->editingLessonIsPreview,
            ]);

        $this->editingLessonId = null;
    }

    public function cancelEditLesson(): void
    {
        $this->editingLessonId = null;
    }

    public function deleteLesson(int $lessonId): void
    {
        AvaLesson::whereHas('module', fn ($q) => $q->where('course_id', $this->course->id))
            ->findOrFail($lessonId)
            ->delete();
    }

    public function moveLessonUp(int $lessonId): void
    {
        $lesson  = AvaLesson::findOrFail($lessonId);
        $lessons = AvaLesson::where('module_id', $lesson->module_id)->orderBy('sort_order')->get();
        $idx     = $lessons->search(fn ($l) => $l->id === $lessonId);

        if ($idx > 0) {
            $this->swapLessonSortOrder($lessons[$idx], $lessons[$idx - 1]);
        }
    }

    public function moveLessonDown(int $lessonId): void
    {
        $lesson  = AvaLesson::findOrFail($lessonId);
        $lessons = AvaLesson::where('module_id', $lesson->module_id)->orderBy('sort_order')->get();
        $idx     = $lessons->search(fn ($l) => $l->id === $lessonId);

        if ($idx !== false && $idx < $lessons->count() - 1) {
            $this->swapLessonSortOrder($lessons[$idx], $lessons[$idx + 1]);
        }
    }

    private function swapLessonSortOrder(AvaLesson $a, AvaLesson $b): void
    {
        [$a->sort_order, $b->sort_order] = [$b->sort_order, $a->sort_order];
        $a->save();
        $b->save();
    }

    // ── Materiais complementares ──────────────────────────────────────────────

    public function openMaterialUpload(int $lessonId): void
    {
        $this->uploadingMaterialForLesson = $lessonId;
        $this->materialTitle              = '';
        $this->materialFile               = null;
    }

    public function cancelMaterialUpload(): void
    {
        $this->uploadingMaterialForLesson = null;
        $this->materialFile               = null;
    }

    public function uploadMaterial(): void
    {
        $this->validate([
            'materialTitle' => 'required|string|max:255',
            'materialFile'  => 'required|file|max:20480|mimes:pdf,pptx,docx,xlsx,zip,mp3,mp4',
        ]);

        $lesson = AvaLesson::whereHas('module', fn ($q) => $q->where('course_id', $this->course->id))
            ->findOrFail($this->uploadingMaterialForLesson);

        $ext      = $this->materialFile->getClientOriginalExtension();
        $sizeKb   = (int) ceil($this->materialFile->getSize() / 1024);
        $maxOrder = AvaLessonMaterial::where('lesson_id', $lesson->id)->max('sort_order') ?? -1;

        $path = $this->materialFile->storeAs(
            'ava/materials/' . $lesson->id,
            uniqid() . '.' . $ext
        );

        AvaLessonMaterial::create([
            'lesson_id'   => $lesson->id,
            'title'       => $this->materialTitle,
            'file_path'   => $path,
            'file_type'   => $ext,
            'file_size_kb' => $sizeKb,
            'sort_order'  => $maxOrder + 1,
        ]);

        $this->uploadingMaterialForLesson = null;
        $this->materialFile               = null;
        $this->materialTitle              = '';
        session()->flash('success', 'Material enviado com sucesso.');
    }

    public function deleteMaterial(int $materialId): void
    {
        $material = AvaLessonMaterial::whereHas(
            'lesson.module',
            fn ($q) => $q->where('course_id', $this->course->id)
        )->findOrFail($materialId);

        \Illuminate\Support\Facades\Storage::delete($material->file_path);
        $material->delete();
    }

    private function detectVideoProvider(string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (str_contains($url, 'youtu')) {
            return 'youtube';
        }
        if (str_contains($url, 'vimeo')) {
            return 'vimeo';
        }

        return null;
    }

    public function render(): View
    {
        $modules = AvaModule::where('course_id', $this->course->id)
            ->with([
                'lessons' => fn ($q) => $q->orderBy('sort_order'),
                'lessons.materials' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.lojista.ava.curso-builder', [
            'modules' => $modules,
        ])->layout('lojista.layouts.app', ['title' => 'Construtor: ' . $this->course->product->name]);
    }
}
