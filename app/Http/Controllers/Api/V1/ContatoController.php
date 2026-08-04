<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContatoMensagemRequest;
use App\Mail\ContatoConfirmacaoUsuario;
use App\Mail\ContatoMensagemRecebida;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContatoController extends Controller
{
    /**
     * GET /api/v1/contato — WhatsApp e e-mail públicos de contato, usados
     * pela seção "Quer vender seus produtos na Feira?" do app mobile.
     * Expõe só os dois campos públicos do SiteSetting, nunca o registro
     * inteiro (que também guarda credenciais de e-mail/pagamento/frete).
     */
    public function show(): JsonResponse
    {
        $settings = SiteSetting::instance();

        return response()->json([
            'data' => [
                'whatsapp' => $settings->whatsapp,
                'email' => $settings->email,
            ],
        ]);
    }

    /**
     * POST /api/v1/contato — mesmo fluxo do formulário de contato do site
     * (rota `contato.enviar` em routes/web.php), só que direto do app, sem
     * abrir o navegador: envia a mensagem para o e-mail da plataforma e uma
     * confirmação para quem enviou.
     */
    public function store(StoreContatoMensagemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $settings = SiteSetting::instance();
        $recipient = $settings->email
            ?: $settings->mail_from_address
            ?: config('mail.from.address')
            ?: 'contato@feiraesquerdalivre.com.br';

        try {
            Mail::to($recipient)->send(new ContatoMensagemRecebida($validated));
            Mail::to($validated['email'])->send(new ContatoConfirmacaoUsuario($validated));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Não foi possível enviar sua mensagem agora. Tente novamente em alguns instantes.',
            ], 502);
        }

        return response()->json([
            'message' => 'Mensagem enviada com sucesso! Nossa equipe retornará em breve.',
        ]);
    }
}
