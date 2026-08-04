<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExpositorSolicitacaoRequest;
use App\Mail\LojistaSolicitacaoRecebida;
use App\Models\LojistasSolicitacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ExpositorSolicitacaoController extends Controller
{
    /**
     * POST /api/v1/seja-um-expositor — mesmo fluxo do formulário
     * "Seja um Expositor" do site (rota `seja-um-expositor.post` em
     * routes/web.php), direto do app: cria a solicitação e dispara o
     * e-mail de confirmação. A aprovação continua manual, pelo admin.
     */
    public function store(StoreExpositorSolicitacaoRequest $request): JsonResponse
    {
        $solicitacao = LojistasSolicitacao::create($request->validated());

        try {
            Mail::to($solicitacao->email)->send(new LojistaSolicitacaoRecebida($solicitacao));
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'message' => 'Solicitação enviada! Enviamos um e-mail de confirmação e nossa equipe entrará em contato em até 3 dias úteis.',
        ], 201);
    }
}
