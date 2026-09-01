<?php

namespace App\CatalogIntelligence\DTOs;

use App\CatalogIntelligence\Support\ContextSanitizer;
use App\Enums\ItemType;
use App\Models\ContentCategory;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * O mínimo necessário para entender **o que um item é**.
 *
 * É o insumo do assistente de conteúdo: tudo o que a CAT-05D vai ler para
 * escrever nome, resumo e descrição sai daqui, e nada além disto chega lá.
 *
 * ## Só identidade de catálogo (D-CAT-05B-3)
 *
 * Depois da CAT-DOM-02 o item deixou de caber num objeto só: `Product` diz o
 * que ele é, `ProductOffer` diz quem vende, por quanto e em que condições. O
 * contexto lê **apenas o primeiro**, e o construtor nem sequer aceita o
 * segundo — não há parâmetro para `ProductOffer` nem para `Expositor`, e é
 * isso que torna a fronteira estrutural em vez de disciplinar.
 *
 * A razão não é só de privacidade. Um texto que descreve o produto vale para
 * qualquer expositor que venha a oferecê-lo; um texto que menciona preço ou
 * prazo de entrega vale para um só, e envelhece na primeira alteração. No dia
 * em que houver N ofertas sobre o mesmo item, "a condição comercial" deixa de
 * existir no singular — e um contexto que a carregasse teria de escolher um
 * vendedor, que é decisão de produto e não de DTO.
 *
 * ## Funciona com item que ainda não existe
 *
 * `paraItemNovo()` monta contexto a partir de campos soltos, sem nenhuma linha
 * no banco. É o caso do cadastro em andamento — o lojista digitou o nome,
 * ainda não salvou, e quer ajuda. É a mesma escolha que `ProductKnowledgeInput`
 * fez na CAT-04 e pelo mesmo motivo: amarrar o assistente ao model obrigaria a
 * existir produto antes de qualquer sugestão, que é o contrário do que a
 * trilha quer.
 *
 * ## Nada de model Eloquent aqui dentro
 *
 * Todos os campos são escalares ou arrays de escalares. `deProduct()` lê o
 * model e o descarta; `comConhecimento()` e `comSemelhantes()` recebem os DTOs
 * do motor da CAT-04 e guardam só o texto. Guardar o model deixaria relações,
 * timestamps e — no caso de `Product` — o caminho de volta para as ofertas ao
 * alcance de quem consumisse o contexto.
 *
 * ## Imutável, e completado por cópia
 *
 * O conhecimento e os semelhantes não chegam pelo construtor porque não são
 * conhecidos no momento em que o contexto nasce: quem os busca é o
 * `ListingAssistant`, na CAT-05D, e ele o faz *a partir* deste contexto.
 * `comConhecimento()` e `comSemelhantes()` devolvem uma cópia nova em vez de
 * mutar — um contexto que muda depois de montado deixaria de ser reproduzível,
 * e reproduzir a entrada é o que permitirá auditar uma sugestão depois.
 *
 * @see ContextSanitizer  a minimização, aplicada aqui na construção
 */
final class ListingContext
{
    /**
     * @param  array<int, string>  $categoryPath  Do ancestral mais alto até a própria categoria.
     * @param  array<string, scalar>  $knownAttributes  Só o que foi informado; nunca inferido.
     * @param  array<int, array{name: string, type: string, description: string|null, terms: array<int, string>}>  $knowledge
     * @param  array<int, array{name: string, shared_concepts: array<int, string>, reasons: array<int, string>}>  $similarItems
     */
    private function __construct(
        public readonly ItemType $itemType,
        public readonly string $name,
        public readonly array $categoryPath = [],
        public readonly ?string $existingShortDescription = null,
        public readonly ?string $existingDescription = null,
        public readonly array $knownAttributes = [],
        public readonly array $knowledge = [],
        public readonly array $similarItems = [],
    ) {}

    /**
     * Contexto de um item que ainda não foi salvo.
     *
     * `$knownAttributes` é o que o formulário informou — e **só** isso. O
     * domínio não tem hoje campo estruturado de material, técnica ou cor: a
     * CAT-02 decidiu deixá-los fora de `products` por serem multivalorados. O
     * parâmetro existe para a CAT-09 entregar o que o lojista digitar, e não
     * para esta camada deduzir nada.
     *
     * ## Obrigação de quem for preencher isto (CAT-09) — dívida C-1
     *
     * **Mapeie campo a campo. Nunca repasse o array do request.**
     *
     * `$request->all()`, `$request->except([...])` ou o array de propriedades
     * do Livewire entregues inteiros aqui são proibidos, e o motivo é que a
     * proteção deste parâmetro é uma **lista de proibição**, não de permissão:
     * ela derruba o que alguém lembrou de listar em
     * `ContextSanitizer::CAMPOS_PROIBIDOS`, e deixa passar qualquer chave
     * sensível com nome que ninguém previu. Um payload repassado em bloco
     * transforma cada campo novo de formulário em vazamento silencioso.
     *
     * A forma correta é nomear o que entra:
     *
     * ```php
     * knownAttributes: ['material' => $this->material, 'tecnica' => $this->tecnica]
     * ```
     *
     * A dívida se fecha sozinha no dia em que existir vocabulário de atributos
     * no domínio — aí a lista vira de permissão e o repasse deixa de importar.
     * Registro em `CAT_05C_LISTING_CONTEXT_E_SANITIZER.md`.
     *
     * @param  array<int, string>  $categoryPath
     * @param  array<string, mixed>  $knownAttributes
     */
    public static function paraItemNovo(
        ItemType $itemType,
        string $name,
        array $categoryPath = [],
        ?string $shortDescription = null,
        ?string $description = null,
        array $knownAttributes = [],
        ?ContextSanitizer $sanitizer = null,
    ): self {
        $sanitizer ??= app(ContextSanitizer::class);

        return new self(
            itemType: $itemType,
            name: trim($name),
            categoryPath: array_values(array_filter(array_map('strval', $categoryPath), fn ($c) => trim($c) !== '')),
            existingShortDescription: $sanitizer->texto($shortDescription),
            existingDescription: $sanitizer->texto($description),
            knownAttributes: $sanitizer->atributos($knownAttributes),
        );
    }

    /**
     * Contexto de um item que já está no catálogo.
     *
     * Repare no que **não** é lido: nada de `offers`, `ofertaVigente`,
     * `expositor`, `images` ou `faqs`. O produto entra por aqui e sai como
     * texto; o model não é guardado.
     *
     * @param  array<string, mixed>  $knownAttributes
     */
    public static function deProduct(
        Product $product,
        array $knownAttributes = [],
        ?ContextSanitizer $sanitizer = null,
    ): self {
        return self::paraItemNovo(
            itemType: $product->item_type instanceof ItemType
                ? $product->item_type
                : ItemType::from((string) $product->item_type),
            name: (string) $product->name,
            categoryPath: self::caminhoDaCategoria($product->category),
            shortDescription: $product->short_description,
            description: $product->description,
            knownAttributes: $knownAttributes,
            sanitizer: $sanitizer,
        );
    }

    /**
     * Cópia deste contexto com os conceitos encontrados pelo motor da CAT-04.
     *
     * @param  Collection<int, KnowledgeCandidate>|array<int, KnowledgeCandidate>  $candidatos
     */
    public function comConhecimento(Collection|array $candidatos, ?ContextSanitizer $sanitizer = null): self
    {
        $sanitizer ??= app(ContextSanitizer::class);

        return new self(
            itemType: $this->itemType,
            name: $this->name,
            categoryPath: $this->categoryPath,
            existingShortDescription: $this->existingShortDescription,
            existingDescription: $this->existingDescription,
            knownAttributes: $this->knownAttributes,
            knowledge: $sanitizer->conhecimento($candidatos),
            similarItems: $this->similarItems,
        );
    }

    /**
     * Cópia deste contexto com as referências internas.
     *
     * A vigência já veio decidida: quem produz `SimilarProduct` é
     * `FindSimilarProducts`, e desde a D-CAT-05B-2 ele só devolve item que
     * alguém está de fato oferecendo. Este método não reconfere — reconferir
     * seria a segunda definição de vigência que a CAT-DOM-01 eliminou.
     *
     * @param  Collection<int, SimilarProduct>|array<int, SimilarProduct>  $semelhantes
     */
    public function comSemelhantes(Collection|array $semelhantes, ?ContextSanitizer $sanitizer = null): self
    {
        $sanitizer ??= app(ContextSanitizer::class);

        return new self(
            itemType: $this->itemType,
            name: $this->name,
            categoryPath: $this->categoryPath,
            existingShortDescription: $this->existingShortDescription,
            existingDescription: $this->existingDescription,
            knownAttributes: $this->knownAttributes,
            knowledge: $this->knowledge,
            similarItems: $sanitizer->semelhantes($semelhantes),
        );
    }

    /**
     * A ponte para o motor da CAT-04, sem duplicar o que ele já sabe fazer.
     *
     * `ProductKnowledgeInput` decide quais campos alimentam a busca e em que
     * ordem são concatenados. Montar essa lista de novo aqui criaria duas
     * respostas para "o que é o texto deste item", e elas divergiriam na
     * primeira vez que uma das duas ganhasse um campo.
     *
     * A categoria que vai junto é a **própria**, a última do caminho — é ela
     * que nomeia o item, e os ancestrais servem ao texto, não ao casamento.
     */
    public function paraBuscaDeConhecimento(): ProductKnowledgeInput
    {
        return new ProductKnowledgeInput(
            name: $this->name,
            shortDescription: $this->existingShortDescription,
            description: $this->existingDescription,
            // `array_key_last` e não `end()`: aquele recebe o array por
            // referência e move o ponteiro interno, o que PHP recusa sobre uma
            // propriedade `readonly`.
            categoryName: $this->categoryPath === []
                ? null
                : $this->categoryPath[array_key_last($this->categoryPath)],
        );
    }

    /**
     * O que ainda não se sabe sobre este item.
     *
     * Não é a `missing_information` da sugestão — aquela é da CAT-05E e fala a
     * linguagem do lojista. Esta é a leitura crua do contexto, e existe para
     * que a decisão "há material suficiente?" seja tomada sobre um fato, e não
     * sobre um `empty()` espalhado por quem consome.
     *
     * @return array<int, string>
     */
    public function lacunas(): array
    {
        return array_values(array_filter([
            $this->existingShortDescription === null ? 'short_description' : null,
            $this->existingDescription === null ? 'description' : null,
            $this->categoryPath === [] ? 'category' : null,
            $this->knownAttributes === [] ? 'attributes' : null,
            $this->knowledge === [] ? 'knowledge' : null,
        ]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'item_type' => $this->itemType->value,
            'name' => $this->name,
            'category_path' => $this->categoryPath,
            'existing_short_description' => $this->existingShortDescription,
            'existing_description' => $this->existingDescription,
            'known_attributes' => $this->knownAttributes,
            'knowledge' => $this->knowledge,
            'similar_items' => $this->similarItems,
        ];
    }

    /**
     * Nome da categoria e dos ancestrais, do topo para baixo.
     *
     * Sobe pela cadeia com um teto de dez níveis: `content_categories` é
     * hierárquica por `parent_id` e nada no banco impede um ciclo, então uma
     * subida ingênua travaria a requisição em vez de devolver contexto.
     *
     * @return array<int, string>
     */
    private static function caminhoDaCategoria(?ContentCategory $categoria): array
    {
        $caminho = [];
        $vistos = [];
        $atual = $categoria;

        while ($atual !== null && count($caminho) < 10 && ! in_array($atual->id, $vistos, true)) {
            $vistos[] = $atual->id;
            array_unshift($caminho, (string) $atual->name);
            $atual = $atual->parent;
        }

        return $caminho;
    }
}
