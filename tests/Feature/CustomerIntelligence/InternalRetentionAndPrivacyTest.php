<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Actions\ForgetUser;
use App\CustomerIntelligence\Actions\ResolveVisitorSession;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\CustomerIntelligence\Support\PropertySanitizer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithConsent;
use Tests\TestCase;

/**
 * Retencao de 180 dias (CI-09B) e comportamento de privacidade (CI-09C).
 */
class InternalRetentionAndPrivacyTest extends TestCase
{
    use InteractsWithConsent, RefreshDatabase;

    /**
     * Analytics e opt-in desde a GOV-01. Esta suite descreve o comportamento da
     * COLETA, que so existe sob aceite — entao o aceite e a precondicao dela.
     * O que acontece sem aceite tem suite propria: ConsentPolicyTest.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->acceptingAnalytics();
    }

    private function service(): CustomerIntelligenceService
    {
        return app(CustomerIntelligenceService::class);
    }

    private function eventoEm(Carbon $quando, EventName $evento = EventName::ProdutoVisualizado): TrackedEvent
    {
        return TrackedEvent::create([
            'event_uuid' => (string) Str::orderedUuid(),
            'event_name' => $evento,
            'event_category' => $evento->category(),
            'occurred_at' => $quando,
        ]);
    }

    // ─── Retenção ─────────────────────────────────────────────────────────

    /**
     * A regra e `occurred_at < cutoff`, com cutoff em agora menos 180 dias.
     * Um evento com exatamente 180 dias fica; so o estritamente mais velho sai.
     */
    public function test_the_cutoff_keeps_179_and_180_days_and_removes_181(): void
    {
        $de179 = $this->eventoEm(Carbon::now()->subDays(179));
        $de180 = $this->eventoEm(Carbon::now()->subDays(180)->addMinutes(5));
        $de181 = $this->eventoEm(Carbon::now()->subDays(181));

        $this->artisan('customer-intelligence:prune-events')->assertSuccessful();

        $this->assertNotNull($de179->fresh(), '179 dias precisa ficar.');
        $this->assertNotNull($de180->fresh(), 'Exatamente 180 dias fica: a regra é estritamente menor que o corte.');
        $this->assertNull($de181->fresh(), '181 dias precisa sair.');
    }

    public function test_dry_run_reports_without_deleting_anything(): void
    {
        $this->eventoEm(Carbon::now()->subDays(200));
        $this->eventoEm(Carbon::now()->subDays(300));

        $this->artisan('customer-intelligence:prune-events --dry-run')
            ->expectsOutputToContain('Eventos fora da janela: 2')
            ->assertSuccessful();

        $this->assertSame(2, TrackedEvent::count(), 'Dry-run não pode apagar nada.');
    }

    public function test_retention_window_is_configurable(): void
    {
        $this->eventoEm(Carbon::now()->subDays(40));

        $this->artisan('customer-intelligence:prune-events --days=30')->assertSuccessful();

        $this->assertSame(0, TrackedEvent::count());
    }

    public function test_days_zero_is_rejected_and_deletes_nothing(): void
    {
        $this->eventoEm(Carbon::now()->subDays(400));

        $this->artisan('customer-intelligence:prune-events --days=0')
            ->expectsOutputToContain('pelo menos 1 dia')
            ->assertFailed();

        $this->assertSame(1, TrackedEvent::count(), 'Nenhum DELETE pode ter rodado.');
    }

    public function test_a_negative_retention_is_rejected(): void
    {
        $this->eventoEm(Carbon::now()->subDays(400));

        $this->artisan('customer-intelligence:prune-events --days=-5')->assertFailed();

        $this->assertSame(1, TrackedEvent::count());
    }

    public function test_one_day_of_retention_is_accepted(): void
    {
        $this->eventoEm(Carbon::now()->subDays(2));
        $this->eventoEm(Carbon::now());

        $this->artisan('customer-intelligence:prune-events --days=1')->assertSuccessful();

        $this->assertSame(1, TrackedEvent::count(), 'Só o de 2 dias sai.');
    }

    public function test_without_days_the_configured_default_is_used(): void
    {
        $this->eventoEm(Carbon::now()->subDays(181));
        $this->eventoEm(Carbon::now()->subDays(179));

        $this->artisan('customer-intelligence:prune-events')
            ->expectsOutputToContain('Retenção de 180 dias')
            ->assertSuccessful();

        $this->assertSame(1, TrackedEvent::count());
    }

    public function test_pruning_uses_occurred_at_and_not_created_at(): void
    {
        // Fato antigo, gravado hoje: e o que acontece quando a fila atrasa.
        $evento = $this->eventoEm(Carbon::now()->subDays(400));
        $this->assertTrue($evento->created_at->isToday());

        $this->artisan('customer-intelligence:prune-events')->assertSuccessful();

        $this->assertSame(0, TrackedEvent::count(), 'A idade vem de occurred_at.');
    }

    public function test_pruning_runs_in_batches_without_loading_everything(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->eventoEm(Carbon::now()->subDays(200 + $i));
        }

        $this->artisan('customer-intelligence:prune-events --chunk=5')->assertSuccessful();

        $this->assertSame(0, TrackedEvent::count());
    }

    /**
     * O ponto central da política: o agregado sobrevive ao evento bruto. É o
     * que torna a retenção curta viável sem perder a série histórica.
     */
    public function test_pruning_never_touches_the_daily_metrics(): void
    {
        $antigo = Carbon::now()->subDays(300);
        $this->service()->record(EventName::PedidoCriado, occurredAt: $antigo, eventUuid: (string) Str::orderedUuid());

        $agregadosAntes = DailyMetric::count();
        $this->assertGreaterThan(0, $agregadosAntes);

        $this->artisan('customer-intelligence:prune-events')->assertSuccessful();

        $this->assertSame(0, TrackedEvent::count(), 'O evento bruto saiu.');
        $this->assertSame($agregadosAntes, DailyMetric::count(), 'Os agregados são permanentes.');
    }

    public function test_prune_is_registered_in_the_scheduler(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('customer-intelligence:prune-events')
            ->assertSuccessful();
    }

    /**
     * Reconstruir um intervalo cujo evento bruto já foi expurgado zeraria a
     * série histórica. O comando limita o início ao evento mais antigo ainda
     * disponível.
     */
    public function test_rebuild_does_not_erase_metrics_of_pruned_periods(): void
    {
        $antigo = Carbon::now()->subDays(300);
        $this->service()->record(EventName::PedidoCriado, occurredAt: $antigo, eventUuid: (string) Str::orderedUuid());
        $this->service()->record(EventName::ProdutoVisualizado, eventUuid: (string) Str::orderedUuid());

        $this->artisan('customer-intelligence:prune-events')->assertSuccessful();
        $metricaAntiga = DailyMetric::where('metric_date', $antigo->toDateString())->count();
        $this->assertGreaterThan(0, $metricaAntiga);

        $this->artisan('customer-intelligence:rebuild-daily-metrics --from='.$antigo->toDateString())
            ->assertSuccessful();

        $this->assertSame(
            $metricaAntiga,
            DailyMetric::where('metric_date', $antigo->toDateString())->count(),
            'Os agregados do período expurgado precisam sobreviver ao rebuild.'
        );
    }

    // ─── Privacidade ──────────────────────────────────────────────────────

    public function test_deleting_a_user_unlinks_visitor_and_events(): void
    {
        $user = User::factory()->create();
        $sessao = app(ResolveVisitorSession::class)('v-lgpd', 's-lgpd', $user->id);
        $this->service()->record(EventName::PedidoCriado, session: $sessao, eventUuid: (string) Str::orderedUuid());

        $this->assertSame($user->id, TrackedEvent::sole()->user_id);

        $user->delete();

        $this->assertNull(Visitor::sole()->user_id, 'O visitante fica anônimo.');
        $this->assertNull(TrackedEvent::sole()->user_id, 'O evento perde o vínculo pessoal.');
        $this->assertSame(1, TrackedEvent::count(), 'O evento em si permanece: já não identifica ninguém.');
    }

    public function test_deleting_a_user_preserves_the_aggregates(): void
    {
        $user = User::factory()->create();
        $sessao = app(ResolveVisitorSession::class)('v-agg', 's-agg', $user->id);
        $this->service()->record(EventName::PedidoCriado, session: $sessao, eventUuid: (string) Str::orderedUuid());

        $antes = DailyMetric::sum('metric_value');
        $user->delete();

        $this->assertSame($antes, DailyMetric::sum('metric_value'));
    }

    public function test_forgetting_a_user_unlinks_without_deleting_the_account(): void
    {
        $user = User::factory()->create();
        $sessao = app(ResolveVisitorSession::class)('v-esquecer', 's-esquecer', $user->id);
        $this->service()->record(EventName::PedidoCriado, session: $sessao, eventUuid: (string) Str::orderedUuid());

        $uuidAntes = Visitor::sole()->visitor_uuid;

        $resultado = app(ForgetUser::class)($user->id);

        $this->assertSame(1, $resultado['visitors']);
        $this->assertSame(1, $resultado['events']);
        $this->assertNull(Visitor::sole()->user_id);
        $this->assertNull(TrackedEvent::sole()->user_id);
        $this->assertNotSame($uuidAntes, Visitor::sole()->visitor_uuid, 'O pseudônimo é rotacionado.');
        $this->assertNotNull($user->fresh(), 'A conta continua existindo.');
    }

    public function test_forgetting_a_user_keeps_events_and_aggregates(): void
    {
        $user = User::factory()->create();
        $sessao = app(ResolveVisitorSession::class)('v-mantem', 's-mantem', $user->id);
        $this->service()->record(EventName::PedidoCriado, session: $sessao, eventUuid: (string) Str::orderedUuid());

        $agregados = DailyMetric::sum('metric_value');

        app(ForgetUser::class)($user->id);

        $this->assertSame(1, TrackedEvent::count());
        $this->assertSame($agregados, DailyMetric::sum('metric_value'));
    }

    public function test_forgetting_a_user_does_not_touch_other_visitors(): void
    {
        $alvo = User::factory()->create();
        $outro = User::factory()->create();

        app(ResolveVisitorSession::class)('v-alvo', 's-alvo', $alvo->id);
        app(ResolveVisitorSession::class)('v-outro', 's-outro', $outro->id);

        app(ForgetUser::class)($alvo->id);

        $this->assertNull(Visitor::where('visitor_uuid', '!=', 'v-outro')->first()?->user_id);
        $this->assertSame($outro->id, Visitor::where('visitor_uuid', 'v-outro')->value('user_id'));
    }

    // ─── Comando forget-user ──────────────────────────────────────────────

    public function test_forget_user_command_runs_when_confirmed(): void
    {
        $user = User::factory()->create();
        app(ResolveVisitorSession::class)('v-cmd-sim', 's-cmd-sim', $user->id);

        $this->artisan('customer-intelligence:forget-user '.$user->id)
            ->expectsConfirmation('Confirmar?', 'yes')
            ->assertSuccessful();

        $this->assertNull(Visitor::sole()->user_id);
    }

    /**
     * Cancelar nao e sucesso: devolver SUCCESS faria um script achar que o
     * rastro foi eliminado quando nada aconteceu.
     */
    public function test_forget_user_command_fails_when_cancelled(): void
    {
        $user = User::factory()->create();
        app(ResolveVisitorSession::class)('v-cmd-nao', 's-cmd-nao', $user->id);

        $this->artisan('customer-intelligence:forget-user '.$user->id)
            ->expectsConfirmation('Confirmar?', 'no')
            ->assertFailed();

        $this->assertSame($user->id, Visitor::sole()->user_id, 'Nada pode ter sido alterado.');
    }

    /**
     * Sem interacao a confirmacao assume o padrao `false`: a operacao e
     * irreversivel e nao roda sem autorizacao explicita.
     *
     * Usa Artisan::call em vez de $this->artisan() porque o harness deste
     * ultimo substitui a saida por um mock que rejeita qualquer pergunta nao
     * declarada — impedindo justamente o caminho nao interativo.
     */
    public function test_forget_user_command_is_safe_without_interaction(): void
    {
        $user = User::factory()->create();
        app(ResolveVisitorSession::class)('v-cmd-auto', 's-cmd-auto', $user->id);

        $codigo = Artisan::call('customer-intelligence:forget-user', [
            'user' => (string) $user->id,
            '--no-interaction' => true,
        ]);

        $this->assertSame(Command::FAILURE, $codigo, 'Cancelamento não pode sair como sucesso.');
        $this->assertSame($user->id, Visitor::sole()->user_id, 'Nada destrutivo sem autorização.');
    }

    public function test_forget_user_command_reports_an_unknown_user(): void
    {
        $this->artisan('customer-intelligence:forget-user nao-existe@teste.com')->assertFailed();
    }

    // ─── Rebuild sem eventos ──────────────────────────────────────────────

    /**
     * `ci_events` vazia, `ci_sessions` com registros antigos e agregados no
     * historico: o rebuild nao pode usar as sessoes para decidir o que apagar.
     */
    public function test_rebuild_preserves_metrics_when_there_are_no_events(): void
    {
        app(ResolveVisitorSession::class)('v-antigo', 's-antigo');
        VisitorSession::query()->update(['started_at' => Carbon::now()->subDays(400)]);

        DailyMetric::record(Carbon::now()->subDays(400)->toDateString(), 'eventos', 42);
        DailyMetric::record(Carbon::now()->subDays(300)->toDateString(), 'eventos', 17);

        $this->assertSame(0, TrackedEvent::count());
        $antes = DailyMetric::sum('metric_value');

        $this->artisan('customer-intelligence:rebuild-daily-metrics')
            ->expectsOutputToContain('preservados')
            ->assertSuccessful();

        $this->assertSame($antes, DailyMetric::sum('metric_value'), 'Nenhum agregado pode ter sumido.');
    }

    public function test_rebuild_keeps_history_older_than_the_oldest_raw_event(): void
    {
        // Histórico agregado de dois anos, eventos brutos só nos últimos dias.
        DailyMetric::record(Carbon::now()->subDays(500)->toDateString(), 'eventos', 99);
        $this->service()->record(EventName::ProdutoVisualizado, eventUuid: (string) Str::orderedUuid());

        $this->artisan('customer-intelligence:rebuild-daily-metrics --from='.Carbon::now()->subDays(600)->toDateString())
            ->expectsOutputToContain('Início ajustado')
            ->assertSuccessful();

        $this->assertSame(
            '99.0000',
            DailyMetric::where('metric_date', Carbon::now()->subDays(500)->toDateString())->value('metric_value'),
            'O agregado anterior ao evento mais antigo precisa sobreviver.'
        );
    }

    public function test_rebuild_is_idempotent_when_run_twice(): void
    {
        $this->service()->record(EventName::PedidoCriado, eventUuid: (string) Str::orderedUuid());

        $this->artisan('customer-intelligence:rebuild-daily-metrics')->assertSuccessful();
        $primeira = DailyMetric::sum('metric_value');

        $this->artisan('customer-intelligence:rebuild-daily-metrics')->assertSuccessful();

        $this->assertSame($primeira, DailyMetric::sum('metric_value'));
    }

    // ─── Minimização ──────────────────────────────────────────────────────

    public function test_the_sanitizer_covers_the_sensitive_keys_the_project_can_produce(): void
    {
        $sanitizer = new PropertySanitizer;

        foreach (['cpf', 'CNPJ', 'documento', 'rg', 'senha', 'password', 'api_key', 'authorization',
            'credit_card', 'card_number', 'cvv', 'cartao', 'secret', 'user_token'] as $chave) {
            $this->assertTrue($sanitizer->isSensitive($chave), "{$chave} deveria ser redigida.");
        }

        foreach (['produto_id', 'pedido_id', 'valor_total', 'quantidade', 'transportadora',
            'codigo_rastreio', 'eixo', 'preco_unitario'] as $chave) {
            $this->assertFalse($sanitizer->isSensitive($chave), "{$chave} não é sensível.");
        }
    }

    public function test_no_ip_or_user_agent_is_ever_stored(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (rastreável)')->get('/')->assertOk();

        $sessao = VisitorSession::sole();
        $colunas = array_keys($sessao->getAttributes());

        foreach ($colunas as $coluna) {
            $this->assertStringNotContainsStringIgnoringCase('ip', $coluna === 'landing_url' ? 'n/a' : $coluna);
            $this->assertStringNotContainsStringIgnoringCase('user_agent', $coluna);
        }

        $this->assertFalse(
            str_contains(json_encode($sessao->getAttributes()), 'Mozilla'),
            'O user-agent não pode acabar gravado em nenhum campo.'
        );
    }

    public function test_aggregates_carry_no_individual_identity(): void
    {
        $user = User::factory()->create();
        $sessao = app(ResolveVisitorSession::class)('v-anon', 's-anon', $user->id);
        $this->service()->record(EventName::PedidoCriado, session: $sessao, eventUuid: (string) Str::orderedUuid());

        foreach (DailyMetric::all() as $metrica) {
            $conteudo = json_encode($metrica->getAttributes());
            $this->assertStringNotContainsString((string) $user->id, (string) $metrica->dimension_value);
            $this->assertStringNotContainsString('v-anon', $conteudo);
        }

        $this->assertNotContains(
            'visitor',
            array_keys(DailyMetric::first()->getAttributes()),
            'A tabela de agregados não tem coluna de identidade.'
        );
    }

    public function test_the_retention_policy_is_declared_in_config(): void
    {
        $this->assertSame(180, config('customer-intelligence-internal.retention.event_days'));
    }
}
