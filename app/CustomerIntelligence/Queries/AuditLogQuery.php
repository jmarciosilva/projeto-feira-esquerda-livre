<?php

namespace App\CustomerIntelligence\Queries;

use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Leitura da trilha de auditoria.
 *
 * Somente leitura: nao ha metodo de escrita nem de exclusao aqui. Escrever e
 * atribuicao exclusiva de RecordAuditLog, e apagar so acontece pelo expurgo de
 * retencao.
 */
class AuditLogQuery
{
    /**
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function paginate(?AuditAction $action = null, int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::query()
            // Apenas id e nome: a tela precisa dizer quem foi, nao expor o
            // cadastro inteiro de quem foi.
            ->with('user:id,name')
            ->when($action !== null, fn ($q) => $q->where('action', $action->value))
            // Por `id`, e nao por `created_at`: a tabela e append-only e o id e
            // monotonico, entao ele ordena com o mesmo significado e sem
            // empates dentro do mesmo segundo.
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
