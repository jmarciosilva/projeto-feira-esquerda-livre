<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\GuardedPrompt;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Exceptions\CatalogAiProviderException;
use App\CatalogIntelligence\Providers\FakeCatalogAiProvider;
use App\CatalogIntelligence\Providers\NullCatalogAiProvider;
use App\CatalogIntelligence\Support\PromptGuard;
use App\Enums\ItemType;
use App\Services\CatalogAi\CatalogAiSettings;
use App\Services\CatalogAi\OpenAiCatalogAiProvider;
use Tests\TestCase;

/**
 * CAT-06D — o contrato do provider, e as duas implementações que não falam com
 * a rede.
 *
 * ## O que este arquivo substitui
 *
 * A antiga trava do teste do assistente contra interface de provider externo
 * prendia quatro nomes. Três chegaram nesta subfase, por decisão, e as
 * linguetas correspondentes viraram as garantias positivas daqui: em vez de
 * *"o contrato não existe"*, agora se afirma *"o contrato existe, e nem ele nem
 * as implementações sabem falar com fora"*.
 *
 * `EmbeddingProvider` continua preso lá, porque a **B-3** segue sem decisão.
 *
 * ## O que a CAT-06G mudou aqui
 *
 * `suggest()` passou a receber `GuardedPrompt`, e não `ListingContext`
 * (D-CAT-06G-1); a falha esperada da fronteira ganhou tipo,
 * `CatalogAiProviderException` (D-CAT-06G-7); e o contrato ganhou binding padrão,
 * o `Null` (D-CAT-06G-9). Os casos que prendiam a forma antiga foram trocados
 * pela forma nova — nenhum foi apagado.
 *
 * ## Sem banco, sem rede, sem dublê de terceiro
 *
 * Nenhum caso aqui usa `RefreshDatabase` — o contrato e as duas implementações
 * são puros. Se um dia algum deles precisar de banco, este arquivo quebra, e a
 * quebra é o recado.
 */
class ContratosDeProviderTest extends TestCase
{
    private function prompt(string $nome = 'Tapete de crochê'): GuardedPrompt
    {
        return (new PromptGuard)(ListingContext::paraItemNovo(ItemType::Produto, $nome));
    }

    /** @return array<int, string> Os arquivos da fronteira: os três da CAT-06D e a exceção da CAT-06G. */
    private function arquivosDaFronteira(): array
    {
        return [
            app_path('CatalogIntelligence/Contracts/CatalogAiProvider.php'),
            app_path('CatalogIntelligence/Providers/NullCatalogAiProvider.php'),
            app_path('CatalogIntelligence/Providers/FakeCatalogAiProvider.php'),
            app_path('CatalogIntelligence/Exceptions/CatalogAiProviderException.php'),
        ];
    }

    // ─── O contrato ───────────────────────────────────────────────────────────

    /**
     * A assinatura que a CAT-06G deixou: o provider recebe o prompt protegido.
     *
     * Até a CAT-06D o parâmetro de `suggest()` era `ListingContext`, transcrito da
     * §3.3 da especificação. O caso continua travando os dois métodos, nem mais
     * nem menos — só que agora com o tipo que garante que S-1 e C-2 já foram
     * aplicados antes da fronteira.
     */
    public function test_o_contrato_existe_e_recebe_o_prompt_protegido(): void
    {
        $this->assertTrue(interface_exists(CatalogAiProvider::class));

        $reflexao = new \ReflectionClass(CatalogAiProvider::class);

        $this->assertSame(
            ['isAvailable', 'suggest'],
            collect($reflexao->getMethods())->map(fn ($m) => $m->name)->sort()->values()->all(),
            'a interface tem exatamente dois métodos — nem mais, nem menos',
        );

        $this->assertSame('bool', (string) $reflexao->getMethod('isAvailable')->getReturnType());
        $this->assertSame(ListingSuggestion::class, (string) $reflexao->getMethod('suggest')->getReturnType());
        $this->assertSame(
            GuardedPrompt::class,
            (string) $reflexao->getMethod('suggest')->getParameters()[0]->getType(),
            'o provider recebe o prompt protegido (D-CAT-06G-1) — ListingContext não é fronteira segura de transporte',
        );

        foreach (['isAvailable', 'suggest'] as $metodo) {
            $this->assertStringContainsString(
                '@throws CatalogAiProviderException',
                (string) $reflexao->getMethod($metodo)->getDocComment(),
                "o contrato de {$metodo}() deixou de declarar a falha esperada da fronteira",
            );
        }
    }

    /** O contexto cru não atravessa mais a fronteira — nem por descuido de quem chama. */
    public function test_o_contrato_nao_aceita_mais_o_contexto_cru(): void
    {
        $this->expectException(\TypeError::class);

        (new NullCatalogAiProvider)->suggest(ListingContext::paraItemNovo(ItemType::Produto, 'Tapete'));
    }

    public function test_as_duas_implementacoes_cumprem_o_contrato(): void
    {
        $this->assertInstanceOf(CatalogAiProvider::class, new NullCatalogAiProvider);
        $this->assertInstanceOf(CatalogAiProvider::class, FakeCatalogAiProvider::disponivel());
    }

    /**
     * Uma exceção só, tipada e sem hierarquia (D-CAT-06G-7).
     *
     * Todo motivo de falha externa leva ao mesmo desfecho; uma subclasse não teria
     * o que distinguir para o assistente.
     */
    public function test_a_falha_esperada_da_fronteira_tem_um_tipo_so(): void
    {
        $reflexao = new \ReflectionClass(CatalogAiProviderException::class);

        $this->assertTrue($reflexao->isSubclassOf(\RuntimeException::class));
        $this->assertTrue($reflexao->isFinal(), 'sem hierarquia: toda falha externa leva ao mesmo desfecho');
        $this->assertInstanceOf(CatalogAiProviderException::class, CatalogAiProviderException::tempoEsgotado());
    }

    // ─── Null nunca lança ─────────────────────────────────────────────────────

    /**
     * A invariante da spec §3.3, exercitada de todas as formas que um chamador
     * distraído poderia tentar — inclusive as que ele não deveria.
     */
    public function test_o_null_nunca_lanca_em_nenhum_caminho(): void
    {
        $null = new NullCatalogAiProvider;

        $this->assertFalse($null->isAvailable());

        // Chamado sem checar isAvailable() — erro de chamador, nunca exceção.
        $sugestao = $null->suggest($this->prompt());
        $this->assertInstanceOf(ListingSuggestion::class, $sugestao);
        $this->assertFalse($sugestao->temAlgoAPropor());

        // Repetido, e com prompts de formatos diferentes.
        foreach (['', 'x', str_repeat('nome muito longo ', 500), "quebra\nde linha", '<script>'] as $nome) {
            $this->assertInstanceOf(
                ListingSuggestion::class,
                $null->suggest($this->prompt($nome)),
                "o Null lançou para o nome {$nome} — a invariante da §3.3 é 'nunca lança'",
            );
        }

        // isAvailable() é estável: não há estado que o faça virar true.
        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($null->isAvailable());
        }
    }

    public function test_o_null_devolve_sempre_a_mesma_coisa(): void
    {
        $null = new NullCatalogAiProvider;

        $this->assertEquals(
            $null->suggest($this->prompt())->toArray(),
            $null->suggest($this->prompt('Outro item'))->toArray(),
            'a resposta do Null não depende do prompt: não há nada para depender dele',
        );
    }

    // ─── Fake é determinístico ────────────────────────────────────────────────

    public function test_o_fake_e_deterministico_entre_chamadas_e_instancias(): void
    {
        $primeira = FakeCatalogAiProvider::disponivel()->suggest($this->prompt())->toArray();

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(
                $primeira,
                FakeCatalogAiProvider::disponivel()->suggest($this->prompt())->toArray(),
                'o Fake variou entre instâncias — determinismo é a característica principal dele',
            );
        }

        $mesmaInstancia = FakeCatalogAiProvider::disponivel();
        $this->assertSame(
            $mesmaInstancia->suggest($this->prompt())->toArray(),
            $mesmaInstancia->suggest($this->prompt())->toArray(),
            'o Fake variou entre chamadas da mesma instância',
        );
    }

    public function test_prompts_diferentes_dao_respostas_diferentes_mas_estaveis(): void
    {
        $a = FakeCatalogAiProvider::disponivel()->suggest($this->prompt('Tapete'));
        $b = FakeCatalogAiProvider::disponivel()->suggest($this->prompt('Colar'));

        $this->assertNotSame($a->description, $b->description, 'a resposta deriva do prompt');
        $this->assertSame(
            $a->toArray(),
            FakeCatalogAiProvider::disponivel()->suggest($this->prompt('Tapete'))->toArray(),
        );
    }

    public function test_o_fake_simula_os_estados_de_ausencia_sucesso_e_falha(): void
    {
        // Ausente.
        $this->assertFalse(FakeCatalogAiProvider::indisponivel()->isAvailable());

        // Responde bem, e a procedência é externa.
        $boa = FakeCatalogAiProvider::disponivel()->suggest($this->prompt());
        $this->assertSame(SuggestionSource::External, $boa->source);

        // Falha — com a exceção tipada da fronteira, e não com uma qualquer.
        $this->expectException(CatalogAiProviderException::class);
        FakeCatalogAiProvider::queFalha()->suggest($this->prompt());
    }

    /**
     * B-5 simulado sem relógio: o Fake lança na hora a exceção que o adaptador
     * lançaria depois do prazo. O que se testa é a reação, não a espera.
     */
    public function test_o_fake_simula_prazo_esgotado_sem_esperar(): void
    {
        $fake = FakeCatalogAiProvider::queEsgotaOTempo();
        $inicio = hrtime(true);

        try {
            $fake->suggest($this->prompt());
            $this->fail('o prazo esgotado deveria ter sido sinalizado');
        } catch (CatalogAiProviderException $falha) {
            $this->assertSame(CatalogAiProviderException::tempoEsgotado()->getMessage(), $falha->getMessage());
        }

        $this->assertLessThan(1_000_000_000, hrtime(true) - $inicio, 'o Fake simula o prazo, não espera por ele');
        $this->assertSame(1, $fake->chamadas(), 'a tentativa que falhou conta como tentativa');
    }

    public function test_o_fake_devolve_a_resposta_fixada_inclusive_invalida(): void
    {
        // Resposta fora do contrato, para exercitar a B-4.
        $invalida = new ListingSuggestion(
            suggestedName: null,
            shortDescription: '   ',
            description: null,
            source: SuggestionSource::External,
        );

        $this->assertSame(
            '   ',
            FakeCatalogAiProvider::respondendo($invalida)->suggest($this->prompt())->shortDescription,
        );
    }

    public function test_o_fake_conta_as_chamadas(): void
    {
        $fake = FakeCatalogAiProvider::disponivel();

        $this->assertSame(0, $fake->chamadas(), 'nasce sem ter sido consultado');

        $fake->suggest($this->prompt());
        $fake->suggest($this->prompt());

        $this->assertSame(2, $fake->chamadas());
    }

    /** O que chegou à fronteira fica visível do lado de quem recebe — é por aqui que a 06G vê a redação. */
    public function test_o_fake_guarda_os_prompts_recebidos_na_ordem(): void
    {
        $fake = FakeCatalogAiProvider::disponivel();
        $primeiro = $this->prompt('Tapete');
        $segundo = $this->prompt('Colar');

        $this->assertSame([], $fake->promptsRecebidos());

        $fake->suggest($primeiro);
        $fake->suggest($segundo);

        $this->assertSame([$primeiro, $segundo], $fake->promptsRecebidos());
        $this->assertSame(count($fake->promptsRecebidos()), $fake->chamadas());
    }

    public function test_o_indisponivel_nao_e_consultado_por_acidente(): void
    {
        $fake = FakeCatalogAiProvider::indisponivel();

        $this->assertFalse($fake->isAvailable());
        $this->assertSame(0, $fake->chamadas());
    }

    // ─── Nenhum dos arquivos da fronteira fala com fora ───────────────────────

    public function test_nenhum_arquivo_da_fronteira_importa_cliente_http(): void
    {
        foreach ($this->arquivosDaFronteira() as $arquivo) {
            $conteudo = file_get_contents($arquivo);

            foreach (['Illuminate\\Support\\Facades\\Http', 'GuzzleHttp', 'curl_init', 'file_get_contents', 'fsockopen', 'stream_context_create'] as $marca) {
                $this->assertStringNotContainsString(
                    $marca,
                    $conteudo,
                    basename($arquivo)." contém \"{$marca}\": a CAT-06 entrega contrato, Fake e Null — ".
                    'nenhuma implementação real, e nenhum texto sai da aplicação ao fim dela.',
                );
            }
        }
    }

    /**
     * Nenhum nome de fornecedor, em código **ou** em comentário.
     *
     * A varredura é sobre o arquivo inteiro, e não só sobre o código
     * executável, porque um nome de fornecedor num docblock também é
     * conhecimento vazando para o domínio — a spec diz que ele *"não conhece
     * OpenAI, Anthropic, Gemini nem nome de modelo"*, e docblock é onde esse
     * conhecimento entraria primeiro, com a melhor das intenções.
     *
     * Escrever a lista aqui é seguro: este arquivo é de teste, e a varredura de
     * `FronteiraDePromptTest` cobre só `app/CatalogIntelligence`.
     */
    public function test_nenhum_arquivo_da_fronteira_nomeia_fornecedor_real(): void
    {
        $fornecedores = [
            'OpenAI', 'openai', 'Anthropic', 'anthropic', 'Claude', 'GPT',
            'Gemini', 'gemini', 'Bedrock', 'Ollama', 'Mistral', 'Cohere',
            'HuggingFace', 'Azure', 'Vertex',
        ];

        foreach ($this->arquivosDaFronteira() as $arquivo) {
            $conteudo = file_get_contents($arquivo);

            foreach ($fornecedores as $nome) {
                $this->assertStringNotContainsString(
                    $nome,
                    $conteudo,
                    basename($arquivo)." nomeia \"{$nome}\" — o domínio não conhece fornecedor, ".
                    'nem em string, nem em valor, nem em comentário.',
                );
            }
        }
    }

    public function test_nenhum_arquivo_da_fronteira_le_credencial_ou_endpoint(): void
    {
        foreach ($this->arquivosDaFronteira() as $arquivo) {
            $conteudo = file_get_contents($arquivo);

            foreach (['env(', 'api_key', 'apiKey', 'secret', 'Bearer', 'https://', 'http://'] as $marca) {
                $this->assertStringNotContainsString(
                    $marca,
                    $conteudo,
                    basename($arquivo)." contém \"{$marca}\": credencial e endpoint não entram na CAT-06 ".
                    '(D-CAT-06B-2); se um dia houver, moram em config/services.php.',
                );
            }
        }
    }

    /**
     * O caminho externo não conhece oferta, expositor, o cadastro, request nem
     * Customer Intelligence (H-06).
     *
     * **Quais arquivos.** Os que existem para a consulta externa ou cujos valores
     * atravessam a fronteira: o contrato, as duas implementações e a exceção; o
     * assistente, que liga tudo, e a política, que decide se consulta; o contexto,
     * o guard, o prompt protegido e a instrução; os dois redatores da saída; a
     * sugestão, a procedência, o validador e os motivos de recusa; o desfecho.
     *
     * **Quais não, de propósito (H-14).** A varredura olha as dependências diretas
     * de cada arquivo, não o fechamento transitivo. O resto do módulo tem
     * dependências conscientes que não entram aqui: a vigência da similaridade em
     * `FindSimilarProducts` (D-CAT-05B-2) e a lista de campos da oferta que o
     * `ContextSanitizer` lê de `SaveProductWithOffer` (D-CAT-05C-7) — o contexto usa
     * o sanitizer justamente para que nenhum campo de oferta chegue ao prompt.
     *
     * **Como.** Sobre o **código**, sem comentários: os docblocks citam
     * `SaveProductWithOffer` e `ProductOffer` justamente para dizer que não os usam.
     * Strings continuam na varredura, porque classe e tabela também se alcançam por
     * nome. As marcas cobrem as formas reais de chegar a cada dependência neste
     * projeto: model e tabela; o namespace das Actions de cadastro; namespace e
     * facade de Customer Intelligence; a request pelo tipo, pela facade, pelo alias
     * global, pelo helper e pelas classes HTTP da aplicação.
     */
    public function test_o_caminho_externo_nao_depende_de_oferta_expositor_cadastro_nem_request(): void
    {
        $arquivos = [
            ...$this->arquivosDaFronteira(),
            app_path('CatalogIntelligence/Actions/GenerateListingSuggestion.php'),
            app_path('CatalogIntelligence/Support/SuggestionPolicy.php'),
            app_path('CatalogIntelligence/DTOs/ListingContext.php'),
            app_path('CatalogIntelligence/Support/PromptGuard.php'),
            app_path('CatalogIntelligence/DTOs/GuardedPrompt.php'),
            app_path('CatalogIntelligence/Enums/ProviderInstruction.php'),
            app_path('CatalogIntelligence/Support/GuardedPromptRedactor.php'),
            app_path('CatalogIntelligence/Support/FreeTextRedactor.php'),
            app_path('CatalogIntelligence/DTOs/ListingSuggestion.php'),
            app_path('CatalogIntelligence/Enums/SuggestionSource.php'),
            app_path('CatalogIntelligence/Support/ProviderResponseValidator.php'),
            app_path('CatalogIntelligence/Enums/ProviderResponseViolation.php'),
            app_path('CatalogIntelligence/DTOs/ListingOutcome.php'),
            app_path('CatalogIntelligence/Enums/ListingOutcomeState.php'),
        ];

        $marcas = [
            // oferta e expositor: models (e o que carrega o nome deles) e tabelas
            'ProductOffer', 'Expositor', 'product_offers', 'expositores',
            // o cadastro
            'SaveProductWithOffer', 'App\\Actions\\Catalog\\',
            // Customer Intelligence: namespace e facade
            'CustomerIntelligence',
            // request e HTTP da aplicação: tipo, facade, alias global, helper, controllers e form requests
            'Illuminate\\Http\\', 'Illuminate\\Foundation\\Http\\', 'Illuminate\\Support\\Facades\\Request',
            'Request::', 'request(', 'App\\Http\\',
        ];

        foreach ($arquivos as $arquivo) {
            $this->assertFileExists($arquivo, 'um arquivo do caminho externo mudou de lugar: a lista precisa acompanhar');

            $codigo = collect(token_get_all(file_get_contents($arquivo)))
                ->reject(fn ($token) => is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true))
                ->map(fn ($token) => is_array($token) ? $token[1] : $token)
                ->implode('');

            foreach ($marcas as $marca) {
                $this->assertStringNotContainsString(
                    $marca,
                    $codigo,
                    basename($arquivo)." passou a conhecer \"{$marca}\": o caminho até o provider sugere sobre a identidade ".
                    'do item e não lê oferta, expositor, request nem cadastro.',
                );
            }
        }
    }

    /**
     * O binding padrão do contrato é o `Null`, e o `Fake` nunca é registrado.
     *
     * O que substituiu `test_o_service_provider_nao_registra_binding_de_provider`.
     * A CAT-06D não ligava nem o `Null`, porque o acoplamento era da 06G. A 06G o
     * ligou (D-CAT-06G-9): sem credencial, o contrato resolve para "não há
     * provider", que é o estado normal de produção (D-CAT-06B-5).
     *
     * ## Revisto na CAT-10A, por decisão (H-13)
     *
     * A H-13 mandava que um provider real quebrasse este teste e exigisse revisão
     * arquitetural explícita. A revisão foi feita e aprovada: o binding passou a
     * resolver pelo `CatalogAiProviderSelector`, fora do módulo. A trava continua
     * prendendo o essencial — um binding só, sem `singleton`, sem `Fake` — e passa a
     * prender também o caminho: sem configuração o contrato é o `Null`; com
     * configuração válida, é o adaptador real, e nunca o dublê.
     */
    public function test_o_binding_padrao_do_contrato_e_o_null(): void
    {
        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class), 'sem configuração, não há provider');

        $conteudo = file_get_contents(app_path('CatalogIntelligence/CatalogIntelligenceServiceProvider.php'));

        $this->assertSame(1, substr_count($conteudo, '->bind('), 'o módulo tem um binding só: o do contrato do provider');
        $this->assertStringContainsString(
            'CatalogAiProviderSelector::class)->resolve()',
            $conteudo,
            'o contrato resolve pelo seletor da aplicação (CAT-10A, H-13 revista)',
        );

        foreach (['FakeCatalogAiProvider', '->singleton('] as $marca) {
            $this->assertStringNotContainsString(
                $marca,
                $conteudo,
                "o ServiceProvider passou a conhecer \"{$marca}\" — o dublê de teste nunca é o provider de produção.",
            );
        }

        // CAT-10A.1: a configuração é a que o painel grava, lida por `CatalogAiSettings`,
        // e a trava técnica que o phpunit.xml força precisa ser desligada de propósito.
        config()->set('services.catalog_ai.force_disabled', false);
        $this->app->instance(CatalogAiSettings::class, new class extends CatalogAiSettings
        {
            public function atual(): array
            {
                return [
                    'enabled' => true,
                    'provider' => 'openai',
                    'model' => 'modelo-de-teste',
                    'api_key' => 'chave-de-teste-que-nao-vale-nada',
                    'timeout' => 8,
                ];
            }
        });

        $real = app(CatalogAiProvider::class);

        $this->assertInstanceOf(OpenAiCatalogAiProvider::class, $real, 'com configuração válida, o contrato resolve para o adaptador real');
        $this->assertNotInstanceOf(FakeCatalogAiProvider::class, $real);
    }
}
