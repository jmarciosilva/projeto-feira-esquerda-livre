<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Models\AuditLog;
use App\Enums\UserRole;
use App\Livewire\Admin\CustomerIntelligence\AuditIndex;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tela de auditoria e a permissao propria dela (GOV-01D).
 *
 * A decisao de produto foi explicita: quem pode ver metricas NAO passa por isso
 * a ver quem olhou o que. Sao permissoes separadas.
 */
class AuditPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // Mesma garantia do resto do painel: sem rede.
        Http::preventStrayRequests();
    }

    private function comPapel(UserRole $papel): User
    {
        $user = User::factory()->create(['role' => $papel]);
        $user->assignRole($papel->spatieRole());

        return $user;
    }

    // ─── Permissão ────────────────────────────────────────────────────────

    public function test_the_audit_permission_exists_and_is_separate(): void
    {
        $this->assertContains('customer_intelligence.auditoria', RolePermissionSeeder::PERMISSIONS);
        $this->assertNotSame('customer_intelligence.visualizar', 'customer_intelligence.auditoria');
    }

    public function test_only_the_administrator_role_holds_it(): void
    {
        $papeis = RolePermissionSeeder::rolePermissions();

        $this->assertContains('customer_intelligence.auditoria', $papeis['administrador']);

        foreach (['gerente', 'supervisor', 'editor', 'lojista', 'cliente'] as $papel) {
            $this->assertNotContains(
                'customer_intelligence.auditoria',
                $papeis[$papel],
                "O papel [{$papel}] não deve ter acesso à auditoria."
            );
        }
    }

    public function test_admin_can_open_the_audit_screen(): void
    {
        $this->actingAs($this->comPapel(UserRole::Admin))
            ->get(route('admin.customer-intelligence.auditoria'))
            ->assertOk()
            ->assertSee('Auditoria');
    }

    /**
     * O caso central: gerente e supervisor veem o painel de metricas e mesmo
     * assim nao entram na auditoria.
     */
    public function test_manager_and_supervisor_see_the_dashboard_but_not_the_audit(): void
    {
        foreach ([UserRole::Gerente, UserRole::Supervisor] as $papel) {
            $user = $this->comPapel($papel);

            $this->actingAs($user)
                ->get(route('admin.customer-intelligence.dashboard'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('admin.customer-intelligence.auditoria'))
                ->assertForbidden();
        }
    }

    public function test_editor_is_forbidden(): void
    {
        $this->actingAs($this->comPapel(UserRole::Editor))
            ->get(route('admin.customer-intelligence.auditoria'))
            ->assertForbidden();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('admin.customer-intelligence.auditoria'))->assertRedirect();
    }

    /**
     * Autorizacao no servidor em dois niveis: a rota e o proprio componente.
     * Montar o componente por outro caminho tambem tem de esbarrar na
     * permissao.
     */
    public function test_the_component_authorizes_on_its_own(): void
    {
        Livewire::actingAs($this->comPapel(UserRole::Gerente))
            ->test(AuditIndex::class)
            ->assertForbidden();
    }

    // ─── Conteúdo da tela ─────────────────────────────────────────────────

    public function test_the_screen_lists_the_recorded_actions(): void
    {
        $admin = $this->comPapel(UserRole::Admin);

        app(\App\CustomerIntelligence\Actions\RecordAuditLog::class)(
            AuditAction::VisitorView,
            'ci_visitor',
            42,
            $admin->id,
        );

        Livewire::actingAs($admin)
            ->test(AuditIndex::class)
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee(AuditAction::VisitorView->label())
            ->assertSee('ci_visitor#42');
    }

    public function test_the_screen_filters_by_action(): void
    {
        $admin = $this->comPapel(UserRole::Admin);
        $registrar = app(\App\CustomerIntelligence\Actions\RecordAuditLog::class);

        $registrar(AuditAction::VisitorView, 'ci_visitor', 1, $admin->id);
        $registrar(AuditAction::ForgetUser, 'user', 7, $admin->id);

        $componente = Livewire::actingAs($admin)
            ->test(AuditIndex::class)
            ->set('action', AuditAction::ForgetUser->value);

        $registros = $componente->viewData('registros');

        $this->assertSame(1, $registros->total());
        $this->assertSame(AuditAction::ForgetUser, $registros->first()->action);
    }

    /**
     * A paginacao so aparece a partir da segunda pagina, entao um erro nela
     * ficaria escondido ate a trilha crescer. Este teste a exercita de saida.
     */
    public function test_the_screen_paginates(): void
    {
        $admin = $this->comPapel(UserRole::Admin);
        $registrar = app(\App\CustomerIntelligence\Actions\RecordAuditLog::class);

        for ($i = 0; $i < 60; $i++) {
            $registrar(AuditAction::DashboardView, null, null, $admin->id);
        }

        $componente = Livewire::actingAs($admin)->test(AuditIndex::class)->assertOk();

        $registros = $componente->viewData('registros');

        $this->assertSame(61, $registros->total(), 'Os 60 mais a linha da própria abertura.');
        $this->assertSame(50, $registros->count(), 'Cinquenta por página.');
        $this->assertTrue($registros->hasPages());
    }

    public function test_the_screen_announces_the_retention_window(): void
    {
        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(AuditIndex::class)
            ->assertOk()
            ->assertSee('730 dias');
    }

    /**
     * A trilha e append-only tambem pela interface: nao existe acao de editar,
     * apagar ou exportar no componente.
     */
    public function test_the_screen_offers_no_way_to_change_the_trail(): void
    {
        $metodos = get_class_methods(AuditIndex::class);

        foreach (['delete', 'destroy', 'update', 'edit', 'export', 'purge', 'clear'] as $proibido) {
            $this->assertNotContains($proibido, $metodos);
        }
    }

    public function test_a_scheduled_entry_shows_as_system(): void
    {
        app(\App\CustomerIntelligence\Actions\RecordAuditLog::class)(AuditAction::PruneEvents);

        $this->assertNull(AuditLog::sole()->user_id);

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(AuditIndex::class)
            ->assertOk()
            ->assertSee('Sistema');
    }
}
