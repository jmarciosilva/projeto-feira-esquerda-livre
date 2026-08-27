<?php

namespace Tests\Feature\Api\V1;

use App\Models\ContentCategory;
use App\Models\Event;
use App\Models\Expositor;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeExpositor(): Expositor
    {
        return Expositor::create([
            'name' => 'Ateliê das Mãos',
            'slug' => 'atelie-das-maos',
            'is_active' => true,
        ]);
    }

    public function test_lists_products_by_eixo(): void
    {
        $expositor = $this->makeExpositor();

        Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal',
            'price' => 89.9,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'servico',
            'name' => 'Aula de Design',
            'slug' => 'aula-de-design',
            'price' => 50,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/produtos')->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Bolsa Artesanal');

        $this->getJson('/api/v1/servicos')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/cuidados')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_shows_single_product(): void
    {
        $expositor = $this->makeExpositor();
        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal',
            'price' => 89.9,
            'is_active' => true,
        ]);

        $this->getJson("/api/v1/produtos/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Bolsa Artesanal')
            ->assertJsonPath('data.price', 89.9);
    }

    public function test_inactive_product_returns_404(): void
    {
        $expositor = $this->makeExpositor();
        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Descontinuado',
            'slug' => 'descontinuado',
            'price' => 10,
            'is_active' => false,
        ]);

        $this->getJson("/api/v1/produtos/{$product->id}")->assertNotFound();
    }

    public function test_shows_store_with_products(): void
    {
        $expositor = $this->makeExpositor();
        Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal',
            'price' => 89.9,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/lojas/atelie-das-maos')
            ->assertOk()
            ->assertJsonPath('expositor.name', 'Ateliê das Mãos')
            ->assertJsonCount(1, 'products');
    }

    public function test_lists_active_stores(): void
    {
        Expositor::create(['name' => 'Ateliê das Mãos', 'slug' => 'atelie-das-maos', 'is_active' => true]);
        Expositor::create(['name' => 'Loja Inativa', 'slug' => 'loja-inativa', 'is_active' => false]);

        $this->getJson('/api/v1/lojas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ateliê das Mãos');
    }

    public function test_lists_only_published_news_posts_ordered_by_recent(): void
    {
        $author = User::factory()->create();

        $older = Post::create([
            'user_id' => $author->id,
            'title' => 'Notícia mais antiga',
            'slug' => 'noticia-mais-antiga',
            'type' => 'news',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        $newer = Post::create([
            'user_id' => $author->id,
            'title' => 'Notícia mais recente',
            'slug' => 'noticia-mais-recente',
            'type' => 'news',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'is_active' => true,
        ]);

        Post::create([
            'user_id' => $author->id,
            'title' => 'Rascunho não publicado',
            'slug' => 'rascunho-nao-publicado',
            'type' => 'post',
            'status' => 'draft',
            'published_at' => null,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/noticias')->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.slug', $newer->slug);
        $response->assertJsonPath('data.1.slug', $older->slug);
    }

    public function test_shows_single_news_post_with_content_and_related_posts(): void
    {
        $author = User::factory()->create(['name' => 'Redação FEL']);

        $post = Post::create([
            'user_id' => $author->id,
            'title' => 'Notícia principal',
            'slug' => 'noticia-principal',
            'content' => '<p>Corpo completo da <strong>notícia</strong>.</p>',
            'type' => 'news',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $related = Post::create([
            'user_id' => $author->id,
            'title' => 'Notícia relacionada',
            'slug' => 'noticia-relacionada',
            'type' => 'news',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'is_active' => true,
        ]);

        // Mesmo eixo (post normal), não deve aparecer como relacionada de uma notícia.
        Post::create([
            'user_id' => $author->id,
            'title' => 'Post de outro tipo',
            'slug' => 'post-de-outro-tipo',
            'type' => 'post',
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/noticias/{$post->slug}")->assertOk();
        $response->assertJsonPath('noticia.title', 'Notícia principal');
        $response->assertJsonPath('noticia.content', '<p>Corpo completo da <strong>notícia</strong>.</p>');
        $response->assertJsonPath('noticia.author_name', 'Redação FEL');
        $response->assertJsonCount(1, 'relacionadas');
        $response->assertJsonPath('relacionadas.0.slug', $related->slug);
    }

    public function test_unpublished_news_post_returns_404(): void
    {
        $author = User::factory()->create();
        Post::create([
            'user_id' => $author->id,
            'title' => 'Rascunho',
            'slug' => 'rascunho',
            'type' => 'news',
            'status' => 'draft',
            'published_at' => null,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/noticias/rascunho')->assertNotFound();
    }

    public function test_contato_returns_public_whatsapp_and_email(): void
    {
        SiteSetting::instance()->update([
            'whatsapp' => '(11) 99999-9999',
            'email' => 'contato@feiraesquerdalivre.com.br',
            'mercado_pago_access_token' => 'SEGREDO_NAO_PODE_APARECER',
        ]);

        $response = $this->getJson('/api/v1/contato')->assertOk();
        $response->assertJsonPath('data.whatsapp', '(11) 99999-9999');
        $response->assertJsonPath('data.email', 'contato@feiraesquerdalivre.com.br');
        $response->assertJsonMissingPath('data.mercado_pago_access_token');
    }

    public function test_lists_categories_filtered_by_eixo(): void
    {
        ContentCategory::create(['name' => 'Artesanato', 'slug' => 'artesanato', 'eixo' => 'produto', 'is_active' => true]);
        ContentCategory::create(['name' => 'Consultorias', 'slug' => 'consultorias', 'eixo' => 'servico', 'is_active' => true]);

        $this->getJson('/api/v1/categorias?eixo=produto')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Artesanato');
    }

    public function test_lists_upcoming_events(): void
    {
        Event::create([
            'title' => 'Feira de Verão',
            'slug' => 'feira-de-verao',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => now()->addDays(10),
            'is_active' => true,
        ]);

        Event::create([
            'title' => 'Feira Passada',
            'slug' => 'feira-passada',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => now()->subDays(10),
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/agenda')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Feira de Verão');
    }

    public function test_guest_cannot_submit_product_question(): void
    {
        $expositor = $this->makeExpositor();
        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal',
            'price' => 89.9,
            'is_active' => true,
        ]);

        $this->postJson("/api/v1/produtos/{$product->id}/perguntas", ['question' => 'Tem em outra cor?'])
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_submit_and_view_answered_question(): void
    {
        $expositor = $this->makeExpositor();
        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal',
            'price' => 89.9,
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $token = $user->createToken('teste')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/produtos/{$product->id}/perguntas", ['question' => 'Tem em outra cor?'])
            ->assertCreated()
            ->assertJsonPath('data.question', 'Tem em outra cor?');

        // Pergunta ainda não respondida não aparece na listagem pública
        $this->getJson("/api/v1/produtos/{$product->id}/perguntas")->assertOk()->assertJsonCount(0, 'data');

        ProductQuestion::where('product_id', $product->id)->update([
            'answer' => 'Sim, temos em azul.',
            'answered_at' => now(),
        ]);

        $this->getJson("/api/v1/produtos/{$product->id}/perguntas")->assertOk()->assertJsonCount(1, 'data');
    }
}
