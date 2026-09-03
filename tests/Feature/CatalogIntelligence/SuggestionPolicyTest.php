<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\DTOs\KnowledgeCandidate;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Enums\KnowledgeSufficiency;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Support\SuggestionPolicy;
use App\Enums\ItemType;
use Tests\TestCase;

/**
 * CAT-06C — o limiar de fallback, e por que o veredito não é booleano.
 *
 * ## Não há `RefreshDatabase` aqui, e isso é o ponto
 *
 * A política não toca banco, não toca rede e não recebe dependência nenhuma.
 * Todo caso deste arquivo monta um `ListingContext` em memória e chama a
 * política — nenhum dublê, nenhuma migration, nenhum `Http::fake()`.
 *
 * É a prova operacional da D-CAT-06B-3: uma política que conhecesse provider
 * precisaria de um `Fake` que só nasce na CAT-06D, e este arquivo não poderia
 * existir antes dela.
 *
 * ## As cinco lacunas, e como cada caso as controla
 *
 * `ListingContext::lacunas()` enumera cinco: `short_description`,
 * `description`, `category`, `attributes` e `knowledge`. Um contexto montado
 * por `paraItemNovo()` só com o nome tem **as cinco** abertas; preencher cada
 * argumento fecha a sua.
 *
 * `knowledge` e `similar_items` não entram pelo construtor — chegam por
 * `comConhecimento()`, que é o que a CAT-05D faz depois de consultar a base.
 */
class SuggestionPolicyTest extends TestCase
{
    private function politica(): SuggestionPolicy
    {
        return new SuggestionPolicy;
    }

    /**
     * Contexto com um número controlado de lacunas abertas.
     *
     * As cinco fecham nesta ordem: conhecimento, atributos, categoria,
     * descrição, resumo. A ordem existe para que "duas lacunas" signifique
     * sempre o mesmo par entre um caso e outro.
     *
     * @param  int  $lacunas  Quantas devem ficar **abertas**, de 0 a 5.
     */
    private function contextoCom(int $lacunas): ListingContext
    {
        $abertas = fn (int $n) => $lacunas >= $n;

        $contexto = ListingContext::paraItemNovo(
            ItemType::Produto,
            'Tapete de crochê',
            categoryPath: $abertas(3) ? [] : ['Casa', 'Tapetes'],
            shortDescription: $abertas(5) ? null : 'Tapete redondo feito à mão.',
            description: $abertas(4) ? null : 'Peça de crochê em algodão cru.',
            knownAttributes: $abertas(2) ? [] : ['material' => 'algodão'],
        );

        return $abertas(1)
            ? $contexto
            : $contexto->comConhecimento(collect([$this->conceito()]));
    }

    /**
     * Um conceito aprovado, em memória, só para que `knowledge` deixe de estar
     * vazio.
     *
     * A entrada **não é salva**, e a relação `terms` é pré-carregada vazia de
     * propósito: `ContextSanitizer::termosUteis()` percorre essa coleção, e sem
     * o `setRelation` o Eloquent tentaria buscá-la no banco. Assim o arquivo
     * inteiro roda sem migration.
     *
     * `Approved` é obrigatório — o sanitizer descarta candidato em qualquer
     * outro estado, e um conceito rascunho deixaria a lacuna `knowledge` aberta
     * sem que o caso de teste dissesse isso.
     */
    private function conceito(): KnowledgeCandidate
    {
        $entrada = new KnowledgeEntry;
        $entrada->name = 'Crochê';
        $entrada->type = KnowledgeEntryType::Technique;
        $entrada->status = KnowledgeStatus::Approved;
        $entrada->description = 'Técnica de tecer fios com agulha única.';
        $entrada->setRelation('terms', collect());

        return new KnowledgeCandidate($entrada, score: 10, reasons: []);
    }

    // ─── Os dois extremos ─────────────────────────────────────────────────────

    public function test_item_sem_nenhuma_lacuna_e_suficiente(): void
    {
        $contexto = $this->contextoCom(0);

        $this->assertSame([], $contexto->lacunas(), 'o helper precisa fechar as cinco');
        $this->assertSame(KnowledgeSufficiency::Sufficient, ($this->politica())($contexto));
    }

    public function test_item_com_todas_as_lacunas_e_insuficiente(): void
    {
        $contexto = $this->contextoCom(5);

        $this->assertCount(5, $contexto->lacunas(), 'o helper precisa abrir as cinco');
        $this->assertNotSame(KnowledgeSufficiency::Sufficient, ($this->politica())($contexto));
    }

    // ─── O limiar, nos dois lados da borda ────────────────────────────────────

    /**
     * A comparação é `>=`: no número exato do limiar já é insuficiente.
     *
     * Os três casos abaixo cercam o mesmo limiar de 3 — dois, três e quatro
     * lacunas —, porque um limiar só está testado quando o caso de borda e os
     * dois vizinhos estão fixados.
     */
    public function test_uma_lacuna_a_menos_que_o_limiar_ainda_e_suficiente(): void
    {
        config()->set('catalog-intelligence.fallback.minimum_gaps', 3);

        $this->assertSame(
            KnowledgeSufficiency::Sufficient,
            ($this->politica())($this->contextoCom(2)),
        );
    }

    public function test_no_limiar_exato_ja_e_insuficiente(): void
    {
        config()->set('catalog-intelligence.fallback.minimum_gaps', 3);

        $this->assertNotSame(
            KnowledgeSufficiency::Sufficient,
            ($this->politica())($this->contextoCom(3)),
        );
    }

    public function test_uma_lacuna_a_mais_que_o_limiar_e_insuficiente(): void
    {
        config()->set('catalog-intelligence.fallback.minimum_gaps', 3);

        $this->assertNotSame(
            KnowledgeSufficiency::Sufficient,
            ($this->politica())($this->contextoCom(4)),
        );
    }

    // ─── O limiar vem mesmo do config ─────────────────────────────────────────

    /**
     * O mesmo contexto, dois limiares, dois vereditos.
     *
     * É o teste que separa "lê do config" de "tem 3 escrito no meio do código e
     * o config é decorativo". Com quatro lacunas abertas, limiar 5 diz
     * suficiente e limiar 4 diz insuficiente — e nada além do config mudou.
     */
    public function test_o_limiar_vem_do_config_e_a_politica_reage_a_ele(): void
    {
        $contexto = $this->contextoCom(4);

        config()->set('catalog-intelligence.fallback.minimum_gaps', 5);
        $this->assertSame(KnowledgeSufficiency::Sufficient, ($this->politica())($contexto));

        config()->set('catalog-intelligence.fallback.minimum_gaps', 4);
        $this->assertNotSame(KnowledgeSufficiency::Sufficient, ($this->politica())($contexto));
    }

    public function test_o_arquivo_de_config_existe_e_traz_o_limiar(): void
    {
        $this->assertIsInt(config('catalog-intelligence.fallback.minimum_gaps'));
    }

    /**
     * O config é de política, não de conexão.
     *
     * A D-CAT-06B-2 proíbe credencial, fornecedor, endpoint e segredo neste
     * arquivo. O teste lê o array inteiro e falha se qualquer chave desse tipo
     * aparecer — inclusive aninhada, num acréscimo futuro distraído.
     */
    public function test_o_config_nao_carrega_credencial_nem_fornecedor(): void
    {
        $achatado = json_encode(config('catalog-intelligence'));

        foreach (['key', 'secret', 'token', 'endpoint', 'url', 'openai', 'anthropic', 'gemini'] as $proibida) {
            $this->assertStringNotContainsString(
                $proibida,
                strtolower((string) $achatado),
                "config/catalog-intelligence.php ganhou \"{$proibida}\": é arquivo de política, não de conexão (D-CAT-06B-2). ".
                'Credencial de integração mora em config/services.php.',
            );
        }
    }

    // ─── O veredito não é booleano ────────────────────────────────────────────

    /**
     * Faltando texto, consulta externa se justifica.
     *
     * Resumo e descrição são as duas lacunas que `podeSerPreenchidaPelaSugestao()`
     * marca como preenchíveis por quem escreve.
     */
    public function test_lacuna_de_texto_justifica_consulta_externa(): void
    {
        config()->set('catalog-intelligence.fallback.minimum_gaps', 2);

        // Abre resumo e descrição; categoria, atributos e conhecimento fechados.
        $contexto = ListingContext::paraItemNovo(
            ItemType::Produto,
            'Tapete de crochê',
            categoryPath: ['Casa', 'Tapetes'],
            knownAttributes: ['material' => 'algodão'],
        )->comConhecimento(collect([$this->conceito()]));

        $veredito = ($this->politica())($contexto);

        $this->assertSame(KnowledgeSufficiency::ExternalMayHelp, $veredito);
        $this->assertTrue($veredito->justificaConsultaExterna());
    }

    /**
     * Faltando só o que o lojista sabe, consultar seria pagar por invenção.
     *
     * Categoria, atributos e conhecimento curado não se resolvem escrevendo
     * texto — é a regra 1 das invioláveis aplicada à decisão de consultar.
     */
    public function test_lacuna_que_so_o_lojista_preenche_nao_justifica_consulta(): void
    {
        config()->set('catalog-intelligence.fallback.minimum_gaps', 2);

        // Resumo e descrição preenchidos; as outras três abertas.
        $contexto = ListingContext::paraItemNovo(
            ItemType::Produto,
            'Tapete de crochê',
            shortDescription: 'Tapete redondo feito à mão.',
            description: 'Peça de crochê em algodão cru.',
        );

        $veredito = ($this->politica())($contexto);

        $this->assertSame(KnowledgeSufficiency::AwaitsMerchant, $veredito);
        $this->assertFalse($veredito->justificaConsultaExterna());
    }

    // ─── Fonte única: a política não reconta ──────────────────────────────────

    /**
     * A política pergunta a `lacunas()` e não olha o contexto por conta própria.
     *
     * `ListingContext` é `final` com construtor privado, então não há como
     * espionar a chamada por subclasse — a asserção é sobre a fonte, no mesmo
     * padrão estrutural que `FronteiraDePromptTest` já usa no módulo.
     *
     * O que se prova: o arquivo chama `->lacunas()` **uma vez** e não menciona
     * nenhuma das cinco propriedades cruas de onde `lacunas()` deriva. Se
     * alguém reintroduzir um `$contexto->knowledge === []` aqui, passam a
     * existir duas respostas para "o que falta neste item".
     */
    public function test_a_politica_nao_reconta_lacunas(): void
    {
        $fonte = file_get_contents(app_path('CatalogIntelligence/Support/SuggestionPolicy.php'));

        $codigo = preg_replace('!/\*.*?\*/!s', '', $fonte);

        $this->assertSame(
            1,
            substr_count($codigo, '->lacunas()'),
            'a política deve consultar lacunas() exatamente uma vez e derivar tudo do resultado',
        );

        $cruas = [
            'existingShortDescription',
            'existingDescription',
            'categoryPath',
            'knownAttributes',
            '->knowledge',
        ];

        foreach ($cruas as $propriedade) {
            $this->assertStringNotContainsString(
                $propriedade,
                (string) $codigo,
                "SuggestionPolicy lê {$propriedade} direto do contexto — isso recria a segunda fonte que ".
                'lacunas() existe para evitar (D-CAT-06B-3).',
            );
        }
    }
}
