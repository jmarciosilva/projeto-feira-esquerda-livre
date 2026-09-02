<?php

namespace App\CatalogIntelligence\DTOs;

use App\CatalogIntelligence\Enums\SuggestionSource;

/**
 * O que a inteligência propõe para um item — estruturado, nunca texto solto.
 *
 * A forma vem da §3.4 do documento arquitetural e não foi reaberta aqui.
 * Estruturado importa por dois motivos práticos: a tela da CAT-09 precisa
 * oferecer **aplicação seletiva** — o lojista aceita o resumo e recusa a
 * descrição —, e a CAT-07 precisa registrar o que foi aplicado campo a campo.
 * Um blob de texto tornaria as duas coisas impossíveis.
 *
 * ## Sugerir não é salvar
 *
 * Nada aqui está gravado, e nada nesta fase grava (D-CAT-05B-1). Este objeto é
 * pré-visualização: quem decide o que fazer com ele é a CAT-09, e quem tem
 * autoridade para mudar a identidade do item é a `ProductPolicy`, que esta
 * fase não aciona.
 *
 * ## Campo nulo é resposta, não falha
 *
 * Um campo nulo diz "não tenho o que propor aqui", e isso é informação útil —
 * bem mais útil que devolver o texto que o lojista já escreveu, fingindo
 * contribuição. Quem quiser saber se sobrou alguma coisa pergunta a
 * `temAlgoAPropor()`.
 *
 * ## Sobre `confidence`
 *
 * Fica **nula**, e é uma decisão, não um esquecimento.
 *
 * A CAT-03 tomou a mesma para a coluna `confidence` de `KnowledgeEntry`:
 * *"atribuir 0,7 a uma origem hoje seria inventar precisão que ninguém
 * mediu"*. O argumento não mudou. Um número aqui seria derivado do score da
 * similaridade — que a própria CAT-04 declara servir para **ordenar** e não
 * para ser lido como porcentagem. Converter uma ordem em um decimal e mostrá-lo
 * a um lojista é falsa ciência com casa decimal.
 *
 * O campo existe porque a §3.4 o nomeia e porque a CAT-06 pode ter uma medida
 * de verdade vinda do provider. Até lá, nulo.
 *
 * ## Este texto passou pelo lojista, e a CAT-09 precisa saber (dívida S-2)
 *
 * `shortDescription` e `description` são compostos **a partir do texto que o
 * lojista digitou**: o nome do item abre as duas frases, sempre. O módulo não
 * escapa nada — não é o papel dele, e escapar aqui gravaria entidade HTML
 * dentro de um campo que a CAT-09 pode aplicar a `products.description`.
 *
 * A consequência é uma obrigação de quem renderiza: **a sugestão é conteúdo de
 * usuário e tem de ser tratada como tal**. Blade escapa por padrão, então
 * `{{ $sugestao->description }}` está correto e nada precisa ser feito — o que
 * não pode acontecer é `{!! !!}`, nem `wire:ignore` com `innerHTML`, nem
 * `v-html`, sob o raciocínio de que "o texto veio da inteligência". Não veio:
 * veio do formulário, deu uma volta e voltou.
 *
 * É a mesma forma da dívida **C-1** — escrita por extenso ao lado do que a
 * exige, e não só num documento —, e pela mesma razão: quem for escrever a
 * tela lê este arquivo, não necessariamente o roadmap.
 *
 * Não é prompt injection, que é outra dívida (**S-1**) e outro gate: aquela
 * trata de texto do lojista virando **instrução** para um provider, e só passa
 * a existir na CAT-06. Esta trata de texto do lojista virando **marcação** numa
 * página, e existe desde que a primeira tela renderizar uma sugestão.
 */
final class ListingSuggestion
{
    /**
     * @param  array<int, string>  $keywords
     * @param  array<int, string>  $missingInformation
     */
    public function __construct(
        public readonly ?string $suggestedName,
        public readonly ?string $shortDescription,
        public readonly ?string $description,
        public readonly array $keywords = [],
        public readonly array $missingInformation = [],
        public readonly SuggestionSource $source = SuggestionSource::Internal,
        public readonly ?float $confidence = null,
    ) {}

    /**
     * A sugestão vazia — o assistente rodou e não tinha o que propor.
     *
     * É o retorno correto quando a base de conhecimento não alcança o item, e
     * **não** é erro: `missing_information` continua carregando o que falta, e
     * é isso que a CAT-05E vai transformar em pedido ao lojista. A alternativa
     * — lançar exceção ou devolver nulo — obrigaria cada superfície a tratar
     * ausência como falha, e ausência de conhecimento é o estado normal de um
     * catálogo que está começando.
     *
     * @param  array<int, string>  $missingInformation
     */
    public static function vazia(array $missingInformation = []): self
    {
        return new self(
            suggestedName: null,
            shortDescription: null,
            description: null,
            keywords: [],
            missingInformation: $missingInformation,
            source: SuggestionSource::Internal,
        );
    }

    /** Sobrou alguma proposta de texto, ou só o que falta? */
    public function temAlgoAPropor(): bool
    {
        return $this->suggestedName !== null
            || $this->shortDescription !== null
            || $this->description !== null
            || $this->keywords !== [];
    }

    /** Os campos de texto que a sugestão preenche, para a aplicação seletiva da CAT-09. */
    public function camposPropostos(): array
    {
        return array_keys(array_filter([
            'name' => $this->suggestedName,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
        ], fn ($v) => $v !== null));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'suggested_name' => $this->suggestedName,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'missing_information' => $this->missingInformation,
            'source' => $this->source->value,
            'confidence' => $this->confidence,
        ];
    }
}
