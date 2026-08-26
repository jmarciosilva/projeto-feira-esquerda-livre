<?php

namespace App\CustomerIntelligence\Http\Controllers;

use App\CustomerIntelligence\Actions\RecordConsentDecision;
use App\CustomerIntelligence\Enums\ConsentState;
use App\CustomerIntelligence\Support\ConsentContext;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Superficie publica da preferencia de privacidade.
 *
 * Vive dentro do modulo, e nao em App\Http\Controllers, porque e o modulo que
 * define o que a escolha significa. Quem consome sao duas telas: o banner, que
 * so aparece enquanto a pergunta nao foi respondida, e a pagina permanente de
 * preferencias, que existe para que mudar de ideia seja tao facil quanto
 * decidir da primeira vez.
 *
 * Formulario HTML comum, sem JavaScript: a escolha de privacidade precisa
 * funcionar tambem para quem bloqueia script, que costuma ser exatamente quem
 * mais se importa com ela.
 */
class ConsentController extends Controller
{
    public function edit(ConsentContext $consent): View
    {
        return view('privacidade.preferencias', [
            'settings' => SiteSetting::instance(),
            'estado' => $consent->state(),
            'decididoEm' => $consent->decidedAt(),
        ]);
    }

    public function store(Request $request, RecordConsentDecision $registrar): RedirectResponse
    {
        // Apenas os dois estados que uma pessoa pode escolher. `unknown` e um
        // estado do sistema, nao uma opcao de formulario — aceita-lo aqui
        // deixaria qualquer requisicao devolver alguem ao limbo do banner.
        $dados = $request->validate([
            'decision' => 'required|in:accepted,rejected',
        ]);

        $decisao = ConsentState::from($dados['decision']);

        $registrar($decisao);

        return back(fallback: route('privacidade.preferencias'))
            ->with('privacidade_status', $decisao->allowsAnalytics()
                ? 'Preferência salva: você aceitou as métricas de navegação.'
                : 'Preferência salva: as métricas de navegação estão desativadas.');
    }
}
