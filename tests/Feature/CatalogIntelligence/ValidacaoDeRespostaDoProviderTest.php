<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\ProviderResponseViolation;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Support\ProviderResponseValidator;
use Tests\TestCase;

/**
 * CAT-06D — a dívida B-4: o que o tipo de retorno de `suggest()` não garante.
 *
 * Cada caso aqui constrói uma `ListingSuggestion` que **o construtor aceita
 * sem reclamar** e que mesmo assim não deveria atravessar a fronteira. Se algum
 * destes casos passar a lançar `TypeError` na construção, a violação
 * correspondente virou responsabilidade do PHP e pode sair do validador — e
 * este arquivo é onde isso apareceria.
 */
class ValidacaoDeRespostaDoProviderTest extends TestCase
{
    private function validador(): ProviderResponseValidator
    {
        return new ProviderResponseValidator;
    }

    /** Uma resposta boa, para servir de base e de controle. */
    private function boa(mixed ...$sobrescreve): ListingSuggestion
    {
        return new ListingSuggestion(
            suggestedName: $sobrescreve['suggestedName'] ?? null,
            shortDescription: $sobrescreve['shortDescription'] ?? 'Um resumo honesto.',
            description: $sobrescreve['description'] ?? 'Uma descrição honesta.',
            keywords: $sobrescreve['keywords'] ?? ['crochê', 'tapete'],
            missingInformation: $sobrescreve['missingInformation'] ?? [],
            source: $sobrescreve['source'] ?? SuggestionSource::External,
            confidence: $sobrescreve['confidence'] ?? null,
        );
    }

    // ─── O caso que passa ─────────────────────────────────────────────────────

    public function test_resposta_bem_formada_nao_tem_violacao(): void
    {
        $this->assertSame([], $this->validador()->violacoes($this->boa()));
        $this->assertTrue($this->validador()->ehUtilizavel($this->boa()));
    }

    public function test_campo_nulo_nao_e_violacao(): void
    {
        $vazia = $this->boa(shortDescription: null, description: null);

        $this->assertSame(
            [],
            $this->validador()->violacoes($vazia),
            'nulo é "não tenho o que propor" — resposta legítima desde a CAT-05D, não falha',
        );
    }

    // ─── Texto em branco ──────────────────────────────────────────────────────

    /**
     * `?string` aceita `''` e `'   '`, e `temAlgoAPropor()` responde **true**
     * para os dois — a CAT-09 ofereceria um campo vazio como se fosse proposta.
     */
    public function test_texto_em_branco_e_violacao(): void
    {
        foreach (['', '   ', "\n\t "] as $branco) {
            $this->assertContains(
                ProviderResponseViolation::TextoEmBranco,
                $this->validador()->violacoes($this->boa(shortDescription: $branco)),
                'texto em branco passou pelo validador',
            );
        }
    }

    public function test_o_branco_e_pego_em_qualquer_um_dos_tres_campos(): void
    {
        foreach (['suggestedName', 'shortDescription', 'description'] as $campo) {
            $this->assertContains(
                ProviderResponseViolation::TextoEmBranco,
                $this->validador()->violacoes($this->boa(...[$campo => '  '])),
                "{$campo} em branco não foi pego",
            );
        }
    }

    /** A prova de que o DTO realmente aceita — se ele passar a recusar, isto quebra. */
    public function test_o_dto_de_fato_aceita_texto_em_branco(): void
    {
        $sugestao = $this->boa(shortDescription: '   ');

        $this->assertSame('   ', $sugestao->shortDescription);
        $this->assertTrue(
            $sugestao->temAlgoAPropor(),
            'é justamente isto que torna o branco perigoso: ele se apresenta como proposta',
        );
    }

    // ─── Keywords e missing_information malformadas ───────────────────────────

    /**
     * O `@param array<int, string>` do DTO é documentação, não garantia.
     *
     * @dataProvider listasMalformadas
     */
    public function test_keywords_malformadas_sao_violacao(array $keywords, string $porque): void
    {
        $this->assertContains(
            ProviderResponseViolation::KeywordsMalformadas,
            $this->validador()->violacoes($this->boa(keywords: $keywords)),
            "não pegou: {$porque}",
        );
    }

    /** @dataProvider listasMalformadas */
    public function test_missing_information_malformada_e_violacao(array $lista, string $porque): void
    {
        $this->assertContains(
            ProviderResponseViolation::MissingInformationMalformada,
            $this->validador()->violacoes($this->boa(missingInformation: $lista)),
            "não pegou: {$porque}",
        );
    }

    public static function listasMalformadas(): array
    {
        return [
            'associativo' => [['a' => 'crochê'], 'array associativo vira objeto em JSON'],
            'índices furados' => [[0 => 'crochê', 2 => 'tapete'], 'lista não sequencial'],
            'aninhado' => [[['crochê']], 'array dentro de array'],
            'número' => [[42], 'inteiro onde se espera string'],
            'nulo' => [[null], 'nulo dentro da lista'],
            'string vazia' => [[''], 'string vazia não é keyword'],
            'só espaço' => [['   '], 'espaço não é keyword'],
            'objeto' => [[new \stdClass], 'objeto onde se espera string'],
            'booleano' => [[true], 'booleano onde se espera string'],
        ];
    }

    public function test_lista_vazia_e_valida(): void
    {
        $this->assertSame(
            [],
            $this->validador()->violacoes($this->boa(keywords: [], missingInformation: [])),
            'não ter keyword é diferente de ter keyword malformada',
        );
    }

    // ─── Confiança fora de faixa ──────────────────────────────────────────────

    public function test_confianca_fora_de_zero_a_um_e_violacao(): void
    {
        foreach ([-0.1, -3.0, 1.01, 42.0] as $valor) {
            $this->assertContains(
                ProviderResponseViolation::ConfiancaForaDeFaixa,
                $this->validador()->violacoes($this->boa(confidence: $valor)),
                "confiança {$valor} passou",
            );
        }
    }

    public function test_as_bordas_da_faixa_sao_validas(): void
    {
        foreach ([0.0, 0.5, 1.0] as $valor) {
            $this->assertNotContains(
                ProviderResponseViolation::ConfiancaForaDeFaixa,
                $this->validador()->violacoes($this->boa(confidence: $valor)),
                "confiança {$valor} deveria ser aceita",
            );
        }
    }

    public function test_confianca_nula_e_valida(): void
    {
        $this->assertNotContains(
            ProviderResponseViolation::ConfiancaForaDeFaixa,
            $this->validador()->violacoes($this->boa(confidence: null)),
            'nulo é a decisão da CAT-05D: score ordena, não mede',
        );
    }

    // ─── Procedência ──────────────────────────────────────────────────────────

    /**
     * Uma resposta de provider rotulada `Internal` mente sobre a origem.
     *
     * Não é cosmético: é a CAT-07 que vai gravar isso como histórico, e
     * procedência errada corrompe a auditoria da sugestão.
     */
    public function test_resposta_externa_rotulada_como_interna_e_violacao(): void
    {
        $this->assertContains(
            ProviderResponseViolation::ProcedenciaIncorreta,
            $this->validador()->violacoes($this->boa(source: SuggestionSource::Internal)),
        );
    }

    /** O `Null` devolve `vazia()`, que é `Internal` — e isso é coerente, não bug. */
    public function test_a_vazia_do_null_acusa_procedencia_porque_nao_e_resposta_externa(): void
    {
        $violacoes = $this->validador()->violacoes(ListingSuggestion::vazia());

        $this->assertSame(
            [ProviderResponseViolation::ProcedenciaIncorreta],
            $violacoes,
            'a única violação é a procedência: nada externo produziu essa resposta, e é verdade. '.
            'A 06G nunca chega aqui, porque checa isAvailable() antes.',
        );
    }

    // ─── Acumulação e forma do retorno ────────────────────────────────────────

    public function test_varias_violacoes_sao_devolvidas_juntas(): void
    {
        $pessima = new ListingSuggestion(
            suggestedName: '  ',
            shortDescription: null,
            description: null,
            keywords: [42],
            missingInformation: ['a' => 'x'],
            source: SuggestionSource::Internal,
            confidence: 9.9,
        );

        $violacoes = $this->validador()->violacoes($pessima);

        $this->assertCount(5, $violacoes, 'as cinco violações devem vir juntas, não a primeira só');
        $this->assertFalse($this->validador()->ehUtilizavel($pessima));
    }

    /**
     * O retorno é a lista de violações, e não um booleano.
     *
     * É o que a 06G precisa para registrar **por que** a resposta foi
     * descartada — o quarto estado do desfecho de F-1 carrega o motivo.
     */
    public function test_o_retorno_identifica_a_violacao_por_enum(): void
    {
        $violacoes = $this->validador()->violacoes($this->boa(confidence: 5.0));

        $this->assertContainsOnlyInstancesOf(ProviderResponseViolation::class, $violacoes);
        $this->assertSame('confianca_fora_de_faixa', $violacoes[0]->value);
    }

    public function test_o_validador_nunca_lanca(): void
    {
        $absurda = new ListingSuggestion(
            suggestedName: '',
            shortDescription: '',
            description: '',
            keywords: [[[]], new \stdClass, null, 0.0],
            missingInformation: [false],
            source: SuggestionSource::Internal,
            confidence: -999.0,
        );

        $this->assertIsArray(
            $this->validador()->violacoes($absurda),
            'resposta ruim é evento previsto, não excepcional — o validador devolve, não lança',
        );
    }
}
