<?php

namespace App\CatalogIntelligence\Actions;

use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\ListingGap;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Queries\FindSimilarProducts;
use App\Models\Product;

/**
 * O assistente de conteúdo — a única porta que o cadastro conhece (§3.2).
 *
 * Recebe um `ListingContext`, completa-o com o que o motor da CAT-04 sabe, e
 * devolve um `ListingSuggestion` estruturado.
 *
 * ## Um caminho só, o interno
 *
 * A §3.2 desenha o assistente decidindo entre conhecimento interno e provider
 * externo. Nesta fase **não há a segunda opção**: a D-CAT-05B-4 situa
 * `CatalogAiProvider`, `Fake` e `Null` na CAT-06, e não existe interface a
 * consultar. O `source` da sugestão é sempre `Internal`, e a decisão de
 * fallback — o "conhecimento suficiente?" do fluxograma da §1 — é a primeira
 * coisa que a CAT-06 vai acrescentar aqui, e não em quem chama.
 *
 * ## Sugerir não é salvar (D-CAT-05B-1)
 *
 * Esta Action **não escreve uma linha**. Não chama `SaveProductWithOffer`, não
 * aciona `ProductPolicy::updateCanonical`, não persiste associação. Ela lê e
 * compõe.
 *
 * Repare que ela nem sequer chama `AssociateProductKnowledge`, apesar de ter em
 * mãos exatamente os candidatos de que aquela Action precisa. É deliberado:
 * sugerir texto e afirmar conhecimento são atos diferentes, e o segundo entra
 * na base, é lido depois como verdade e volta reforçando outros itens. Quem
 * persiste associação é o comando de backfill, sob decisão humana.
 *
 * ## O que "compor" significa aqui, e o que ele não faz
 *
 * Sem provider, não há geração de linguagem. O que a fase entrega é
 * **reorganização de material que já existe**:
 *
 * - os conceitos vêm do casamento com o texto do próprio item, então afirmar
 *   "crochê" é repetir o que o lojista escreveu, não deduzir sobre a peça;
 * - a descrição curada de um conceito é **texto humano da curadoria**, e é o
 *   insumo mais valioso que a base tem;
 * - nada que não esteja no contexto entra no texto. Não há adjetivo de enfeite,
 *   não há material presumido, não há origem inventada.
 *
 * **Campo já preenchido não recebe proposta.** Se o lojista escreveu a
 * descrição, o assistente não oferece outra: sem geração real, substituir texto
 * humano por concatenação de conceitos seria piorar com ar de melhoria. Ele
 * propõe onde há vazio, e é por isso que a sugestão de um item bem preenchido
 * pode vir inteira nula — o que é uma resposta correta, não uma falha.
 *
 * ## Semelhantes exigem um item salvo, e isso é do desenho
 *
 * `FindSimilarProducts` compara pelo conhecimento **associado**, que só existe
 * para item que está no banco. Um cadastro em andamento não tem associação
 * nenhuma — nem deveria ter, porque nada foi salvo. Por isso o `Product` é
 * parâmetro opcional: quando ele vem, a similaridade roda; quando não vem,
 * `similarItems` fica vazio e o assistente segue funcionando com o
 * conhecimento, que é o caso do lojista digitando um item novo.
 *
 * O model entra aqui e **não** no `ListingContext`: a D-CAT-05B-3 mantém o
 * contexto livre de Eloquent, e é o assistente que faz a ponte.
 */
class GenerateListingSuggestion
{
    /** Quantos conceitos entram no texto composto, no máximo. */
    private const CONCEITOS_NO_TEXTO = 5;

    /** Quantos itens semelhantes o contexto carrega, no máximo. */
    private const SEMELHANTES_NO_CONTEXTO = 5;

    public function __construct(
        private readonly MatchProductKnowledge $matcher,
        private readonly FindSimilarProducts $semelhantes,
    ) {}

    /**
     * @param  Product|null  $produto  O item salvo, quando houver; nulo no cadastro em andamento.
     * @return array{0: ListingSuggestion, 1: ListingContext} A sugestão e o contexto que a produziu.
     */
    public function comContexto(ListingContext $contexto, ?Product $produto = null): array
    {
        $completo = $this->completar($contexto, $produto);

        return [$this->compor($completo), $completo];
    }

    public function __invoke(ListingContext $contexto, ?Product $produto = null): ListingSuggestion
    {
        return $this->comContexto($contexto, $produto)[0];
    }

    /**
     * Completa o contexto com o que o motor da CAT-04 sabe.
     *
     * Devolvido junto com a sugestão por `comContexto()` porque a CAT-07 vai
     * precisar registrar **a entrada** ao lado da saída: uma sugestão sem o
     * contexto que a produziu não é auditável, e recalcular o contexto depois
     * daria outro resultado se o texto do item tiver mudado no meio.
     */
    private function completar(ListingContext $contexto, ?Product $produto): ListingContext
    {
        $completo = $contexto->comConhecimento(
            ($this->matcher)($contexto->paraBuscaDeConhecimento())
        );

        if ($produto === null) {
            return $completo;
        }

        return $completo->comSemelhantes(
            ($this->semelhantes)($produto, self::SEMELHANTES_NO_CONTEXTO)
        );
    }

    private function compor(ListingContext $contexto): ListingSuggestion
    {
        $conceitos = $contexto->knowledge;

        if ($conceitos === []) {
            // Sem conceito não há do que compor texto — mas o que falta
            // continua sendo dito. É o estado normal de um catálogo cuja base
            // ainda não alcança o item, e não um erro.
            return ListingSuggestion::vazia($this->oQueFalta($contexto, []));
        }

        // O texto é composto **antes** de se apurar o que falta, e a ordem é o
        // ponto: uma lacuna que a própria sugestão preenche deixa de ser
        // pedido. Ver `oQueFalta()`.
        $resumo = $this->resumoSugerido($contexto, $conceitos);
        $descricao = $this->descricaoSugerida($contexto, $conceitos);

        return new ListingSuggestion(
            suggestedName: $this->nomeSugerido(),
            shortDescription: $resumo,
            description: $descricao,
            keywords: $this->palavrasChave($conceitos),
            missingInformation: $this->oQueFalta($contexto, array_keys(array_filter([
                ListingGap::ShortDescription->value => $resumo,
                ListingGap::Description->value => $descricao,
            ], fn ($v) => $v !== null))),
            source: SuggestionSource::Internal,
        );
    }

    /**
     * O caminho interno **não propõe nome**, e devolve nulo sempre.
     *
     * Renomear é o único dos campos que exige de fato escrever algo novo: o
     * resumo e a descrição podem ser compostos a partir de conceitos que o
     * próprio texto do lojista trouxe, mas um nome melhor não está contido em
     * lugar nenhum do que já existe. Concatenar conceitos num título —
     * "Tapete — crochê, feito à mão, decoração" — produziria uma etiqueta, não
     * um nome, e o lojista aplicaria uma piora.
     *
     * O campo continua existindo porque a §3.4 o nomeia e porque é exatamente o
     * que a CAT-06 terá condições de preencher. Devolver nulo é a resposta
     * honesta de quem não tem base para preferir um nome a outro.
     */
    private function nomeSugerido(): ?string
    {
        return null;
    }

    /**
     * Resumo curto, só quando não existe um.
     *
     * O formato é o nome seguido dos conceitos que o texto do item já
     * mencionava, e cabe no `varchar(500)` que a CAT-02 criou — o corte é por
     * conceito inteiro, nunca no meio de uma palavra, porque resumo truncado é
     * o defeito que aquela fase existiu para eliminar.
     *
     * @param  array<int, array{name: string, type: string, description: string|null, terms: array<int, string>}>  $conceitos
     */
    private function resumoSugerido(ListingContext $contexto, array $conceitos): ?string
    {
        if ($contexto->existingShortDescription !== null) {
            return null;
        }

        $nomes = array_slice(array_column($conceitos, 'name'), 0, self::CONCEITOS_NO_TEXTO);

        $resumo = $contexto->name.'. '.implode(', ', $nomes).'.';

        while (mb_strlen($resumo) > 500 && count($nomes) > 1) {
            array_pop($nomes);
            $resumo = $contexto->name.'. '.implode(', ', $nomes).'.';
        }

        return mb_strlen($resumo) > 500 ? null : $resumo;
    }

    /**
     * Descrição composta a partir da **descrição curada** dos conceitos.
     *
     * Este é o único ponto da fase em que texto escrito por uma pessoa da
     * curadoria chega ao lojista, e é o que dá valor real ao caminho interno:
     * "Crochê" com uma descrição curada explica a técnica melhor do que
     * qualquer coisa que o sistema montasse sozinho.
     *
     * Conceito sem descrição curada entra apenas pelo nome, na frase final —
     * não se inventa explicação para ele.
     *
     * @param  array<int, array{name: string, type: string, description: string|null, terms: array<int, string>}>  $conceitos
     */
    private function descricaoSugerida(ListingContext $contexto, array $conceitos): ?string
    {
        if ($contexto->existingDescription !== null) {
            return null;
        }

        $trechos = [];

        foreach (array_slice($conceitos, 0, self::CONCEITOS_NO_TEXTO) as $conceito) {
            $curada = trim((string) ($conceito['description'] ?? ''));

            if ($curada !== '') {
                $trechos[] = $curada;
            }
        }

        if ($trechos === []) {
            return null;
        }

        $abertura = $contexto->categoryPath === []
            ? $contexto->name.'.'
            : $contexto->name.' — '.$this->categoriaLegivel($contexto).'.';

        return $abertura.' '.implode(' ', $trechos);
    }

    /** A categoria mais específica, que é a que nomeia o item na vitrine. */
    private function categoriaLegivel(ListingContext $contexto): string
    {
        return $contexto->categoryPath[array_key_last($contexto->categoryPath)];
    }

    /**
     * Palavras-chave: nome canônico do conceito **e os termos úteis** (P-4).
     *
     * A CAT-05D entregava só nomes canônicos, e a lacuna era verificável na
     * base real: o conceito "Costura" não alcançava quem procura por *"ajuste
     * de roupa"*, que é o termo comercial cadastrado para ele. Palavra-chave
     * existe para ser encontrada, e quem procura raramente usa o nome que a
     * curadoria escolheu.
     *
     * Quais termos entram é decisão do `ContextSanitizer::termosUteis()`, que
     * é onde a regra mora: **termo comercial e sinônimo sim, grafia
     * alternativa não**. Aqui só se ordena e desduplica.
     *
     * O nome canônico vem primeiro, e é isso que a ordem garante: a lista
     * começa pelo que a curadoria nomeou e só depois oferece as variantes.
     *
     * @param  array<int, array{name: string, type: string, description: string|null, terms: array<int, string>}>  $conceitos
     * @return array<int, string>
     */
    private function palavrasChave(array $conceitos): array
    {
        $nomes = array_column($conceitos, 'name');
        $termos = array_merge(...array_values(array_column($conceitos, 'terms') ?: [[]]));

        return array_values(array_unique(array_merge($nomes, $termos)));
    }

    /**
     * O que falta, **em pedido** — não em nome de coluna.
     *
     * É a tradução que a §3.4 exige: *"em vez de inventar material, a
     * inteligência devolve 'informe o material'"*. `ListingContext::lacunas()`
     * continua sendo o insumo correto e não mudou — ele é da CAT-05C e diz o
     * que o item não tem. O que esta subfase acrescenta são duas camadas em
     * cima dele.
     *
     * **Primeira: lacuna que a sugestão preenche não vira pedido.** Se o
     * assistente está oferecendo um resumo, pedir "escreva um resumo" na mesma
     * resposta é ruído — e ruído faz o lojista desconfiar dos outros pedidos,
     * inclusive os que ele precisa mesmo atender. Sobram os que dependem de
     * alguém: categoria é escolha dele, atributo é fato que só ele sabe,
     * conhecimento é trabalho da curadoria.
     *
     * **Segunda: cada lacuna vira texto legível**, por `ListingGap::pedido()`.
     *
     * Lacuna desconhecida é descartada em vez de virar pedido vazio — mas o
     * `match` do enum é o que impede que isso aconteça em silêncio quando
     * alguém acrescentar uma lacuna nova sem tradução.
     *
     * @param  array<int, string>  $preenchidasPelaSugestao
     * @return array<int, string>
     */
    private function oQueFalta(ListingContext $contexto, array $preenchidasPelaSugestao): array
    {
        $pedidos = [];

        foreach ($contexto->lacunas() as $campo) {
            $lacuna = ListingGap::tryFrom($campo);

            if ($lacuna === null || in_array($campo, $preenchidasPelaSugestao, true)) {
                continue;
            }

            $pedidos[] = $lacuna->pedido();
        }

        return $pedidos;
    }
}
