<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Actions\ResolveVisitorSession;
use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Models\AuditLog;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\Enums\UserRole;
use App\Livewire\Admin\CustomerIntelligence\AuditIndex;
use App\Livewire\Admin\CustomerIntelligence\DashboardShow;
use App\Livewire\Admin\CustomerIntelligence\EventIndex;
use App\Livewire\Admin\CustomerIntelligence\VisitorIndex;
use App\Livewire\Admin\CustomerIntelligence\VisitorShow;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithConsent;
use Tests\TestCase;

/**
 * Trilha de auditoria administrativa (GOV-01C).
 *
 * O que se verifica aqui: que a trilha registra os acessos, que registra UMA
 * linha por acesso, que nao guarda dado que nao deveria guardar, e que ela e
 * completamente independente do analytics que audita.
 */
class AuditLogTest extends TestCase
{
    use InteractsWithConsent, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $admin->assignRole('administrador');

        return $admin;
    }

    private function visitante(string $uuid = 'v-auditado'): Visitor
    {
        return app(ResolveVisitorSession::class)($uuid, 'sessao-'.$uuid)->visitor;
    }

    // ─── Uma linha por acesso ─────────────────────────────────────────────

    public function test_opening_the_dashboard_records_exactly_one_line(): void
    {
        Livewire::actingAs($this->admin())->test(DashboardShow::class)->assertOk();

        $this->assertSame(1, AuditLog::where('action', AuditAction::DashboardView)->count());
    }

    public function test_opening_the_event_list_records_exactly_one_line(): void
    {
        Livewire::actingAs($this->admin())->test(EventIndex::class)->assertOk();

        $this->assertSame(1, AuditLog::where('action', AuditAction::EventsView)->count());
    }

    public function test_opening_the_visitor_list_records_exactly_one_line(): void
    {
        Livewire::actingAs($this->admin())->test(VisitorIndex::class)->assertOk();

        $this->assertSame(1, AuditLog::where('action', AuditAction::VisitorsView)->count());
    }

    public function test_opening_the_audit_screen_records_exactly_one_line(): void
    {
        Livewire::actingAs($this->admin())->test(AuditIndex::class)->assertOk();

        $this->assertSame(1, AuditLog::where('action', AuditAction::AuditView)->count());
    }

    /**
     * O risco que a GOV-01 pediu para cobrir explicitamente: auditar no lugar
     * errado transformaria uma visita em dezenas de linhas. Filtrar, buscar e
     * paginar reexecutam `render()` — nenhum deles pode gerar registro novo.
     */
    public function test_livewire_interactions_never_multiply_the_audit_lines(): void
    {
        $componente = Livewire::actingAs($this->admin())->test(EventIndex::class);

        $componente->set('search', 'maria')
            ->set('eventName', 'produto.visualizado')
            ->set('period', '7')
            ->set('search', 'joao')
            ->call('$refresh')
            ->assertOk();

        $this->assertSame(
            1,
            AuditLog::where('action', AuditAction::EventsView)->count(),
            'Uma abertura de tela é um registro, por mais que se interaja com ela.'
        );
    }

    public function test_paginating_the_visitor_list_never_multiplies_the_audit_lines(): void
    {
        $componente = Livewire::actingAs($this->admin())->test(VisitorIndex::class);

        $componente->call('gotoPage', 2)->call('$refresh')->assertOk();

        $this->assertSame(1, AuditLog::where('action', AuditAction::VisitorsView)->count());
    }

    /**
     * Duas aberturas sao dois acessos, e devem aparecer como dois. O oposto do
     * caso acima — deduplicar acessos distintos esconderia consulta repetida.
     */
    public function test_two_separate_visits_record_two_lines(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(DashboardShow::class);
        Livewire::actingAs($admin)->test(DashboardShow::class);

        $this->assertSame(2, AuditLog::where('action', AuditAction::DashboardView)->count());
    }

    // ─── Quem e sobre o quê ───────────────────────────────────────────────

    public function test_the_line_records_who_did_it(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(DashboardShow::class);

        $this->assertSame(
            $admin->id,
            AuditLog::where('action', AuditAction::DashboardView)->sole()->user_id
        );
    }

    /**
     * Abrir o painel e abrir tres telas de uma vez: o resumo, a lista de
     * visitantes e a de eventos, todas embutidas na mesma pagina. Cada uma
     * gera a sua linha, porque cada uma e um acesso a um conjunto diferente de
     * dado — e nenhuma delas gera mais de uma.
     */
    public function test_the_dashboard_records_one_line_per_embedded_screen(): void
    {
        Livewire::actingAs($this->admin())->test(DashboardShow::class)->assertOk();

        $porAcao = AuditLog::query()->pluck('action')->countBy(fn ($acao) => $acao->value);

        $this->assertSame(1, $porAcao[AuditAction::DashboardView->value] ?? 0);
        $this->assertSame(1, $porAcao[AuditAction::VisitorsView->value] ?? 0);
        $this->assertSame(1, $porAcao[AuditAction::EventsView->value] ?? 0);
        $this->assertSame(3, AuditLog::count());
    }

    public function test_opening_a_visitor_records_the_resource_by_internal_key(): void
    {
        $visitor = $this->visitante();

        Livewire::actingAs($this->admin())
            ->test(VisitorShow::class, ['visitor' => $visitor->visitor_uuid])
            ->assertOk();

        $linha = AuditLog::where('action', AuditAction::VisitorView)->sole();

        $this->assertSame('ci_visitor', $linha->resource_type);
        $this->assertSame((string) $visitor->id, $linha->resource_id);

        // A chave interna, e nao o identificador publico do visitante.
        $this->assertNotSame($visitor->visitor_uuid, $linha->resource_id);
    }

    public function test_an_unknown_visitor_records_nothing(): void
    {
        Livewire::actingAs($this->admin())
            ->test(VisitorShow::class, ['visitor' => 'nao-existe']);

        $this->assertSame(
            0,
            AuditLog::where('action', AuditAction::VisitorView)->count(),
            'Não houve acesso a dado nenhum para registrar.'
        );
    }

    // ─── Minimização ──────────────────────────────────────────────────────

    /**
     * A GOV-01 listou o que a trilha nao pode guardar. A forma mais duravel de
     * garantir isso e a propria tabela nao ter onde guardar.
     */
    public function test_the_table_has_no_column_for_forbidden_data(): void
    {
        $colunas = Schema::getColumnListing('ci_audit_logs');

        $this->assertSame(
            ['id', 'user_id', 'action', 'resource_type', 'resource_id', 'created_at'],
            $colunas
        );

        foreach (['metadata', 'meta', 'payload', 'properties', 'ip', 'ip_address', 'user_agent', 'email', 'name', 'visitor_uuid', 'session_uuid', 'cookie'] as $proibida) {
            $this->assertNotContains($proibida, $colunas, "A coluna [{$proibida}] não deve existir na auditoria.");
        }
    }

    public function test_the_audit_table_is_append_only(): void
    {
        $this->assertNull(AuditLog::UPDATED_AT);
        $this->assertNotContains('updated_at', Schema::getColumnListing('ci_audit_logs'));
    }

    // ─── Auditoria não é analytics ────────────────────────────────────────

    /**
     * A separacao que a GOV-01 exigiu: auditar nunca pode gerar evento
     * comportamental. Fossem a mesma coisa, a trilha entraria nos numeros do
     * painel e ficaria sujeita ao consentimento de quem esta sendo auditado.
     */
    public function test_auditing_never_produces_a_tracked_event(): void
    {
        $this->acceptingAnalytics();

        Livewire::actingAs($this->admin())->test(DashboardShow::class);
        Livewire::actingAs($this->admin())->test(AuditIndex::class);

        $this->assertGreaterThan(0, AuditLog::count());
        $this->assertSame(0, TrackedEvent::count(), 'Auditoria não alimenta ci_events.');
    }

    public function test_the_audit_writer_never_touches_the_tracking_facade(): void
    {
        $fonte = (string) file_get_contents(app_path('CustomerIntelligence/Actions/RecordAuditLog.php'));

        $this->assertStringNotContainsString('CustomerIntelligence::', $fonte);
        $this->assertStringNotContainsString('track(', $fonte);
        $this->assertStringNotContainsString('TrackedEvent', $fonte);
    }

    /**
     * A auditoria e sobre o que a pessoa administrativa faz com dado de
     * terceiros. A preferencia de analytics dela no proprio navegador nao tem
     * relacao com isso — e deixar as duas se tocarem permitiria a quem e
     * auditado desligar a propria auditoria.
     */
    public function test_the_audit_works_even_when_the_admin_refused_analytics(): void
    {
        $this->rejectingAnalytics();

        Livewire::actingAs($this->admin())->test(DashboardShow::class)->assertOk();

        $this->assertSame(1, AuditLog::where('action', AuditAction::DashboardView)->count());
        $this->assertSame(0, TrackedEvent::count());
    }

    // ─── Comandos sensíveis ───────────────────────────────────────────────

    public function test_forgetting_a_user_is_audited(): void
    {
        $user = User::factory()->create();
        app(ResolveVisitorSession::class)('v-esquecido', 's-esquecido', $user->id);

        $this->artisan('customer-intelligence:forget-user '.$user->id)
            ->expectsConfirmation('Confirmar?', 'yes')
            ->assertSuccessful();

        $linha = AuditLog::where('action', AuditAction::ForgetUser)->sole();

        $this->assertSame('user', $linha->resource_type);
        $this->assertSame((string) $user->id, $linha->resource_id);
    }

    /**
     * Um cancelamento nao pode aparecer na trilha como se tivesse acontecido —
     * seria pior do que nao registrar nada.
     */
    public function test_cancelling_forget_user_records_nothing(): void
    {
        $user = User::factory()->create();
        app(ResolveVisitorSession::class)('v-mantido', 's-mantido', $user->id);

        $this->artisan('customer-intelligence:forget-user '.$user->id)
            ->expectsConfirmation('Confirmar?', 'no')
            ->assertFailed();

        $this->assertSame(0, AuditLog::where('action', AuditAction::ForgetUser)->count());
    }

    public function test_pruning_events_is_audited_only_when_something_is_removed(): void
    {
        $this->withoutMockingConsoleOutput();

        // Nada fora da janela: nada removido, nada auditado.
        Artisan::call('customer-intelligence:prune-events');
        $this->assertSame(0, AuditLog::where('action', AuditAction::PruneEvents)->count());

        $this->acceptingAnalytics();
        app(CustomerIntelligenceService::class)->record(
            EventName::ProdutoVisualizado,
            occurredAt: now()->subDays(400),
            eventUuid: (string) \Illuminate\Support\Str::orderedUuid(),
        );

        // Dry-run tambem nao audita: nada foi alterado.
        Artisan::call('customer-intelligence:prune-events', ['--dry-run' => true]);
        $this->assertSame(0, AuditLog::where('action', AuditAction::PruneEvents)->count());

        Artisan::call('customer-intelligence:prune-events');
        $this->assertSame(1, AuditLog::where('action', AuditAction::PruneEvents)->count());
    }

    public function test_a_scheduled_run_records_no_actor(): void
    {
        $this->acceptingAnalytics();
        app(CustomerIntelligenceService::class)->record(
            EventName::ProdutoVisualizado,
            occurredAt: now()->subDays(400),
            eventUuid: (string) \Illuminate\Support\Str::orderedUuid(),
        );

        $this->withoutMockingConsoleOutput();
        Artisan::call('customer-intelligence:prune-events');

        $this->assertNull(
            AuditLog::where('action', AuditAction::PruneEvents)->sole()->user_id,
            'Execução agendada não tem ninguém por trás dela.'
        );
    }

    public function test_rebuilding_metrics_is_audited(): void
    {
        $this->acceptingAnalytics();
        app(CustomerIntelligenceService::class)->record(
            EventName::ProdutoVisualizado,
            eventUuid: (string) \Illuminate\Support\Str::orderedUuid(),
        );

        $this->withoutMockingConsoleOutput();
        Artisan::call('customer-intelligence:rebuild-daily-metrics');

        $this->assertSame(1, AuditLog::where('action', AuditAction::RebuildMetrics)->count());
    }

    public function test_rebuilding_with_nothing_to_rebuild_is_not_audited(): void
    {
        $this->withoutMockingConsoleOutput();
        Artisan::call('customer-intelligence:rebuild-daily-metrics');

        $this->assertSame(0, AuditLog::where('action', AuditAction::RebuildMetrics)->count());
    }
}
