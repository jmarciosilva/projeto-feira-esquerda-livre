<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Providers\FakeCatalogAiProvider;
use App\CatalogIntelligence\Providers\NullCatalogAiProvider;
use App\Enums\ItemType;
use Tests\TestCase;

/**
 * CAT-06D — o contrato do provider, e as duas implementações que não falam com
 * a rede.
 *
 * ## O que este arquivo substitui
 *
 * `ListingAssistantTest::test_nenhuma_interface_de_provider_externo_existe`
 * prendia quatro nomes. Três chegaram nesta subfase, por decisão, e as
 * linguetas correspondentes viraram as garantias positivas daqui: em vez de
 * *"o contrato não existe"*, agora se afirma *"o contrato existe, e nem ele nem
 * as implementações sabem falar com fora"*.
 *
 * `EmbeddingProvider` continua preso lá, porque a **B-3** segue sem decisão.
 *
 * ## Sem banco, sem rede, sem dublê de terceiro
 *
 * Nenhum caso aqui usa `RefreshDatabase` — o contrato e as duas implementações
 * são puros. Se um dia algum deles precisar de banco, este arquivo quebra, e a
 * quebra é o recado.
 */
class ContratosDeProviderTest extends TestCase
{
    private function contexto(string $nome = 'Tapete de crochê'): ListingContext
    {
        return ListingContext::paraItemNovo(ItemType::Produto, $nome);
    }

    /** @return array<int, string> Os três arquivos que a CAT-06D entrega. */
    private function arquivosDaSubfase(): array
    {
        return [
            app_path('CatalogIntelligence/Contracts/CatalogAiProvider.php'),
            app_path('CatalogIntelligence/Providers/NullCatalogAiProvider.php'),
            app_path('CatalogIntelligence/Providers/FakeCatalogAiProvider.php'),
        ];
    }

    // ─── O contrato ───────────────────────────────────────────────────────────

    public function test_o_contrato_existe_com_a_assinatura_da_spec(): void
    {
        $this->assertTrue(interface_exists(CatalogAiProvider::class));

        $reflexao = new \ReflectionClass(CatalogAiProvider::class);

        $this->assertSame(
            ['isAvailable', 'suggest'],
            collect($reflexao->getMethods())->map(fn ($m) => $m->name)->sort()->values()->all(),
            'a interface tem exatamente os dois métodos da §3.3 — nem mais, nem menos',
        );

        $this->assertSame('bool', (string) $reflexao->getMethod('isAvailable')->getReturnType());
        $this->assertSame(ListingSuggestion::class, (string) $reflexao->getMethod('suggest')->getReturnType());
        $this->assertSame(
            ListingContext::class,
            (string) $reflexao->getMethod('suggest')->getParameters()[0]->getType(),
        );
    }

    public function test_as_duas_implementacoes_cumprem_o_contrato(): void
    {
        $this->assertInstanceOf(CatalogAiProvider::class, new NullCatalogAiProvider);
        $this->assertInstanceOf(CatalogAiProvider::class, FakeCatalogAiProvider::disponivel());
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
        $sugestao = $null->suggest($this->contexto());
        $this->assertInstanceOf(ListingSuggestion::class, $sugestao);
        $this->assertFalse($sugestao->temAlgoAPropor());

        // Repetido, e com contextos de formatos diferentes.
        foreach (['', 'x', str_repeat('nome muito longo ', 500), "quebra\nde linha", '<script>'] as $nome) {
            $this->assertInstanceOf(
                ListingSuggestion::class,
                $null->suggest($this->contexto($nome)),
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
            $null->suggest($this->contexto())->toArray(),
            $null->suggest($this->contexto('Outro item'))->toArray(),
            'a resposta do Null não depende do contexto: não há nada para depender dele',
        );
    }

    // ─── Fake é determinístico ────────────────────────────────────────────────

    public function test_o_fake_e_deterministico_entre_chamadas_e_instancias(): void
    {
        $primeira = FakeCatalogAiProvider::disponivel()->suggest($this->contexto())->toArray();

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(
                $primeira,
                FakeCatalogAiProvider::disponivel()->suggest($this->contexto())->toArray(),
                'o Fake variou entre instâncias — determinismo é a característica principal dele',
            );
        }

        $mesmaInstancia = FakeCatalogAiProvider::disponivel();
        $this->assertSame(
            $mesmaInstancia->suggest($this->contexto())->toArray(),
            $mesmaInstancia->suggest($this->contexto())->toArray(),
            'o Fake variou entre chamadas da mesma instância',
        );
    }

    public function test_contextos_diferentes_dao_respostas_diferentes_mas_estaveis(): void
    {
        $a = FakeCatalogAiProvider::disponivel()->suggest($this->contexto('Tapete'));
        $b = FakeCatalogAiProvider::disponivel()->suggest($this->contexto('Colar'));

        $this->assertNotSame($a->description, $b->description, 'a resposta deriva do contexto');
        $this->assertSame(
            $a->toArray(),
            FakeCatalogAiProvider::disponivel()->suggest($this->contexto('Tapete'))->toArray(),
        );
    }

    public function test_o_fake_simula_os_estados_de_ausencia_sucesso_e_falha(): void
    {
        // 1 — ausente.
        $this->assertFalse(FakeCatalogAiProvider::indisponivel()->isAvailable());

        // 2 — responde bem, e a procedência é externa.
        $boa = FakeCatalogAiProvider::disponivel()->suggest($this->contexto());
        $this->assertSame(SuggestionSource::External, $boa->source);

        // 3 — falha. É o único caminho que lança, e de propósito.
        $this->expectException(\RuntimeException::class);
        FakeCatalogAiProvider::queFalha()->suggest($this->contexto());
    }

    public function test_o_fake_devolve_a_resposta_fixada_inclusive_invalida(): void
    {
        // 4 — resposta fora do contrato, para a 06G exercitar a B-4.
        $invalida = new ListingSuggestion(
            suggestedName: null,
            shortDescription: '   ',
            description: null,
            source: SuggestionSource::External,
        );

        $this->assertSame(
            '   ',
            FakeCatalogAiProvider::respondendo($invalida)->suggest($this->contexto())->shortDescription,
        );
    }

    public function test_o_fake_conta_as_chamadas(): void
    {
        $fake = FakeCatalogAiProvider::disponivel();

        $this->assertSame(0, $fake->chamadas(), 'nasce sem ter sido consultado');

        $fake->suggest($this->contexto());
        $fake->suggest($this->contexto());

        $this->assertSame(2, $fake->chamadas());
    }

    public function test_o_indisponivel_nao_e_consultado_por_acidente(): void
    {
        $fake = FakeCatalogAiProvider::indisponivel();

        $this->assertFalse($fake->isAvailable());
        $this->assertSame(0, $fake->chamadas());
    }

    // ─── Nenhum dos três fala com fora ────────────────────────────────────────

    public function test_nenhuma_das_tres_classes_importa_cliente_http(): void
    {
        foreach ($this->arquivosDaSubfase() as $arquivo) {
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
    public function test_nenhuma_das_tres_classes_nomeia_fornecedor_real(): void
    {
        $fornecedores = [
            'OpenAI', 'openai', 'Anthropic', 'anthropic', 'Claude', 'GPT',
            'Gemini', 'gemini', 'Bedrock', 'Ollama', 'Mistral', 'Cohere',
            'HuggingFace', 'Azure', 'Vertex',
        ];

        foreach ($this->arquivosDaSubfase() as $arquivo) {
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

    public function test_nenhuma_das_tres_le_credencial_ou_endpoint(): void
    {
        foreach ($this->arquivosDaSubfase() as $arquivo) {
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
     * Nada foi registrado no container apontando para um provider.
     *
     * `D-CAT-06B-5`: o `Null` é o caminho de produção enquanto não houver
     * credencial, e **a 06D não liga nem ele** — quem resolve o provider é a
     * 06G, junto com o redator e o guard. Um binding aqui adiantaria o
     * acoplamento que a ordem das subfases existe para evitar.
     */
    public function test_o_service_provider_nao_registra_binding_de_provider(): void
    {
        $conteudo = file_get_contents(app_path('CatalogIntelligence/CatalogIntelligenceServiceProvider.php'));

        foreach (['CatalogAiProvider', 'NullCatalogAiProvider', 'FakeCatalogAiProvider', '->bind(', '->singleton('] as $marca) {
            $this->assertStringNotContainsString(
                $marca,
                $conteudo,
                "o ServiceProvider passou a conhecer \"{$marca}\" — o acoplamento do provider é da CAT-06G.",
            );
        }
    }
}
