<?php

namespace App\CustomerIntelligence\Actions;

use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Unico caminho de escrita da trilha de auditoria.
 *
 * Grava direto, de forma sincrona, sem fila: uma trilha de auditoria que pode
 * ficar presa numa fila ou se perder numa retentativa nao serve como trilha de
 * auditoria.
 *
 * Nao consulta consentimento — de proposito. A auditoria registra o que a
 * PESSOA ADMINISTRATIVA fez com dado de terceiros; a preferencia de analytics
 * dela propria no navegador nao tem nenhuma relacao com isso, e deixar as duas
 * coisas se tocarem permitiria a quem e auditado desligar a propria auditoria.
 *
 * Assinatura sem parametro livre: o que entra e user, acao e um recurso
 * tipado. Nao ha por onde passar payload, propriedades ou identificador de
 * visitante em campo aberto.
 */
class RecordAuditLog
{
    public function __invoke(
        AuditAction $action,
        ?string $resourceType = null,
        int|string|null $resourceId = null,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            // `Auth::id()` cobre o painel; nulo explicito cobre o agendador,
            // que executa sem ninguem logado.
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId === null ? null : (string) $resourceId,
        ]);
    }
}
