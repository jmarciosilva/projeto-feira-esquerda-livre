<?php

namespace App\CustomerIntelligence\Enums;

/**
 * Metricas agregadas diariamente em `ci_daily_metrics`.
 *
 * Sao poucas de proposito: a tabela guarda agregados, nao uma copia de
 * `ci_events`. Cada uma existe porque alimenta um card ou o grafico do painel.
 */
enum MetricName: string
{
    /** Total de eventos no dia. Com dimensao `event_name`, o total por tipo. */
    case Eventos = 'eventos';

    /** Sessoes abertas no dia. */
    case Sessoes = 'sessoes';

    /** Visitantes distintos que abriram ao menos uma sessao no dia. */
    case Visitantes = 'visitantes';

    /** Conversoes: pedidos criados. */
    case Conversoes = 'conversoes';

    public function label(): string
    {
        return match ($this) {
            self::Eventos => 'Eventos',
            self::Sessoes => 'Sessões',
            self::Visitantes => 'Visitantes',
            self::Conversoes => 'Conversões',
        };
    }

    /**
     * Dimensao usada para desdobrar a metrica por tipo de evento.
     */
    public const DIMENSION_EVENT_NAME = 'event_name';

    /**
     * Evento que conta como conversao. Na plataforma externa isso vivia numa
     * configuracao do servidor; aqui e uma decisao explicita do projeto.
     */
    public static function conversionEvent(): EventName
    {
        return EventName::PedidoCriado;
    }
}
