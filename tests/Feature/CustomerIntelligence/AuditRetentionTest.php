<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Models\AuditLog;
use App\CustomerIntelligence\Models\TrackedEvent;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Retencao da trilha de auditoria (GOV-01D).
 *
 * O ponto que a GOV-01 fez questao de fixar: este expurgo e SEPARADO do de
 * `ci_events`. Prazos diferentes, comandos diferentes, agendamentos diferentes
 * — e nenhum dos dois pode arrastar o outro.
 */
class AuditRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function registroEm(Carbon $quando, AuditAction $acao = AuditAction::DashboardView): AuditLog
    {
        $log = AuditLog::create(['action' => $acao]);

        // `created_at` e preenchido pelo Eloquent; para posicionar no passado
        // e preciso reescrever a coluna direto.
        AuditLog::where('id', $log->id)->update(['created_at' => $quando]);

        return $log->refresh();
    }

    private function eventoEm(Carbon $quando): TrackedEvent
    {
        return TrackedEvent::create([
            'event_uuid' => (string) Str::orderedUuid(),
            'event_name' => EventName::ProdutoVisualizado,
            'event_category' => EventName::ProdutoVisualizado->category(),
            'occurred_at' => $quando,
        ]);
    }

    /**
     * O codigo de um arquivo PHP sem os comentarios, para que uma verificacao
     * de acoplamento nao seja disparada por prosa explicativa.
     */
    private function codigoSemComentarios(string $caminho): string
    {
        $tokens = token_get_all((string) file_get_contents($caminho));

        $codigo = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                $codigo .= $token[1];

                continue;
            }

            $codigo .= $token;
        }

        return $codigo;
    }

    private function rodar(array $opcoes = []): int
    {
        $this->withoutMockingConsoleOutput();

        return Artisan::call('customer-intelligence:prune-audit-logs', $opcoes);
    }

    // ─── Janela padrão ────────────────────────────────────────────────────

    public function test_the_default_retention_is_two_years(): void
    {
        $this->assertSame(730, (int) config('customer-intelligence-internal.retention.audit_days'));
    }

    public function test_recent_records_are_kept(): void
    {
        $this->registroEm(Carbon::now()->subDays(30));
        $this->registroEm(Carbon::now()->subDays(700));

        $this->rodar();

        $this->assertSame(2, AuditLog::count());
    }

    public function test_records_older_than_the_window_are_removed(): void
    {
        $antigo = $this->registroEm(Carbon::now()->subDays(731));
        $recente = $this->registroEm(Carbon::now()->subDays(10));

        $this->rodar();

        $this->assertNull(AuditLog::find($antigo->id));
        $this->assertNotNull(AuditLog::find($recente->id));
    }

    /**
     * A fronteira, escrita do mesmo jeito que a de `ci_events`: exatamente 730
     * dias FICA; so o estritamente mais velho sai.
     */
    public function test_a_record_at_exactly_the_boundary_stays(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');

        $naLinha = $this->registroEm(Carbon::now()->subDays(730));
        $umSegundoAlem = $this->registroEm(Carbon::now()->subDays(730)->subSecond());

        $this->rodar();

        $this->assertNotNull(AuditLog::find($naLinha->id), 'Exatamente 730 dias permanece.');
        $this->assertNull(AuditLog::find($umSegundoAlem->id));

        Carbon::setTestNow();
    }

    // ─── Opções ───────────────────────────────────────────────────────────

    public function test_dry_run_removes_nothing(): void
    {
        $this->registroEm(Carbon::now()->subDays(900));

        $this->rodar(['--dry-run' => true]);

        $this->assertSame(1, AuditLog::count());
    }

    public function test_the_window_can_be_overridden(): void
    {
        $this->registroEm(Carbon::now()->subDays(40));

        $this->rodar(['--days' => 30]);

        $this->assertSame(0, AuditLog::count());
    }

    /**
     * `--days=0` significaria apagar tudo. O comando recusa em vez de aceitar
     * uma retencao de zero dia — e `'0'` sendo falsy em PHP, uma checagem
     * descuidada cairia no padrao em silencio.
     */
    public function test_a_zero_day_window_is_refused(): void
    {
        $this->registroEm(Carbon::now()->subDays(1));

        $codigo = $this->rodar(['--days' => 0]);

        $this->assertSame(Command::FAILURE, $codigo);
        $this->assertSame(1, AuditLog::count(), 'Nada pode ter sido removido.');
    }

    public function test_it_works_in_batches(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->registroEm(Carbon::now()->subDays(800 + $i));
        }

        $this->rodar(['--chunk' => 2]);

        $this->assertSame(0, AuditLog::count());
    }

    // ─── Independência entre os dois expurgos ─────────────────────────────

    public function test_pruning_the_audit_never_touches_the_events(): void
    {
        $evento = $this->eventoEm(Carbon::now()->subDays(900));
        $this->registroEm(Carbon::now()->subDays(900));

        $this->rodar();

        $this->assertSame(0, AuditLog::count());
        $this->assertNotNull(TrackedEvent::find($evento->id), 'ci_events tem prazo e comando próprios.');
    }

    public function test_pruning_the_events_never_touches_the_audit(): void
    {
        $this->eventoEm(Carbon::now()->subDays(900));
        $registro = $this->registroEm(Carbon::now()->subDays(900));

        $this->withoutMockingConsoleOutput();
        Artisan::call('customer-intelligence:prune-events');

        $this->assertSame(0, TrackedEvent::count());
        $this->assertNotNull(
            AuditLog::find($registro->id),
            'A auditoria sobrevive ao expurgo de eventos — inclusive o registro do próprio expurgo.'
        );
    }

    public function test_the_two_prunes_are_separate_commands(): void
    {
        $comandos = array_keys(Artisan::all());

        $this->assertContains('customer-intelligence:prune-events', $comandos);
        $this->assertContains('customer-intelligence:prune-audit-logs', $comandos);

        // Um nao pode delegar para o outro nem alcancar a tabela do outro.
        //
        // Os comentarios sao removidos antes da comparacao: a documentacao do
        // comando explica por que os dois expurgos sao separados, e citar o
        // outro em prosa e o oposto de acoplamento.
        $codigo = $this->codigoSemComentarios(
            app_path('CustomerIntelligence/Console/PruneAuditLogsCommand.php')
        );

        foreach (['TrackedEvent', 'ci_events', 'prune-events', 'Artisan::call', '$this->call('] as $proibido) {
            $this->assertStringNotContainsString(
                $proibido,
                $codigo,
                "O expurgo de auditoria não pode referenciar [{$proibido}] em código."
            );
        }
    }

    public function test_both_prunes_are_scheduled_independently(): void
    {
        $agendados = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($evento) => $evento->command.'|'.$evento->expression);

        $this->assertTrue(
            $agendados->contains(fn ($linha) => str_contains($linha, 'prune-audit-logs')),
            'O expurgo de auditoria precisa ter agendamento próprio.'
        );
        $this->assertTrue(
            $agendados->contains(fn ($linha) => str_contains($linha, 'prune-events')),
            'E o de eventos permanece com o dele.'
        );
    }

    /**
     * O expurgo nao se audita. Registrar cada execucao criaria exatamente o
     * rastro que a retencao existe para limitar — e uma trilha que nunca
     * esvazia.
     */
    public function test_the_audit_prune_does_not_audit_itself(): void
    {
        $this->registroEm(Carbon::now()->subDays(900));

        $this->rodar();

        $this->assertSame(0, AuditLog::count());
    }
}
