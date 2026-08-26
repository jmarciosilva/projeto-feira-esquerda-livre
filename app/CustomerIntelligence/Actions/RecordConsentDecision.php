<?php

namespace App\CustomerIntelligence\Actions;

use App\CustomerIntelligence\Enums\ConsentState;
use App\CustomerIntelligence\Support\ConsentContext;
use App\CustomerIntelligence\Support\ConsentCookie;
use App\CustomerIntelligence\Support\VisitorContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;

/**
 * Grava a escolha de privacidade e aplica os efeitos imediatos dela.
 *
 * Vive fora do controller porque a decisao tem consequencias que nao sao HTTP:
 * quem recusa depois de ter aceitado precisa parar de ser rastreado na mesma
 * requisicao, e nao apenas na proxima.
 *
 * O que esta acao NAO faz, deliberadamente: apagar historico. Recusar analytics
 * interrompe a coleta daqui para frente; desvincular o rastro que ja existe e
 * outro ato, explicito, pelo comando `customer-intelligence:forget-user`.
 * Confundir os dois faria um clique de rodape apagar dado em silencio.
 */
class RecordConsentDecision
{
    public function __construct(
        private readonly ConsentContext $consent,
        private readonly VisitorContext $visitor,
    ) {}

    public function __invoke(ConsentState $decisao): void
    {
        $agora = Carbon::now();

        Cookie::queue(
            ConsentCookie::name(),
            ConsentCookie::encode($decisao, $agora),
            ConsentCookie::minutes(),
        );

        // O cookie so chega ao navegador na resposta. Sem isto, o resto desta
        // requisicao ainda enxergaria a escolha anterior.
        $this->consent->set($decisao, $agora);

        if (! $decisao->allowsAnalytics()) {
            $this->stopTracking();
        }
    }

    /**
     * Efeito imediato da recusa.
     *
     * Expira os cookies de analytics e esvazia o contexto da requisicao. Como
     * os cookies somem, a proxima visita sob ACCEPTED nascera com identidade
     * nova — nao ha tentativa de religar ao visitante anterior, o que seria
     * justamente reidentificar quem pediu para nao ser seguido.
     */
    private function stopTracking(): void
    {
        foreach (['visitor_cookie', 'session_cookie'] as $chave) {
            $nome = (string) config("customer-intelligence-internal.{$chave}.name");

            if ($nome !== '') {
                Cookie::queue(Cookie::forget($nome));
            }
        }

        $this->visitor->forget();
    }
}
