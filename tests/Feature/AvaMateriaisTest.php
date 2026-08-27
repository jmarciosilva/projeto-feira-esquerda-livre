<?php

namespace Tests\Feature;

use App\Enums\AvaEnrollmentStatus;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Ava\AvaLesson;
use App\Models\Ava\AvaLessonMaterial;
use App\Models\Ava\AvaModule;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use App\Services\AvaCertificateService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AvaMateriaisTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake();
    }

    private function makeExpositor(): Expositor
    {
        self::$counter++;
        return Expositor::create([
            'name' => 'Loja Mat ' . self::$counter,
            'slug' => 'loja-mat-' . self::$counter,
        ]);
    }

    private function makeLojistaWithExpositor(): array
    {
        $lojista   = User::factory()->create();
        $lojista->assignRole('lojista');
        $expositor = $this->makeExpositor();
        $expositor->user()->associate($lojista)->save();
        return [$lojista, $expositor];
    }

    private function makePublishedCourse(Expositor $expositor): array
    {
        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'name'         => 'Curso Mat ' . uniqid(),
            'slug'         => 'curso-mat-' . uniqid(),
            'price'        => 99.00,
            'is_active'    => true,
            'is_digital'   => true,
        ]);
        $course = AvaCourse::create([
            'product_id'   => $product->id,
            'published_at' => now(),
        ]);
        $module = AvaModule::create(['course_id' => $course->id, 'title' => 'M', 'sort_order' => 0]);
        $lesson = AvaLesson::create(['module_id' => $module->id, 'title' => 'L', 'content_type' => 'video', 'sort_order' => 0]);

        return [$product, $course, $module, $lesson];
    }

    private function makeActiveEnrollment(User $user, AvaCourse $course): AvaEnrollment
    {
        return AvaEnrollment::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'status'             => AvaEnrollmentStatus::Active,
            'enrolled_at'        => now(),
            'completion_percent' => 0,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Upload de material pelo lojista
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lojista_can_upload_material(): void
    {
        [$lojista, $expositor]           = $this->makeLojistaWithExpositor();
        [, $course, , $lesson]           = $this->makePublishedCourse($expositor);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoBuilder::class, ['course' => $course])
            ->call('openMaterialUpload', $lesson->id)
            ->set('materialTitle', 'Slides da Aula')
            ->set('materialFile', UploadedFile::fake()->create('slides.pdf', 512, 'application/pdf'))
            ->call('uploadMaterial')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ava_lesson_materials', [
            'lesson_id' => $lesson->id,
            'title'     => 'Slides da Aula',
        ]);
    }

    public function test_lojista_can_delete_material(): void
    {
        [$lojista, $expositor] = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);

        Storage::put('ava/materials/' . $lesson->id . '/test.pdf', 'fake');

        $material = AvaLessonMaterial::create([
            'lesson_id'   => $lesson->id,
            'title'       => 'Para excluir',
            'file_path'   => 'ava/materials/' . $lesson->id . '/test.pdf',
            'file_type'   => 'pdf',
            'sort_order'  => 0,
        ]);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Ava\CursoBuilder::class, ['course' => $course])
            ->call('deleteMaterial', $material->id);

        $this->assertDatabaseMissing('ava_lesson_materials', ['id' => $material->id]);
        Storage::assertMissing('ava/materials/' . $lesson->id . '/test.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Download de material pelo aluno
    // ─────────────────────────────────────────────────────────────────────────

    public function test_enrolled_student_can_download_material(): void
    {
        [, $expositor]         = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);
        $student               = User::factory()->create();
        $this->makeActiveEnrollment($student, $course);

        Storage::put('ava/materials/test.pdf', 'conteudo do pdf');

        $material = AvaLessonMaterial::create([
            'lesson_id'  => $lesson->id,
            'title'      => 'Slides',
            'file_path'  => 'ava/materials/test.pdf',
            'file_type'  => 'pdf',
            'sort_order' => 0,
        ]);

        $url = $material->temporaryUrl();

        $this->actingAs($student)->get($url)->assertOk();
    }

    public function test_non_enrolled_student_cannot_download_material(): void
    {
        [, $expositor]         = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);
        $other                 = User::factory()->create();

        Storage::put('ava/materials/test.pdf', 'conteudo');

        $material = AvaLessonMaterial::create([
            'lesson_id'  => $lesson->id,
            'title'      => 'Slides',
            'file_path'  => 'ava/materials/test.pdf',
            'file_type'  => 'pdf',
            'sort_order' => 0,
        ]);

        $url = $material->temporaryUrl();

        $this->actingAs($other)->get($url)->assertForbidden();
    }

    public function test_material_url_with_invalid_signature_is_rejected(): void
    {
        [, $expositor]         = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);
        $student               = User::factory()->create();
        $this->makeActiveEnrollment($student, $course);

        Storage::put('ava/materials/test.pdf', 'conteudo');

        $material = AvaLessonMaterial::create([
            'lesson_id'  => $lesson->id,
            'title'      => 'Slides',
            'file_path'  => 'ava/materials/test.pdf',
            'file_type'  => 'pdf',
            'sort_order' => 0,
        ]);

        // URL sem assinatura
        $url = route('ava.materiais.download', ['material' => $material->id]);

        $this->actingAs($student)->get($url)->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Certificado
    // ─────────────────────────────────────────────────────────────────────────

    public function test_certificate_generated_when_course_completed(): void
    {
        Mail::fake();

        [, $expositor]         = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);
        $student               = User::factory()->create();
        $enrollment            = $this->makeActiveEnrollment($student, $course);

        // Marca a única aula como concluída
        \App\Models\Ava\AvaLessonProgress::create([
            'enrollment_id' => $enrollment->id,
            'lesson_id'     => $lesson->id,
            'started_at'    => now(),
            'completed_at'  => now(),
        ]);

        $enrollment->updateCompletionPercent();
        $enrollment->refresh();

        $this->assertEquals(100.0, $enrollment->completion_percent);
        $this->assertNotNull($enrollment->certificate_path);
        Storage::assertExists($enrollment->certificate_path);
    }

    public function test_certificate_email_sent_on_completion(): void
    {
        Mail::fake();

        [, $expositor]         = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);
        $student               = User::factory()->create();
        $enrollment            = $this->makeActiveEnrollment($student, $course);

        \App\Models\Ava\AvaLessonProgress::create([
            'enrollment_id' => $enrollment->id,
            'lesson_id'     => $lesson->id,
            'started_at'    => now(),
            'completed_at'  => now(),
        ]);

        $enrollment->updateCompletionPercent();

        Mail::assertSent(\App\Mail\AvaCertificateMail::class, function ($mail) use ($student) {
            return $mail->hasTo($student->email);
        });
    }

    public function test_certificate_not_regenerated_if_already_exists(): void
    {
        Mail::fake();

        [, $expositor]         = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);
        $student               = User::factory()->create();
        $enrollment            = $this->makeActiveEnrollment($student, $course);

        \App\Models\Ava\AvaLessonProgress::create([
            'enrollment_id' => $enrollment->id,
            'lesson_id'     => $lesson->id,
            'started_at'    => now(),
            'completed_at'  => now(),
        ]);

        $enrollment->updateCompletionPercent();
        $firstPath = $enrollment->fresh()->certificate_path;

        // Chama de novo — não deve regerar nem reenviar email
        $enrollment->refresh();
        $enrollment->updateCompletionPercent();

        Mail::assertSentTimes(\App\Mail\AvaCertificateMail::class, 1);
        $this->assertEquals($firstPath, $enrollment->fresh()->certificate_path);
    }

    public function test_student_can_download_certificate(): void
    {
        Mail::fake();

        [, $expositor]         = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);
        $student               = User::factory()->create();
        $enrollment            = $this->makeActiveEnrollment($student, $course);

        \App\Models\Ava\AvaLessonProgress::create([
            'enrollment_id' => $enrollment->id,
            'lesson_id'     => $lesson->id,
            'started_at'    => now(),
            'completed_at'  => now(),
        ]);

        $enrollment->updateCompletionPercent();
        $enrollment->refresh();

        $this->actingAs($student)
            ->get(route('cliente.ava.certificado.download', $enrollment))
            ->assertOk();
    }

    public function test_other_student_cannot_download_certificate(): void
    {
        Mail::fake();

        [, $expositor]         = $this->makeLojistaWithExpositor();
        [, $course, , $lesson] = $this->makePublishedCourse($expositor);
        $student1              = User::factory()->create();
        $student2              = User::factory()->create();
        $enrollment            = $this->makeActiveEnrollment($student1, $course);

        \App\Models\Ava\AvaLessonProgress::create([
            'enrollment_id' => $enrollment->id,
            'lesson_id'     => $lesson->id,
            'started_at'    => now(),
            'completed_at'  => now(),
        ]);

        $enrollment->updateCompletionPercent();

        $this->actingAs($student2)
            ->get(route('cliente.ava.certificado.download', $enrollment))
            ->assertForbidden();
    }
}
