<?php

namespace Tests\Feature\Api\V1;

use App\Models\Expositor;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedApiTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(bool $visible = true): FeedPost
    {
        $expositor = Expositor::create(['name' => 'Ateliê das Mãos', 'slug' => 'atelie-das-maos-'.uniqid()]);

        return FeedPost::create([
            'expositor_id' => $expositor->id,
            'type' => 'texto_livre',
            'content' => 'Chegou peça nova na loja!',
            'is_visible' => $visible,
        ]);
    }

    public function test_lists_only_visible_posts(): void
    {
        $this->makePost(visible: true);
        $this->makePost(visible: false);

        $this->getJson('/api/v1/feed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Chegou peça nova na loja!');
    }

    public function test_liked_by_me_is_false_for_guest_and_reflects_authenticated_user(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();

        $this->getJson('/api/v1/feed')->assertJsonPath('data.0.liked_by_me', false);

        $post->likes()->create(['user_id' => $user->id, 'created_at' => now()]);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/feed')->assertJsonPath('data.0.liked_by_me', true);
    }

    public function test_guest_cannot_curtir(): void
    {
        $post = $this->makePost();

        $this->postJson("/api/v1/feed/{$post->id}/curtir")->assertStatus(401);
    }

    public function test_authenticated_user_can_toggle_curtir(): void
    {
        $post = $this->makePost();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/feed/{$post->id}/curtir")
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->postJson("/api/v1/feed/{$post->id}/curtir")
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('likes_count', 0);
    }

    public function test_cannot_curtir_hidden_post(): void
    {
        $post = $this->makePost(visible: false);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/feed/{$post->id}/curtir")->assertStatus(403);
    }

    public function test_public_can_list_only_visible_comments(): void
    {
        $post = $this->makePost();
        $author = User::factory()->create();
        FeedComment::create([
            'feed_post_id' => $post->id,
            'user_id' => $author->id,
            'content' => 'Que lindo!',
            'is_visible' => true,
        ]);
        FeedComment::create([
            'feed_post_id' => $post->id,
            'user_id' => $author->id,
            'content' => 'Comentário oculto',
            'is_visible' => false,
        ]);

        $this->getJson("/api/v1/feed/{$post->id}/comentarios")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Que lindo!');
    }

    public function test_authenticated_user_can_comment(): void
    {
        $post = $this->makePost();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/feed/{$post->id}/comentarios", ['content' => 'Muito bom!'])
            ->assertCreated()
            ->assertJsonPath('data.content', 'Muito bom!');

        $this->assertDatabaseHas('feed_comments', ['feed_post_id' => $post->id, 'content' => 'Muito bom!']);
    }

    public function test_comment_requires_content(): void
    {
        $post = $this->makePost();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/feed/{$post->id}/comentarios", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_guest_cannot_comment(): void
    {
        $post = $this->makePost();

        $this->postJson("/api/v1/feed/{$post->id}/comentarios", ['content' => 'oi'])->assertStatus(401);
    }

    public function test_authenticated_user_can_report_post_once(): void
    {
        $post = $this->makePost();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/feed/{$post->id}/denunciar", ['reason' => 'Conteúdo impróprio'])
            ->assertOk();
        $this->assertDatabaseHas('feed_reports', ['feed_post_id' => $post->id, 'status' => 'pendente']);
        $this->assertEquals(1, $post->fresh()->reported_count);

        // Segunda denúncia do mesmo usuário não duplica nem incrementa de novo.
        $this->postJson("/api/v1/feed/{$post->id}/denunciar", ['reason' => 'De novo'])->assertOk();
        $this->assertDatabaseCount('feed_reports', 1);
        $this->assertEquals(1, $post->fresh()->reported_count);
    }
}
