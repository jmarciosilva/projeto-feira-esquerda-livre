<?php

namespace App\CatalogIntelligence\Actions;

use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\ListingGap;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Queries\FindSimilarProducts;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

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
 *
 * ## Falha da inteligência não bloqueia nada (CAT-05F)
 *
 * As duas chamadas ao motor da CAT-04 são capturadas aqui dentro. Se o
 * casamento ou a similaridade lançarem, a sugestão degrada — vazia ou sem
 * semelhantes — e **nenhuma exceção sai desta Action**. É a regra 3 das
 * invioláveis implementada no único ponto onde ninguém pode esquecê-la.
 *
 * **Limitação conhecida, e é dívida (F-1).** Quem recebe a sugestão não
 * consegue distinguir *"a base não conhece este item"* de *"a inteligência
 * falhou"*: os dois devolvem `ListingSuggestion::vazia()`. A §3.3 prevê que a
 * UI informe o modo degradado, e para isso a distinção precisará existir —
 * mas dar um campo novo à sugestão reabriria a forma da §3.4, congelada na
 * CAT-05D. Fica endereçada à **CAT-06**, quando existir um segundo modo de
 * falha real (provider fora do ar) e a distinção passar a valer o campo.
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
        $completo = $contexto;

        try {
            $completo = $completo->comConhecimento(
                ($this->matcher)($contexto->paraBuscaDeConhecimento())
            );
        } catch (Throwable $falha) {
            $this->registrarDegradacao('conhecimento', $falha);
        }

        if ($produto === null) {
            return $completo;
        }

        try {
            $completo = $completo->comSemelhantes(
                ($this->semelhantes)($produto, self::SEMELHANTES_NO_CONTEXTO)
            );
        } catch (Throwable $falha) {
            $this->registrarDegradacao('semelhantes', $falha);
        }

        return $completo;
    }

    /**
     * A falha é registrada e engolida — de propósito (CAT-05F).
     *
     * ## Por que capturar em vez de propagar
     *
     * A regra 3 das invioláveis: *"Falha da inteligência não bloqueia cadastro.
     * Provider fora do ar, sem credencial, resposta inválida, timeout — o
     * cadastro manual continua funcionando integralmente."*
     *
     * O assistente é a **única porta** que o cadastro conhece (§3.2). Se ele
     * propagasse, cada superfície futura precisaria do seu próprio `try/catch`,
     * e a primeira que esquecesse quebraria a regra 3 sem que nada acusasse. É
     * o mesmo raciocínio que fez a minimização morar no `ContextSanitizer` em
     * vez de em quem chama: a garantia mora onde não dá para esquecê-la.
     *
     * ## Degradação parcial, não total
     *
     * As duas etapas são capturadas **em separado**, e a ordem importa. Se o
     * casamento falha, não há conceito e `compor()` devolve a sugestão vazia
     * pelo caminho que já existia. Se falha só a similaridade, o conhecimento
     * continua de pé e a sugestão sai completa — o que se perde é a lista de
     * itens semelhantes, que é acessório. Capturar as duas juntas jogaria fora
     * um resultado bom por causa de um acessório que falhou.
     *
     * ## O que fica registrado, e o que não pode ficar
     *
     * `Log::warning` com a etapa e a classe da exceção, para que uma base de
     * conhecimento quebrada não vire silenciosamente "nenhuma sugestão" — que é
     * o custo real de engolir exceção, e a razão de isto não ser um `catch`
     * vazio.
     *
     * A mensagem passa por `mensagemSegura()`. Ver o porquê lá: a §5.3 proíbe
     * conteúdo sensível em log, e há um tipo de exceção que carrega o texto do
     * lojista dentro da própria mensagem.
     */
    private function registrarDegradacao(string $etapa, Throwable $falha): void
    {
        Log::warning('catalog-intelligence: assistente degradado', [
            'etapa' => $etapa,
            'excecao' => $falha::class,
            'mensagem' => $this->mensagemSegura($falha),
        ]);
    }

    /**
     * A mensagem da exceção, sem o texto do lojista dentro.
     *
     * `QueryException::getMessage()` **interpola os bindings no SQL**. Uma falha
     * no matcher registraria, em texto puro no log, o nome e a descrição que o
     * lojista digitou — e a dívida **C-2** diz exatamente que esse texto pode
     * conter telefone ou e-mail que ele escreveu na descrição. O vazamento
     * aconteceria no log, sem provider externo nenhum.
     *
     * A §5.3 é explícita: *"Sem registrar conteúdo sensível em log."* Por isso
     * a exceção de banco entra pelo código SQLSTATE, que é o que serve ao
     * diagnóstico, e o SQL fica de fora. Todo o resto entra pela mensagem
     * normal — uma `RuntimeException` do próprio módulo não carrega dado de
     * ninguém.
     */
    private function mensagemSegura(Throwable $falha): string
    {
        if ($falha instanceof QueryException) {
            return 'QueryException SQLSTATE['.$falha->getCode().'] — SQL e bindings omitidos (§5.3)';
        }

        return $falha->getMessage();
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
