<?php

namespace Tests\Feature;

use App\Models\Expositor;
use App\Models\FeedPost;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductShareImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_page_loads(): void
    {
        $this->get(route('feed.index'))->assertOk();
    }

    public function test_feed_likes_are_unique_per_user(): void
    {
        $user = User::factory()->create();
        $expositor = Expositor::create([
            'name' => 'Atelie das Maos',
            'slug' => 'atelie-das-maos',
        ]);
        $post = FeedPost::create([
            'expositor_id' => $expositor->id,
            'type' => 'texto_livre',
            'content' => 'Novidade no feed',
            'is_visible' => true,
        ]);

        $post->likes()->create(['user_id' => $user->id, 'created_at' => now()]);
        $post->likes()->firstOrCreate(['user_id' => $user->id], ['created_at' => now()]);

        $this->assertSame(1, $post->likes()->count());
    }

    public function test_product_share_image_is_square_png(): void
    {
        $expositor = Expositor::create([
            'name' => 'Atelie das Maos',
            'slug' => 'atelie-das-maos',
        ]);
        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'name' => 'Bolsa Tecida Artesanal',
            'slug' => 'bolsa-tecida-artesanal',
            'price' => 89.90,
            'is_active' => true,
        ]);

        $png = app(ProductShareImageService::class)->make($product->ofertaVigente);
        $size = getimagesizefromstring($png);

        $this->assertSame('image/png', $size['mime']);
        $this->assertSame([1080, 1080], [$size[0], $size[1]]);
    }
}
