<?php

namespace App\CatalogIntelligence\Queries;

use App\CatalogIntelligence\DTOs\MatchReason;
use App\CatalogIntelligence\DTOs\SimilarProduct;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Enums\MatchType;
use App\CatalogIntelligence\Support\SimilarityScorer;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Quais itens do catálogo se parecem com este, e por quê?"
 *
 * A resposta vem do conhecimento associado, não do texto. Dois itens com nomes
 * completamente diferentes — "Tapete de crochê" e "Toalha de crochê para
 * abajur" — são próximos porque compartilham técnica, atributo e contexto. É
 * essa leitura que a trilha inteira existe para viabilizar.
 *
 * ## Alcance: catálogo inteiro, não só a própria loja
 *
 * A comparação atravessa lojistas. Foi decisão consciente: o objetivo declarado
 * da trilha é **reaproveitar conhecimento entre lojas**, e limitar ao próprio
 * expositor esvaziaria isso — um lojista novo não teria referência alguma.
 *
 * O que é lido já é público: apenas nome, categoria e conceitos, exatamente o
 * que qualquer visitante vê em `/produtos` e `/loja/{slug}`. Nada de estoque,
 * custo, dono, pedido ou dado de gestão sai daqui. A SEC-02 continua valendo
 * integralmente — ela protege **edição** de item alheio, e nada nesta consulta
 * escreve.
 *
 * ## Vigência: o que é sugerido é o que alguém está vendendo (CAT-05B, M-17)
 *
 * Até aqui o filtro era `products.is_active` solto, e a frase acima era falsa.
 * A CAT-DOM-01 tirou de `products` a resposta sobre visibilidade, e a D-CAT-10
 * deu a essa coluna outro significado: **validade canônica do item**, decidida
 * pela curadoria. Quem responde "o público vê isto?" passou a ser
 * `ProductOffer::scopeVigente()` — oferta ativa, produto ativo e expositor
 * ativo. Um item com produto ativo e oferta desligada continuava saindo daqui
 * como sugestão, e a sua página pública responde 404.
 *
 * A regra **não é reescrita aqui**: a consulta pede
 * `Product::scopeComOfertaVigente()`, que delega ao escopo da oferta. É a
 * mesma concentração que impede o catálogo por eixo e a página da loja de
 * divergirem, agora valendo também para a similaridade.
 *
 * **Isto não contradiz a D-CAT-21.** Um `Product` sem oferta continua ativo no
 * catálogo interno e na Catalog Intelligence: o conhecimento associado a ele
 * permanece, ele continua sendo comparado *a partir* de si mesmo e volta a
 * aparecer no instante em que alguém criar uma oferta vigente. O que ele deixa
 * de fazer é ser **oferecido a outro lojista como referência** — sugerir o que
 * ninguém vende é sugerir um 404.
 *
 * ## Custo
 *
 * Três consultas, independentemente do tamanho do catálogo: os conceitos do
 * item de origem, uma só varredura do pivot com todos os itens que compartilham
 * algum deles, e a hidratação dos produtos encontrados. A vigência entra como
 * subconsulta na segunda, e não como consulta a mais. Não há laço com consulta
 * dentro.
 */
class FindSimilarProducts
{
    public function __construct(private readonly SimilarityScorer $scorer) {}

    /**
     * @return Collection<int, SimilarProduct> Ordenada por score, maior primeiro.
     */
    public function __invoke(Product $product, int $limit = 10): Collection
    {
        $meus = DB::table('catalog_product_knowledge as pk')
            ->join('catalog_knowledge_entries as e', 'e.id', '=', 'pk.knowledge_entry_id')
            ->where('pk.product_id', $product->id)
            ->where('e.status', KnowledgeStatus::Approved->value)
            ->select('pk.knowledge_entry_id', 'pk.source', 'e.name', 'e.type')
            ->get()
            ->keyBy('knowledge_entry_id');

        if ($meus->isEmpty()) {
            return collect();
        }

        $vizinhos = DB::table('catalog_product_knowledge as pk')
            ->join('products as p', 'p.id', '=', 'pk.product_id')
            ->whereIn('pk.knowledge_entry_id', $meus->keys()->all())
            // Um item nunca é semelhante a si mesmo.
            ->where('pk.product_id', '!=', $product->id)
            // Só o que alguém está de fato oferecendo. A regra inteira vem do
            // escopo do domínio — produto ativo, oferta ativa e expositor
            // ativo —, e não é repetida aqui: repetir seria criar a segunda
            // definição de vigência que a CAT-DOM-01 existiu para eliminar.
            ->whereIn('pk.product_id', Product::query()->comOfertaVigente()->select('products.id'))
            ->select('pk.product_id', 'pk.knowledge_entry_id', 'pk.source', 'p.category_id')
            ->get()
            ->groupBy('product_id');

        if ($vizinhos->isEmpty()) {
            return collect();
        }

        $produtos = Product::query()
            ->whereIn('id', $vizinhos->keys()->all())
            ->get()
            ->keyBy('id');

        return $vizinhos
            ->map(fn (Collection $linhas, int $produtoId) => $this->montar(
                $produtos[$produtoId],
                $linhas,
                $meus,
                $product->category_id,
            ))
            ->filter()
            ->sortByDesc(fn (SimilarProduct $s) => $s->score)
            ->take($limit)
            ->values();
    }

    private function montar(Product $vizinho, Collection $linhas, Collection $meus, ?int $minhaCategoria): SimilarProduct
    {
        $score = 0;
        $reasons = [];
        $compartilhados = [];

        foreach ($linhas as $linha) {
            $meu = $meus[$linha->knowledge_entry_id];

            $score += $this->scorer->pesoDoConceitoCompartilhado(
                KnowledgeSource::from($meu->source),
                KnowledgeSource::from($linha->source),
            );

            $compartilhados[] = $meu->name;
            $reasons[] = new MatchReason(
                MatchType::ExactName,
                $this->frase($meu->type, $meu->name),
                $meu->name,
            );
        }

        if ($minhaCategoria !== null && $vizinho->category_id === $minhaCategoria) {
            $score += $this->scorer->pesoDaMesmaCategoria();
            $reasons[] = new MatchReason(
                MatchType::Related,
                'Está na mesma categoria do catálogo.',
            );
        }

        return new SimilarProduct($vizinho, $score, $compartilhados, $reasons);
    }

    /** Explicação em português, nomeando o papel do conceito compartilhado. */
    private function frase(string $tipo, string $nome): string
    {
        $rotulo = match ($tipo) {
            'technique' => 'Técnica compartilhada',
            'material' => 'Material compartilhado',
            'product_type' => 'Mesmo tipo de item',
            'context' => 'Contexto compartilhado',
            'theme' => 'Tema compartilhado',
            'attribute' => 'Atributo compartilhado',
            default => 'Conceito compartilhado',
        };

        return "{$rotulo}: {$nome}.";
    }
}
