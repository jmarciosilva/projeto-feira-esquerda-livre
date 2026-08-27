<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AvaEnrollmentStatus;
use App\Enums\ItemType;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Ava\AvaLesson;
use App\Models\Ava\AvaModule;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AprendizadoApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeEnrollment(User $user): array
    {
        $expositor = Expositor::create(['name' => 'Tecnologia Solidária', 'slug' => 'tecnologia-solidaria']);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => ItemType::Servico,
            'name' => 'Curso Online de Informática Popular',
            'slug' => 'curso-online-informatica-popular',
            'price' => 0.01,
            'is_digital' => true,
            'is_active' => true,
        ]);

        $course = AvaCourse::create(['product_id' => $product->id, 'published_at' => now()->subMinute()]);
        $module = AvaModule::create(['course_id' => $course->id, 'title' => 'Módulo 1', 'sort_order' => 1]);
        $lesson = AvaLesson::create([
            'module_id' => $module->id,
            'title' => 'Aula 1',
            'content_type' => 'texto',
            'text_content' => 'Conteúdo da aula.',
            'sort_order' => 1,
        ]);

        $enrollment = AvaEnrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => AvaEnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        return compact('enrollment', 'lesson', 'course');
    }

    public function test_lists_own_enrollments(): void
    {
        $user = User::factory()->create();
        $this->makeEnrollment($user);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/aprendizado')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.course.title', 'Curso Online de Informática Popular');
    }

    public function test_cannot_view_another_users_enrollment(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        ['enrollment' => $enrollment] = $this->makeEnrollment($owner);

        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/aprendizado/{$enrollment->id}")->assertStatus(403);
    }

    public function test_shows_course_content_with_lessons(): void
    {
        $user = User::factory()->create();
        ['enrollment' => $enrollment] = $this->makeEnrollment($user);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/aprendizado/{$enrollment->id}")
            ->assertOk()
            ->assertJsonPath('modules.0.lessons.0.title', 'Aula 1')
            ->assertJsonPath('modules.0.lessons.0.is_completed', false);
    }

    public function test_can_mark_lesson_complete_and_certificate_becomes_available(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        ['enrollment' => $enrollment, 'lesson' => $lesson] = $this->makeEnrollment($user);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/aprendizado/{$enrollment->id}/aulas/{$lesson->id}/concluir");
        $response->assertOk()->assertJsonPath('enrollment.completion_percent', 100);

        $this->assertDatabaseHas('ava_lesson_progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
        ]);

        $this->get("/api/v1/aprendizado/{$enrollment->id}/certificado")->assertOk();
    }

    public function test_certificate_forbidden_before_course_completed(): void
    {
        $user = User::factory()->create();
        ['enrollment' => $enrollment] = $this->makeEnrollment($user);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/aprendizado/{$enrollment->id}/certificado")->assertStatus(403);
    }
}
