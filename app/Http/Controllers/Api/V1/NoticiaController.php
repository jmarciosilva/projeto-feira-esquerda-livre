<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoticiaController extends Controller
{
    /** GET /api/v1/noticias — usado pelo carrossel "Nossas Notícias e Blog" do app mobile. */
    public function index(): AnonymousResourceCollection
    {
        $posts = Post::published()->orderByDesc('published_at')->paginate(12);

        return PostResource::collection($posts);
    }

    /**
     * GET /api/v1/noticias/{slug} — notícia completa (com o corpo em HTML,
     * renderizado nativamente pelo app) + notícias relacionadas.
     */
    public function show(string $slug): JsonResponse
    {
        $post = Post::published()->where('slug', $slug)->with('author')->firstOrFail();

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->where('type', $post->type)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return response()->json([
            'noticia' => new PostResource($post),
            'relacionadas' => PostResource::collection($related),
        ]);
    }
}
