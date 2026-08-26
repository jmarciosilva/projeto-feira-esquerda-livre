<?php

namespace App\CustomerIntelligence\Enums;

/**
 * Acoes administrativas registradas na trilha de auditoria.
 *
 * Enum, e nao string solta, para que o conjunto seja fechado: a tela de
 * auditoria filtra por ele e o rotulo em portugues sai daqui, sem tabela de
 * traducao paralela.
 *
 * Duas naturezas convivem:
 *
 *   *.view       alguem abriu uma tela e viu dado comportamental;
 *   as demais    alguem executou uma operacao que altera ou apaga dado.
 */
enum AuditAction: string
{
    case DashboardView = 'customer_intelligence.dashboard.view';
    case EventsView = 'customer_intelligence.events.view';
    case VisitorsView = 'customer_intelligence.visitors.view';
    case VisitorView = 'customer_intelligence.visitor.view';
    case ForgetUser = 'customer_intelligence.forget_user';
    case RebuildMetrics = 'customer_intelligence.rebuild_metrics';
    case PruneEvents = 'customer_intelligence.prune_events';
    case AuditView = 'customer_intelligence.audit.view';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'Abriu o painel',
            self::EventsView => 'Consultou eventos',
            self::VisitorsView => 'Consultou visitantes',
            self::VisitorView => 'Abriu um visitante',
            self::ForgetUser => 'Desvinculou o rastro de um usuário',
            self::RebuildMetrics => 'Reconstruiu métricas diárias',
            self::PruneEvents => 'Expurgou eventos por retenção',
            self::AuditView => 'Consultou a auditoria',
        };
    }

    /**
     * Operacoes que alteram ou apagam dado, em oposicao a simples leitura.
     * A tela destaca as duas naturezas de forma diferente.
     */
    public function isWrite(): bool
    {
        return match ($this) {
            self::ForgetUser, self::RebuildMetrics, self::PruneEvents => true,
            default => false,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
