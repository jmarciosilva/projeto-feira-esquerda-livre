<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\CatalogIntelligenceServiceProvider;
use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\Providers\FakeCatalogAiProvider;
use App\CatalogIntelligence\Providers\NullCatalogAiProvider;
use App\CatalogIntelligence\Support\PromptGuard;
use App\Enums\ItemType;
use App\Services\CatalogAi\CatalogAiProviderSelector;
use App\Services\CatalogAi\CatalogAiSettings;
use App\Services\CatalogAi\OpenAiCatalogAiProvider;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

/**
 * CAT-10A — de onde vem o provider: configuração, seletor e binding. Revisto na CAT-10A.1.
 *
 * O `CatalogAiProviderSelector` devolve o adaptador real só com configuração
 * completa e válida, e o `Null` nas condições previstas: trava técnica ligada, nada
 * gravado, recurso desligado, provider não suportado, chave ou modelo ausentes,
 * prazo inválido. Um prazo acima de 8 s é limitado a 8 s.
 *
 * Desde a CAT-10A.1 a configuração é a que o painel grava, lida por
 * `CatalogAiSettings`. Aqui ela é substituída por um dublê que devolve qualquer
 * valor — inclusive os que a coluna tipada não guarda —, para exercitar o seletor
 * sozinho. A leitura do banco e a tela estão em `ConfiguracaoDoProviderNoPainelTest`.
 *
 * O que não é condição prevista não é tratado: o seletor não tem `try`, e um defeito
 * na resolução sobe.
 *
 * Sem banco e sem rede — o único caso que envia requisição usa `Http::fake`, e toda
 * outra requisição é recusada por `preventStrayRequests`.
 */
class SelecaoDoProviderTest extends TestCase
{
    /** A trava técnica como o `phpunit.xml` a entrega, antes de o teste desligá-la. */
    private mixed $travaDoPhpunit;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->travaDoPhpunit = config('services.catalog_ai.force_disabled');

        // O seletor só é exercitável com a trava desligada de propósito.
        config()->set('services.catalog_ai.force_disabled', false);
    }

    /**
     * @param  array<string, mixed>  $troca
     * @return array<string, mixed>
     */
    private function configValida(array $troca = []): array
    {
        return array_replace([
            'enabled' => true,
            'provider' => 'openai',
            'model' => 'modelo-de-teste',
            'api_key' => 'chave-de-teste-que-nao-vale-nada',
            'timeout' => 8,
        ], $troca);
    }

    /**
     * O que `CatalogAiSettings` devolveria; nulo é "nada gravado".
     *
     * @param  array<string, mixed>|null  $config
     */
    private function configurar(?array $config): void
    {
        $this->app->instance(CatalogAiSettings::class, new class($config) extends CatalogAiSettings
        {
            /** @param  array<string, mixed>|null  $config */
            public function __construct(private readonly ?array $config)
            {
                parent::__construct();
            }

            public function atual(): array
            {
                return $this->config ?? [];
            }
        });
    }

    /** Uma configuração que não pode ser lida: qualquer leitura é defeito. */
    private function configuracaoQueFalhaAoSerLida(): void
    {
        $this->app->instance(CatalogAiSettings::class, new class extends CatalogAiSettings
        {
            public function atual(): array
            {
                throw new RuntimeException('defeito de leitura da configuração');
            }
        });
    }

    private function selecionar(): CatalogAiProvider
    {
        return app(CatalogAiProviderSelector::class)->resolve();
    }

    /**
     * O prazo que o adaptador entrega de fato ao cliente HTTP, lido das opções da
     * requisição que o fake recebe.
     *
     * @return array{0: mixed, 1: mixed} O prazo total e o de conexão.
     */
    private function prazosEnviados(CatalogAiProvider $provider): array
    {
        $opcoes = [];

        Http::fake(function ($request, array $options) use (&$opcoes) {
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

        $provider->suggest((new PromptGuard)(ListingContext::paraItemNovo(ItemType::Produto, 'Tapete de crochê')));

        return [$opcoes['timeout'] ?? null, $opcoes['connect_timeout'] ?? null];
    }

    // ─── A trava técnica ──────────────────────────────────────────────────────

    /** O `phpunit.xml` força a trava: sem desligá-la de propósito, a suíte nunca fala com fora, nem com configuração válida. */
    public function test_a_trava_do_phpunit_isola_a_suite_mesmo_com_configuracao_valida(): void
    {
        $this->assertTrue(filter_var($this->travaDoPhpunit, FILTER_VALIDATE_BOOL), 'o phpunit.xml precisa forçar CATALOG_AI_FORCE_DISABLED=true');

        $this->configurar($this->configValida());
        config()->set('services.catalog_ai.force_disabled', $this->travaDoPhpunit);

        $this->assertInstanceOf(NullCatalogAiProvider::class, $this->selecionar());
        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));
    }

    /** @return array<string, array{0: mixed}> */
    public static function travasQueDesligam(): array
    {
        return [
            'verdadeira' => [true],
            'texto verdadeiro' => ['true'],
            'um' => ['1'],
            'ambígua' => ['talvez'],
        ];
    }

    /** Na dúvida, a trava desliga. */
    #[DataProvider('travasQueDesligam')]
    public function test_a_trava_ligada_resolve_o_null_mesmo_com_configuracao_valida(mixed $trava): void
    {
        $this->configurar($this->configValida());
        config()->set('services.catalog_ai.force_disabled', $trava);

        $this->assertInstanceOf(NullCatalogAiProvider::class, $this->selecionar());
        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));
    }

    /** @return array<string, array{0: mixed}> */
    public static function travasQueDeixamPassar(): array
    {
        return [
            'falsa' => [false],
            'texto falso' => ['false'],
            'zero' => ['0'],
            'ausente' => [null],
        ];
    }

    /** A trava só desliga: desligada, quem decide é a configuração gravada. */
    #[DataProvider('travasQueDeixamPassar')]
    public function test_a_trava_desligada_deixa_a_configuracao_decidir(mixed $trava): void
    {
        $this->configurar($this->configValida());
        config()->set('services.catalog_ai.force_disabled', $trava);

        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, $this->selecionar());
    }

    public function test_com_a_trava_ligada_a_configuracao_nem_e_lida(): void
    {
        $this->configuracaoQueFalhaAoSerLida();
        config()->set('services.catalog_ai.force_disabled', true);

        $this->assertInstanceOf(NullCatalogAiProvider::class, $this->selecionar());
    }

    // ─── Sem configuração, e com configuração válida ──────────────────────────

    public function test_configuracao_valida_resolve_o_adaptador_real_pelo_container(): void
    {
        $this->configurar($this->configValida());

        $provider = app(CatalogAiProvider::class);

        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, $provider);
        $this->assertNotInstanceOf(FakeCatalogAiProvider::class, $provider);
        $this->assertTrue($provider->isAvailable());
        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, $this->selecionar());
    }

    /** Sem `singleton`: a configuração lida é a do momento da resolução. */
    public function test_a_resolucao_acompanha_a_configuracao_do_momento(): void
    {
        $this->configurar($this->configValida());
        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, app(CatalogAiProvider::class));

        $this->configurar($this->configValida(['enabled' => false]));
        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));
    }

    public function test_o_nome_do_provider_aceita_maiusculas_e_espacos(): void
    {
        $this->configurar($this->configValida(['provider' => ' OpenAI ']));

        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, $this->selecionar());
    }

    /** As chaves operacionais que o `config/services.php` tinha na CAT-10A não decidem mais nada. */
    public function test_as_chaves_antigas_do_config_nao_participam_da_resolucao(): void
    {
        config()->set('services.catalog_ai', ['force_disabled' => false] + $this->configValida());
        $this->configurar(null);

        $this->assertInstanceOf(NullCatalogAiProvider::class, $this->selecionar(), 'o config antigo não liga');

        config()->set('services.catalog_ai', ['force_disabled' => false, 'enabled' => false, 'api_key' => null]);
        $this->configurar($this->configValida());

        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, $this->selecionar(), 'nem desliga');
    }

    // ─── As condições previstas resolvem o Null ───────────────────────────────

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function condicoesQueResolvemONull(): array
    {
        return [
            'desligado' => [['enabled' => false]],
            'desligado por texto' => [['enabled' => 'false']],
            'ligado ambíguo' => [['enabled' => 'talvez']],
            'ligado ausente' => [['enabled' => null]],
            'provider não suportado' => [['provider' => 'outro-fornecedor']],
            'provider ausente' => [['provider' => null]],
            'provider em branco' => [['provider' => '   ']],
            'chave ausente' => [['api_key' => null]],
            'chave vazia' => [['api_key' => '']],
            'chave em branco' => [['api_key' => '   ']],
            'modelo ausente' => [['model' => null]],
            'modelo vazio' => [['model' => '']],
            'prazo zero' => [['timeout' => 0]],
            'prazo negativo' => [['timeout' => -3]],
            'prazo não numérico' => [['timeout' => 'oito']],
            'prazo vazio' => [['timeout' => '']],
            'prazo infinito' => [['timeout' => '1e999']],
            'prazo de outro tipo' => [['timeout' => true]],
        ];
    }

    /** @param  array<string, mixed>  $troca */
    #[DataProvider('condicoesQueResolvemONull')]
    public function test_condicao_prevista_resolve_o_null(array $troca): void
    {
        $this->configurar($this->configValida($troca));

        $this->assertInstanceOf(NullCatalogAiProvider::class, $this->selecionar());
        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));
    }

    public function test_sem_configuracao_gravada_resolve_o_null(): void
    {
        $this->configurar(null);

        $this->assertInstanceOf(NullCatalogAiProvider::class, $this->selecionar());
    }

    // ─── O prazo ──────────────────────────────────────────────────────────────

    /** @return array<string, array{0: mixed, 1: float}> */
    public static function prazos(): array
    {
        return [
            'ausente vira o máximo' => [null, 8.0],
            'o máximo' => [8, 8.0],
            'abaixo do máximo' => [5, 5.0],
            'texto numérico' => ['3.5', 3.5],
            'acima do máximo é limitado' => [30, 8.0],
            'texto acima do máximo é limitado' => ['120', 8.0],
        ];
    }

    #[DataProvider('prazos')]
    public function test_o_prazo_enviado_ao_cliente_http_nunca_passa_de_oito_segundos(mixed $configurado, float $esperado): void
    {
        $this->configurar($this->configValida(['timeout' => $configurado]));

        [$total, $conexao] = $this->prazosEnviados($this->selecionar());

        $this->assertSame($esperado, $total, 'prazo total da tentativa');
        $this->assertSame($esperado, $conexao, 'prazo de conexão');
    }

    // ─── Defeito não vira "sem provider" ──────────────────────────────────────

    /** Nenhum `try`, `catch` ou `finally` no seletor, na leitura da configuração nem no ServiceProvider do módulo. */
    public function test_o_seletor_a_configuracao_e_o_binding_nao_capturam_nada(): void
    {
        $arquivos = [
            (new ReflectionClass(CatalogAiProviderSelector::class))->getFileName(),
            (new ReflectionClass(CatalogAiSettings::class))->getFileName(),
            (new ReflectionClass(CatalogIntelligenceServiceProvider::class))->getFileName(),
        ];

        foreach ($arquivos as $arquivo) {
            foreach (token_get_all(file_get_contents($arquivo)) as $token) {
                $this->assertFalse(
                    is_array($token) && in_array($token[0], [T_TRY, T_CATCH, T_FINALLY], true),
                    basename($arquivo).' passou a capturar exceção: defeito na resolução do provider não pode virar Null em silêncio',
                );
            }
        }
    }

    public function test_defeito_na_resolucao_sobe_e_nao_vira_null(): void
    {
        $this->app->bind(CatalogAiProviderSelector::class, fn () => throw new RuntimeException('defeito de construção'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('defeito de construção');

        app(CatalogAiProvider::class);
    }

    public function test_defeito_na_leitura_da_configuracao_sobe_e_nao_vira_null(): void
    {
        $this->configuracaoQueFalhaAoSerLida();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('defeito de leitura da configuração');

        app(CatalogAiProvider::class);
    }

    /** O fornecedor, o transporte, a credencial e a configuração moram fora do módulo — o domínio segue sem saber quem responde. */
    public function test_o_adaptador_o_seletor_e_a_configuracao_moram_fora_do_modulo(): void
    {
        foreach ([OpenAiCatalogAiProvider::class, CatalogAiProviderSelector::class, CatalogAiSettings::class] as $classe) {
            $this->assertStringNotContainsString(
                app_path('CatalogIntelligence'),
                (string) (new ReflectionClass($classe))->getFileName(),
                "{$classe} entrou no módulo — as varreduras de fornecedor e rede do domínio passariam a acusá-lo",
            );
        }

        $this->assertTrue((new ReflectionClass(OpenAiCatalogAiProvider::class))->implementsInterface(CatalogAiProvider::class));
    }
}
