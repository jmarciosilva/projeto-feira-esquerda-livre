<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\Providers\NullCatalogAiProvider;
use App\CatalogIntelligence\Support\PromptGuard;
use App\Enums\ItemType;
use App\Enums\UserRole;
use App\Livewire\Admin\Settings\CatalogAiSettingsForm;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CatalogAi\CatalogAiSettings;
use App\Services\CatalogAi\OpenAiCatalogAiProvider;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use SensitiveParameter;
use Tests\TestCase;

/**
 * CAT-10A.1 — o provider externo configurado pelo painel.
 *
 * Admin → Configurações → Inteligência Artificial grava ligado, provider, modelo, chave
 * e prazo em `site_settings`, e o `CatalogAiProviderSelector` os lê por
 * `CatalogAiSettings` a cada resolução. O `.env` guarda só a trava técnica, que desliga
 * e nunca liga.
 *
 * O que se prova: quem pode ver e mudar; que a chave fica criptografada e nunca vai ao
 * navegador — gravada, recém-digitada ou recusada pela validação —; substituir, remover
 * e manter a chave; e que a mudança vale na resolução seguinte, sem cache nem reinício.
 *
 * Sem rede: `preventStrayRequests`, e a única requisição é a do `Http::fake`.
 */
class ConfiguracaoDoProviderNoPainelTest extends TestCase
{
    use RefreshDatabase;

    private const CHAVE = 'sk-painel-CHAVE-GRAVADA-0123456789abcdef';

    private const CHAVE_NOVA = 'sk-painel-CHAVE-NOVA-9876543210fedcba';

    private const MODELO = 'modelo-do-painel';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Http::preventStrayRequests();
    }

    // ─── Cenário ──────────────────────────────────────────────────────────────

    private function comPapel(UserRole $papel): User
    {
        $usuario = User::factory()->create(['role' => $papel]);
        $usuario->assignRole($papel->spatieRole());

        return $usuario;
    }

    private function admin(): User
    {
        return $this->comPapel(UserRole::Admin);
    }

    private function gravar(bool $ativo = true, ?string $chave = self::CHAVE, ?string $modelo = self::MODELO, ?int $timeout = 8): void
    {
        app(CatalogAiSettings::class)->salvar($ativo, 'openai', $modelo, $timeout, $chave);
    }

    /** A trava técnica que o `phpunit.xml` força, desligada de propósito. */
    private function semTrava(): void
    {
        config()->set('services.catalog_ai.force_disabled', false);
    }

    private function tela(): Testable
    {
        return Livewire::actingAs($this->admin())->test(CatalogAiSettingsForm::class);
    }

    private function gravada(): ?SiteSetting
    {
        return SiteSetting::query()->find(1);
    }

    /** O que o navegador recebe do componente: o HTML, com o snapshot embutido, e o snapshot. */
    private function assertForaDoNavegador(string $segredo, Testable $componente): void
    {
        $this->assertStringNotContainsString($segredo, $componente->html(), 'a chave apareceu no HTML do componente');
        $this->assertStringNotContainsString($segredo, (string) json_encode($componente->snapshot), 'a chave apareceu no snapshot do Livewire');
    }

    // ─── Quem pode ────────────────────────────────────────────────────────────

    public function test_admin_abre_a_tela(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.catalog-ai'))
            ->assertOk()
            ->assertSee('Inteligência Artificial')
            ->assertSee('Nenhuma chave configurada.');
    }

    /** @return array<string, array{0: UserRole}> */
    public static function papeisQueNaoEditamConfiguracoes(): array
    {
        return [
            'gerente' => [UserRole::Gerente],
            'editor' => [UserRole::Editor],
            'supervisor' => [UserRole::Supervisor],
            'lojista' => [UserRole::Lojista],
        ];
    }

    #[DataProvider('papeisQueNaoEditamConfiguracoes')]
    public function test_quem_nao_edita_configuracoes_nao_abre_a_tela(UserRole $papel): void
    {
        $this->actingAs($this->comPapel($papel))
            ->get(route('admin.settings.catalog-ai'))
            ->assertForbidden();
    }

    #[DataProvider('papeisQueNaoEditamConfiguracoes')]
    public function test_quem_nao_edita_configuracoes_nao_monta_o_componente(UserRole $papel): void
    {
        Livewire::actingAs($this->comPapel($papel))
            ->test(CatalogAiSettingsForm::class)
            ->assertForbidden();
    }

    /** @return array<string, array{0: string}> */
    public static function acoes(): array
    {
        return [
            'salvar' => ['save'],
            'remover a chave' => ['removerChave'],
        ];
    }

    /** A rota não basta: cada ação do componente se autoriza de novo. */
    #[DataProvider('acoes')]
    public function test_cada_acao_exige_editar_configuracoes_mesmo_com_o_componente_montado(string $acao): void
    {
        $this->gravar(ativo: false);

        $componente = Livewire::actingAs($this->admin())
            ->test(CatalogAiSettingsForm::class)
            ->set('ativo', true)
            ->set('novaChave', self::CHAVE_NOVA);

        Livewire::actingAs($this->comPapel(UserRole::Gerente));

        $componente->call($acao)->assertForbidden();

        $gravada = $this->gravada();

        $this->assertSame(self::CHAVE, $gravada->catalog_ai_api_key, 'a chave não mudou');
        $this->assertFalse($gravada->catalog_ai_ativo, 'o provider não foi ligado');
    }

    public function test_o_item_de_menu_so_aparece_para_quem_edita_configuracoes(): void
    {
        $link = route('admin.settings.catalog-ai');

        $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee($link, false);

        foreach ([UserRole::Gerente, UserRole::Editor] as $papel) {
            $this->actingAs($this->comPapel($papel))
                ->get(route('admin.settings.edit'))
                ->assertOk()
                ->assertDontSee($link, false);
        }
    }

    // ─── A chave ──────────────────────────────────────────────────────────────

    public function test_a_chave_fica_criptografada_no_banco_e_fora_da_serializacao(): void
    {
        $this->tela()
            ->set('modelo', self::MODELO)
            ->set('novaChave', self::CHAVE)
            ->call('save')
            ->assertHasNoErrors();

        $guardada = DB::table('site_settings')->where('id', 1)->value('catalog_ai_api_key');

        $this->assertIsString($guardada);
        $this->assertStringNotContainsString(self::CHAVE, $guardada);
        $this->assertSame(self::CHAVE, Crypt::decryptString($guardada));

        $gravada = $this->gravada();

        $this->assertContains('catalog_ai_api_key', $gravada->getHidden());
        $this->assertArrayNotHasKey('catalog_ai_api_key', $gravada->toArray());
        $this->assertStringNotContainsString(self::CHAVE, $gravada->toJson());
    }

    public function test_a_chave_gravada_nunca_vai_ao_navegador(): void
    {
        $this->gravar();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.settings.catalog-ai'))
            ->assertOk()
            ->assertSee('Chave configurada.')
            ->assertDontSee(self::CHAVE, false);

        $componente = Livewire::actingAs($admin)
            ->test(CatalogAiSettingsForm::class)
            ->assertSet('chaveConfigurada', true)
            ->assertSet('novaChave', '')
            ->assertSet('ativo', true)
            ->assertSet('modelo', self::MODELO);

        $this->assertForaDoNavegador(self::CHAVE, $componente);

        $componente->set('timeout', '5')->call('save')->assertHasNoErrors();

        $this->assertForaDoNavegador(self::CHAVE, $componente);
    }

    public function test_a_chave_nova_nao_volta_ao_navegador_depois_de_salvar(): void
    {
        $componente = $this->tela()
            ->set('ativo', true)
            ->set('modelo', self::MODELO)
            ->set('timeout', '8')
            ->set('novaChave', self::CHAVE_NOVA)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('novaChave', '')
            ->assertSet('chaveConfigurada', true);

        $this->assertForaDoNavegador(self::CHAVE_NOVA, $componente);
        $this->assertSame(self::CHAVE_NOVA, $this->gravada()->catalog_ai_api_key);
    }

    public function test_a_chave_nova_nao_volta_ao_navegador_quando_a_validacao_falha(): void
    {
        $componente = $this->tela()
            ->set('ativo', true)
            ->set('modelo', '')
            ->set('timeout', '30')
            ->set('novaChave', self::CHAVE_NOVA)
            ->call('save')
            ->assertHasErrors(['modelo', 'timeout'])
            ->assertSet('novaChave', '');

        $this->assertForaDoNavegador(self::CHAVE_NOVA, $componente);
        $this->assertFalse(DB::table('site_settings')->whereNotNull('catalog_ai_api_key')->exists(), 'nada foi gravado');
    }

    public function test_substituir_a_chave(): void
    {
        $this->gravar();

        $this->tela()
            ->set('novaChave', self::CHAVE_NOVA)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(self::CHAVE_NOVA, $this->gravada()->catalog_ai_api_key);
    }

    /** `APP_KEY` trocada: a chave antiga não se lê mais, e é justamente quando precisa ser substituída. */
    public function test_substituir_uma_chave_que_nao_se_decripta_mais(): void
    {
        $this->gravar();
        DB::table('site_settings')->where('id', 1)->update(['catalog_ai_api_key' => 'nao-e-um-payload-criptografado']);

        $this->tela()
            ->assertSet('chaveConfigurada', true)
            ->set('novaChave', self::CHAVE_NOVA)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(self::CHAVE_NOVA, $this->gravada()->catalog_ai_api_key);
    }

    public function test_remover_a_chave_apaga_e_desativa_o_provider(): void
    {
        $this->semTrava();
        $this->gravar();

        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, app(CatalogAiProvider::class));

        $this->tela()
            ->call('removerChave')
            ->assertHasNoErrors()
            ->assertSet('chaveConfigurada', false)
            ->assertSet('ativo', false);

        $gravada = $this->gravada();

        $this->assertNull(DB::table('site_settings')->where('id', 1)->value('catalog_ai_api_key'));
        $this->assertFalse($gravada->catalog_ai_ativo);
        $this->assertSame(self::MODELO, $gravada->catalog_ai_modelo, 'o resto da configuração fica');

        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));
    }

    public function test_salvar_os_outros_campos_mantem_a_chave(): void
    {
        $this->gravar();

        $this->tela()
            ->set('modelo', 'outro-modelo')
            ->set('timeout', '5')
            ->call('save')
            ->assertHasNoErrors();

        $gravada = $this->gravada();

        $this->assertSame(self::CHAVE, $gravada->catalog_ai_api_key);
        $this->assertSame('outro-modelo', $gravada->catalog_ai_modelo);
        $this->assertSame(5, $gravada->catalog_ai_timeout);
    }

    // ─── Validação ────────────────────────────────────────────────────────────

    public function test_ativar_sem_chave_e_recusado(): void
    {
        $this->tela()
            ->set('ativo', true)
            ->set('modelo', self::MODELO)
            ->call('save')
            ->assertHasErrors(['novaChave']);

        $this->assertFalse(DB::table('site_settings')->where('catalog_ai_ativo', true)->exists());
    }

    public function test_ativar_sem_modelo_e_recusado(): void
    {
        $this->gravar(ativo: false, modelo: null);

        $this->tela()
            ->set('ativo', true)
            ->call('save')
            ->assertHasErrors(['modelo']);

        $this->assertFalse($this->gravada()->catalog_ai_ativo);
    }

    public function test_desativado_salva_sem_chave_e_sem_modelo(): void
    {
        $this->tela()
            ->set('ativo', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($this->gravada()->catalog_ai_ativo);
    }

    /** @return array<string, array{0: string}> */
    public static function timeoutsRecusados(): array
    {
        return [
            'zero' => ['0'],
            'nove' => ['9'],
            'negativo' => ['-1'],
            'fracionário' => ['2.5'],
            'texto' => ['oito'],
        ];
    }

    #[DataProvider('timeoutsRecusados')]
    public function test_timeout_fora_de_1_a_8_e_recusado(string $timeout): void
    {
        $this->tela()
            ->set('timeout', $timeout)
            ->call('save')
            ->assertHasErrors(['timeout']);

        $this->assertNull($this->gravada(), 'nada foi gravado');
    }

    /** @return array<string, array{0: string, 1: int|null}> */
    public static function timeoutsAceitos(): array
    {
        return [
            'um' => ['1', 1],
            'oito' => ['8', 8],
            'em branco vale o padrão' => ['', null],
        ];
    }

    #[DataProvider('timeoutsAceitos')]
    public function test_timeout_de_1_a_8_ou_em_branco_e_gravado(string $timeout, ?int $gravado): void
    {
        $this->tela()
            ->set('timeout', $timeout)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($gravado, $this->gravada()->catalog_ai_timeout);
    }

    public function test_modelo_com_espaco_e_provider_nao_suportado_sao_recusados(): void
    {
        $this->tela()
            ->set('modelo', 'modelo com espaco')
            ->set('provider', 'outro-fornecedor')
            ->call('save')
            ->assertHasErrors(['modelo', 'provider']);

        $this->assertNull($this->gravada(), 'nada foi gravado');
    }

    // ─── Resolução ────────────────────────────────────────────────────────────

    public function test_sem_configuracao_gravada_o_contrato_e_o_null_e_a_leitura_nao_escreve(): void
    {
        $this->semTrava();

        $escritas = [];

        DB::listen(function (QueryExecuted $consulta) use (&$escritas) {
            if (preg_match('/^\s*select\b/i', $consulta->sql) !== 1) {
                $escritas[] = $consulta->sql;
            }
        });

        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));

        $this->assertSame([], $escritas, 'ler a configuração não pode gravar');
        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_ativar_e_desativar_pelo_painel_vale_na_resolucao_seguinte(): void
    {
        $this->semTrava();
        $admin = $this->admin();

        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));

        Livewire::actingAs($admin)
            ->test(CatalogAiSettingsForm::class)
            ->set('ativo', true)
            ->set('modelo', self::MODELO)
            ->set('timeout', '8')
            ->set('novaChave', self::CHAVE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, app(CatalogAiProvider::class));

        Livewire::actingAs($admin)
            ->test(CatalogAiSettingsForm::class)
            ->set('ativo', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));

        // Nada passou por config: a operação não depende de limpar nem recriar cache.
        $this->assertSame(['force_disabled' => false], config('services.catalog_ai'));
    }

    public function test_o_adaptador_usa_o_modelo_a_chave_e_o_prazo_gravados_no_painel(): void
    {
        $this->semTrava();
        $this->gravar(timeout: 3);

        $enviada = null;
        $opcoes = [];

        Http::fake(function (Request $requisicao, array $options) use (&$enviada, &$opcoes) {
            $enviada = $requisicao;
            $opcoes = $options;

            return Http::response([
                'status' => 'completed',
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode(['suggested_name' => null, 'short_description' => null, 'description' => null, 'keywords' => []]),
                    ]],
                ]],
            ]);
        });

        app(CatalogAiProvider::class)->suggest((new PromptGuard)(ListingContext::paraItemNovo(ItemType::Produto, 'Tapete de crochê')));

        $this->assertInstanceOf(Request::class, $enviada);
        $this->assertSame('Bearer '.self::CHAVE, $enviada->header('Authorization')[0] ?? null);
        $this->assertSame(self::MODELO, $enviada->data()['model'] ?? null);
        $this->assertSame(3.0, $opcoes['timeout'] ?? null);
    }

    public function test_com_a_trava_tecnica_ligada_o_contrato_e_sempre_o_null_e_o_banco_nem_e_lido(): void
    {
        $this->gravar();
        config()->set('services.catalog_ai.force_disabled', true);

        $consultas = 0;

        DB::listen(function () use (&$consultas) {
            $consultas++;
        });

        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));
        $this->assertSame(0, $consultas, 'com a trava ligada, a configuração não é lida');
    }

    public function test_a_configuracao_antiga_do_ambiente_nao_participa_da_resolucao(): void
    {
        config()->set('services.catalog_ai', [
            'force_disabled' => false,
            'enabled' => true,
            'provider' => 'openai',
            'model' => self::MODELO,
            'api_key' => self::CHAVE,
            'timeout' => 8,
        ]);

        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class), 'sem nada gravado, o config antigo não liga o provider');

        $this->gravar();
        config()->set('services.catalog_ai.enabled', false);
        config()->set('services.catalog_ai.api_key', null);

        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, app(CatalogAiProvider::class), 'nem desliga o que o painel ligou');
    }

    /** D-4: chave que não se decripta com o provider ligado é defeito de infraestrutura, e não "sem provider". */
    public function test_chave_ilegivel_com_o_provider_ligado_sobe_como_defeito(): void
    {
        $this->semTrava();
        $this->gravar();
        DB::table('site_settings')->where('id', 1)->update(['catalog_ai_api_key' => 'nao-e-um-payload-criptografado']);

        $this->expectException(DecryptException::class);

        app(CatalogAiProvider::class);
    }

    public function test_chave_ilegivel_com_o_provider_desligado_nem_e_lida(): void
    {
        $this->semTrava();
        $this->gravar(ativo: false);
        DB::table('site_settings')->where('id', 1)->update(['catalog_ai_api_key' => 'nao-e-um-payload-criptografado']);

        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));
    }

    public function test_os_parametros_que_recebem_a_chave_em_texto_puro_sao_sensiveis(): void
    {
        $parametros = [
            [OpenAiCatalogAiProvider::class, '__construct', 'chave'],
            [CatalogAiSettings::class, 'salvar', 'novaChave'],
        ];

        foreach ($parametros as [$classe, $metodo, $nome]) {
            $encontrados = array_values(array_filter(
                (new ReflectionMethod($classe, $metodo))->getParameters(),
                fn ($parametro) => $parametro->getName() === $nome,
            ));

            $this->assertCount(1, $encontrados, "{$classe}::{$metodo} não tem \${$nome}");
            $this->assertNotEmpty(
                $encontrados[0]->getAttributes(SensitiveParameter::class),
                "{$classe}::{$metodo}(\${$nome}) precisa de #[\\SensitiveParameter]",
            );
        }
    }
}
