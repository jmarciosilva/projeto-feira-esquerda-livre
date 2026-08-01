<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lojista\AnswerQuestionRequest;
use App\Http\Resources\Api\V1\Lojista\PerguntaResource;
use App\Models\ProductQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PerguntaController extends Controller
{
    /** GET /api/v1/lojista/perguntas */
    public function index(Request $request): AnonymousResourceCollection
    {
        $expositorId = $request->user()->expositor->id;
        $filter = $request->input('filter', 'pending');

        $questions = $this->baseQuery($expositorId)
            ->when($filter === 'pending', fn ($q) => $q->whereNull('answered_at'))
            ->when($filter === 'answered', fn ($q) => $q->whereNotNull('answered_at'))
            ->with(['product', 'user'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return PerguntaResource::collection($questions)->additional([
            'meta' => [
                'pending_count' => $this->baseQuery($expositorId)->whereNull('answered_at')->count(),
                'answered_count' => $this->baseQuery($expositorId)->whereNotNull('answered_at')->count(),
            ],
        ]);
    }

    /** PATCH /api/v1/lojista/perguntas/{question}/responder */
    public function responder(AnswerQuestionRequest $request, int $question): PerguntaResource
    {
        $expositorId = $request->user()->expositor->id;
        $productQuestion = $this->baseQuery($expositorId)->findOrFail($question);

        $productQuestion->update([
            'answer' => trim($request->validated('answer')),
            'answered_at' => now(),
            'answered_by' => $request->user()->id,
        ]);

        return new PerguntaResource($productQuestion->load(['product', 'user']));
    }

    /** PATCH /api/v1/lojista/perguntas/{question}/visibilidade */
    public function visibilidade(Request $request, int $question): PerguntaResource
    {
        $expositorId = $request->user()->expositor->id;
        $productQuestion = $this->baseQuery($expositorId)->findOrFail($question);

        $productQuestion->update(['is_visible' => ! $productQuestion->is_visible]);

        return new PerguntaResource($productQuestion->load(['product', 'user']));
    }

    private function baseQuery(int $expositorId)
    {
        return ProductQuestion::whereHas('product', fn ($q) => $q->where('expositor_id', $expositorId));
    }
}
