<?php

namespace App\CustomerIntelligence\Console;

use App\CustomerIntelligence\Actions\ForgetUser;
use App\CustomerIntelligence\Actions\RecordAuditLog;
use App\CustomerIntelligence\Enums\AuditAction;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Atende um pedido de eliminacao de rastro comportamental (LGPD, art. 18).
 *
 * Desvincula visitantes e eventos da conta e rotaciona o identificador publico
 * do visitante. Nao apaga eventos nem agregados — depois da desvinculacao eles
 * nao identificam mais a pessoa.
 *
 * A operacao e irreversivel, entao pede confirmacao.
 */
class ForgetUserCommand extends Command
{
    protected $signature = 'customer-intelligence:forget-user
                            {user : ID ou e-mail do usuário}';

    protected $description = 'Desvincula do Customer Intelligence todo o rastro de um usuário';

    public function handle(ForgetUser $forget, RecordAuditLog $auditar): int
    {
        $usuario = $this->resolveUser((string) $this->argument('user'));

        if ($usuario === null) {
            $this->components->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        $this->components->warn(sprintf(
            'Isto vai desvincular permanentemente o rastro de %s (#%d). Os eventos permanecem, mas deixam de apontar para a conta.',
            $usuario->email,
            $usuario->id
        ));

        // Sem confirmacao nada acontece — e o codigo de saida precisa dizer
        // isso. Devolver SUCCESS faria um script achar que o rastro foi
        // eliminado. O padrao `false` tambem cobre --no-interaction: a operacao
        // e irreversivel e nao roda sem autorizacao explicita.
        if (! $this->confirm('Confirmar?', false)) {
            $this->components->warn('Cancelado. Nenhum dado foi alterado.');

            return self::FAILURE;
        }

        $resultado = $forget($usuario->id);

        // Depois da execucao, nunca antes: a trilha registra o que aconteceu,
        // e um cancelamento (ou uma falha) nao deve aparecer como se tivesse
        // acontecido. Cancelar sai pelo return acima, sem gravar nada.
        $auditar(AuditAction::ForgetUser, 'user', $usuario->id);

        $this->components->info(sprintf(
            '%d visitante(s) e %d evento(s) desvinculados. Agregados diários preservados.',
            $resultado['visitors'],
            $resultado['events']
        ));

        return self::SUCCESS;
    }

    private function resolveUser(string $identificador): ?User
    {
        return is_numeric($identificador)
            ? User::find((int) $identificador)
            : User::where('email', $identificador)->first();
    }
}
