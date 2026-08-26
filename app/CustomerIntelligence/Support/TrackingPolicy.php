<?php

namespace App\CustomerIntelligence\Support;

/**
 * O unico lugar do sistema que decide se rastrear e permitido.
 *
 * Existe para que a resposta seja dada uma vez, e nao sete. Os pontos de
 * negocio que chamam `track()` — carrinho, checkout, pedido, envio — nao
 * conhecem consentimento e nao deveriam conhecer: espalhar `if` por eles
 * transformaria cada regra nova de privacidade numa varredura pelo codigo de
 * compra, que e exatamente onde nao se quer mexer.
 *
 * Dois consumidores, so:
 *
 *   TrackVisitorSession          antes de criar visitante, sessao e cookies
 *   CustomerIntelligenceService  antes de enfileirar qualquer evento
 *
 * `record()` de proposito NAO consulta a politica: ele roda dentro do worker,
 * onde nao existe cookie para consultar. A autorizacao e verificada onde o
 * evento nasce; depois disso o job so persiste o que ja foi autorizado.
 */
class TrackingPolicy
{
    public function __construct(
        private readonly ConsentContext $consent,
    ) {}

    /**
     * Coleta permitida: o modulo esta ligado E a pessoa aceitou.
     *
     * As duas condicoes sao independentes. `enabled` e uma chave de operacao,
     * para desligar o modulo inteiro num diagnostico; o consentimento e da
     * pessoa. Nenhuma das duas sobrepoe a outra.
     */
    public function allowsAnalytics(): bool
    {
        return $this->moduleEnabled() && $this->consent->allowsAnalytics();
    }

    public function moduleEnabled(): bool
    {
        return (bool) config('customer-intelligence-internal.enabled', true);
    }

    /**
     * Se a pergunta ainda precisa ser feita — o que o banner consulta.
     *
     * Com o modulo desligado nao ha o que consentir, entao o banner nao
     * aparece: perguntar sobre uma coleta que nao existe seria ruido.
     */
    public function needsDecision(): bool
    {
        return $this->moduleEnabled() && ! $this->consent->isDecided();
    }
}
