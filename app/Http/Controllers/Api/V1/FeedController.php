<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReportFeedPostRequest;
use App\Http\Requests\Api\V1\StoreFeedCommentRequest;
use App\Http\Resources\Api\V1\FeedCommentResource;
use App\Http\Resources\Api\V1\FeedPostResource;
use App\Models\FeedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    /** GET /api/v1/feed — público; "liked_by_me" só é calculado se autenticado. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()?->id;

        $posts = FeedPost::visible()
            ->with('expositor')
            ->withCount(['likes', 'comments' => fn ($q) => $q->visible()])
            ->when($userId, fn ($q) => $q->with(['likes' => fn ($q2) => $q2->where('user_id', $userId)]))
            ->latest()
            ->paginate(10);

        return FeedPostResource::collection($posts);
    }

    /** GET /api/v1/feed/{post}/comentarios — público. */
    public function comentarios(FeedPost $post): AnonymousResourceCollection
    {
        $comments = $post->comments()->visible()->with('user')->orderBy('created_at')->get();

        return FeedCommentResource::collection($comments);
    }

    /** POST /api/v1/feed/{post}/comentarios — requer login. */
    public function comentar(StoreFeedCommentRequest $request, FeedPost $post): FeedCommentResource
    {
        $this->authorizeInteracao($request, $post);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
            'is_visible' => true,
        ]);

        return new FeedCommentResource($comment->load('user'));
    }

    /** POST /api/v1/feed/{post}/curtir — requer login; alterna curtir/descurtir. */
    public function curtir(Request $request, FeedPost $post): JsonResponse
    {
        $this->authorizeInteracao($request, $post);

        $userId = $request->user()->id;
        $existente = $post->likes()->where('user_id', $userId)->first();

        if ($existente) {
            $existente->delete();
        } else {
            $post->likes()->create(['user_id' => $userId, 'created_at' => now()]);
        }

        return response()->json([
            'liked' => ! $existente,
            'likes_count' => $post->likes()->count(),
        ]);
    }

    /** POST /api/v1/feed/{post}/denunciar — requer login; uma denúncia por usuário/post. */
    public function denunciar(ReportFeedPostRequest $request, FeedPost $post): JsonResponse
    {
        $this->authorizeInteracao($request, $post);

        $report = $post->reports()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['reason' => $request->validated('reason'), 'status' => 'pendente'],
        );

        if ($report->wasRecentlyCreated) {
            $post->increment('reported_count');

            return response()->json(['message' => 'Denúncia registrada. Nossa equipe vai analisar.']);
        }

        return response()->json(['message' => 'Você já denunciou esta publicação.']);
    }

    /** Mesma regra das Policies FeedPostPolicy::interact / FeedCommentPolicy::create. */
    private function authorizeInteracao(Request $request, FeedPost $post): void
    {
        abort_unless($post->is_visible && $request->user()->is_active, 403);
    }
}
