<?php

namespace Tests\Feature;

use App\Enums\AvaEnrollmentStatus;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Ava\AvaLesson;
use App\Models\Ava\AvaModule;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AvaBuilderTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeExpositor(): Expositor
    {
        self::$counter++;
        return Expositor::create([
            'name'  => 'Loja Builder ' . self::$counter,
            'slug'  => 'loja-builder-' . self::$counter,
        ]);
    }

    private function makeLojistaWithExpositor(): array
    {
        $lojista  = User::factory()->create();
        $lojista->assignRole('lojista');
        $expositor = $this->makeExpositor();
        $expositor->user()->associate($lojista)->save();

        return [$lojista, $expositor];
    }

    private function makeDigitalProductWithCourse(Expositor $expositor, bool $published = false): array
    {
        $product = Product::create([
            'expositor_id' => $expositor->id,
            'name'         => 'Curso Teste ' . uniqid(),
            'slug'         => 'curso-teste-' . uniqid(),
            'price'        => 99.90,
            'is_active'    => true,
            'is_digital'   => true,
        ]);

        $course = AvaCourse::create([
            'product_id'   => $product->id,
            'published_at' => $published ? now() : null,
        ]);

        return [$product, $course];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CursoIndex
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lojista_sees_curso_index(): void
    {
        [$lojista, $expositor] = $this->makeLojistaWithExpositor();
        [$product, $course]    = $this->makeDigitalProductWithCourse($expositor);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoIndex::class)
            ->assertSee($product->name);
    }

    public function test_lojista_does_not_see_other_lojista_cursos(): void
    {
        [$lojista1, $expositor1] = $this->makeLojistaWithExpositor();
        [$lojista2, $expositor2] = $this->makeLojistaWithExpositor();

        [$product1] = $this->makeDigitalProductWithCourse($expositor1);
        [$product2] = $this->makeDigitalProductWithCourse($expositor2);

        Livewire::actingAs($lojista1)
            ->test(\App\Livewire\Lojista\Ava\CursoIndex::class)
            ->assertSee($product1->name)
            ->assertDontSee($product2->name);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CursoBuilder — configurações
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lojista_can_save_course_settings(): void
    {
        [$lojista, $expositor] = $this->makeLojistaWithExpositor();
        [, $course]            = $this->makeDigitalProductWithCourse($expositor);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoBuilder::class, ['course' => $course])
            ->set('level', 'avancado')
            ->set('estimated_hours', '12')
            ->set('certificate_enabled', true)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertEquals('avancado', $course->fresh()->level);
        $this->assertEquals(12, $course->fresh()->estimated_hours);
    }

    public function test_lojista_can_publish_and_unpublish_course(): void
    {
        [$lojista, $expositor] = $this->makeLojistaWithExpositor();
        [, $course]            = $this->makeDigitalProductWithCourse($expositor, published: false);

        $component = Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoBuilder::class, ['course' => $course]);

        $component->call('togglePublish');
        $this->assertNotNull($course->fresh()->published_at);

        $component->call('togglePublish');
        $this->assertNull($course->fresh()->published_at);
    }

    public function test_other_lojista_cannot_access_course_builder(): void
    {
        [$lojista1, $expositor1] = $this->makeLojistaWithExpositor();
        [$lojista2]              = $this->makeLojistaWithExpositor();
        [, $course]              = $this->makeDigitalProductWithCourse($expositor1);

        $this->actingAs($lojista2)
            ->get(route('lojista.ava.builder', $course))
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CursoBuilder — módulos
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lojista_can_add_module(): void
    {
        [$lojista, $expositor] = $this->makeLojistaWithExpositor();
        [, $course]            = $this->makeDigitalProductWithCourse($expositor);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoBuilder::class, ['course' => $course])
            ->call('openNewModuleForm')
            ->set('newModuleTitle', 'Módulo 1')
            ->call('addModule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ava_modules', [
            'course_id' => $course->id,
            'title'     => 'Módulo 1',
        ]);
    }

    public function test_lojista_can_delete_module(): void
    {
        [$lojista, $expositor] = $this->makeLojistaWithExpositor();
        [, $course]            = $this->makeDigitalProductWithCourse($expositor);

        $module = AvaModule::create([
            'course_id'  => $course->id,
            'title'      => 'Para excluir',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoBuilder::class, ['course' => $course])
            ->call('deleteModule', $module->id);

        $this->assertDatabaseMissing('ava_modules', ['id' => $module->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CursoBuilder — aulas
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lojista_can_add_lesson_to_module(): void
    {
        [$lojista, $expositor] = $this->makeLojistaWithExpositor();
        [, $course]            = $this->makeDigitalProductWithCourse($expositor);

        $module = AvaModule::create([
            'course_id'  => $course->id,
            'title'      => 'Módulo A',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoBuilder::class, ['course' => $course])
            ->call('openNewLessonForm', $module->id)
            ->set('newLessonTitle', 'Aula 1 — Introdução')
            ->set('newLessonContentType', 'video')
            ->set('newLessonVideoUrl', 'https://youtu.be/dQw4w9WgXcQ')
            ->call('addLesson')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ava_lessons', [
            'module_id' => $module->id,
            'title'     => 'Aula 1 — Introdução',
        ]);
    }

    public function test_lojista_can_edit_lesson(): void
    {
        [$lojista, $expositor] = $this->makeLojistaWithExpositor();
        [, $course]            = $this->makeDigitalProductWithCourse($expositor);

        $module = AvaModule::create(['course_id' => $course->id, 'title' => 'M', 'sort_order' => 0]);
        $lesson = AvaLesson::create([
            'module_id'    => $module->id,
            'title'        => 'Aula Antiga',
            'content_type' => 'video',
            'sort_order'   => 0,
        ]);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoBuilder::class, ['course' => $course])
            ->call('startEditLesson', $lesson->id)
            ->set('editingLessonTitle', 'Aula Nova')
            ->call('saveLesson')
            ->assertHasNoErrors();

        $this->assertEquals('Aula Nova', $lesson->fresh()->title);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CursoPlayer
    // ─────────────────────────────────────────────────────────────────────────

    public function test_student_can_view_player(): void
    {
        $student   = User::factory()->create();
        [, $expositor] = $this->makeLojistaWithExpositor();
        [, $course] = $this->makeDigitalProductWithCourse($expositor, published: true);

        $module = AvaModule::create(['course_id' => $course->id, 'title' => 'M1', 'sort_order' => 0]);
        AvaLesson::create(['module_id' => $module->id, 'title' => 'L1', 'content_type' => 'video', 'sort_order' => 0]);

        $enrollment = AvaEnrollment::create([
            'user_id'            => $student->id,
            'course_id'          => $course->id,
            'status'             => AvaEnrollmentStatus::Active,
            'enrolled_at'        => now(),
            'completion_percent' => 0,
        ]);

        Livewire::actingAs($student)
            ->test(\App\Livewire\Cliente\Ava\CursoPlayer::class, ['enrollment' => $enrollment])
            ->assertSee('L1');
    }

    public function test_student_can_mark_lesson_complete(): void
    {
        $student    = User::factory()->create();
        [, $expositor] = $this->makeLojistaWithExpositor();
        [, $course] = $this->makeDigitalProductWithCourse($expositor, published: true);

        $module = AvaModule::create(['course_id' => $course->id, 'title' => 'M', 'sort_order' => 0]);
        $lesson = AvaLesson::create(['module_id' => $module->id, 'title' => 'L', 'content_type' => 'video', 'sort_order' => 0]);

        $enrollment = AvaEnrollment::create([
            'user_id'            => $student->id,
            'course_id'          => $course->id,
            'status'             => AvaEnrollmentStatus::Active,
            'enrolled_at'        => now(),
            'completion_percent' => 0,
        ]);

        Livewire::actingAs($student)
            ->test(\App\Livewire\Cliente\Ava\CursoPlayer::class, ['enrollment' => $enrollment])
            ->set('activeLessonId', $lesson->id)
            ->call('markComplete');

        $this->assertDatabaseHas('ava_lesson_progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id'     => $lesson->id,
        ]);

        $this->assertNotNull(
            \App\Models\Ava\AvaLessonProgress::where('enrollment_id', $enrollment->id)
                ->where('lesson_id', $lesson->id)
                ->value('completed_at')
        );
        $this->assertEquals(100.0, $enrollment->fresh()->completion_percent);
    }

    public function test_other_student_cannot_access_enrollment(): void
    {
        $student1 = User::factory()->create();
        $student2 = User::factory()->create();
        [, $expositor] = $this->makeLojistaWithExpositor();
        [, $course]    = $this->makeDigitalProductWithCourse($expositor, published: true);

        $enrollment = AvaEnrollment::create([
            'user_id'            => $student1->id,
            'course_id'          => $course->id,
            'status'             => AvaEnrollmentStatus::Active,
            'enrolled_at'        => now(),
            'completion_percent' => 0,
        ]);

        $this->actingAs($student2)
            ->get(route('cliente.ava.player', $enrollment))
            ->assertForbidden();
    }
}
