<?php

namespace App\CatalogIntelligence\Actions;

use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingOutcome;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\KnowledgeSufficiency;
use App\CatalogIntelligence\Enums\ListingGap;
use App\CatalogIntelligence\Enums\ListingOutcomeState;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Exceptions\CatalogAiProviderException;
use App\CatalogIntelligence\Queries\FindSimilarProducts;
use App\CatalogIntelligence\Support\GuardedPromptRedactor;
use App\CatalogIntelligence\Support\KnowledgeNormalizer;
use App\CatalogIntelligence\Support\PromptGuard;
use App\CatalogIntelligence\Support\ProviderResponseValidator;
use App\CatalogIntelligence\Support\SuggestionPolicy;
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
 * ## Dois caminhos, e quem decide entre eles (CAT-06G)
 *
 * O caminho interno roda sempre, e primeiro: é barato, determinístico e é a
 * sugestão que sobra quando nada de fora ajuda. Depois dele a `SuggestionPolicy`
 * responde o "conhecimento suficiente?" do fluxograma da §1, e **só** o veredito
 * `ExternalMayHelp` leva ao provider. A decisão mora aqui, e não em quem chama.
 *
 * A saída tem uma ordem que não se inverte: o `PromptGuard` separa instrução,
 * contexto e dado (S-1); o `GuardedPromptRedactor` redige o conteúdo dos dois
 * canais não confiáveis (C-2); só então o provider recebe o prompt; e a resposta
 * passa pelo `ProviderResponseValidator` antes de qualquer campo ser aproveitado.
 * O contexto devolvido a quem chama continua o original — a redação é da
 * fronteira de saída, não do caminho interno (D-CAT-06B-2).
 *
 * A resposta externa **complementa** a interna e nunca a substitui: ver
 * `complementar()`.
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
 * ## Falha da inteligência não bloqueia nada (CAT-05F, CAT-06G)
 *
 * As duas chamadas ao motor da CAT-04 são capturadas aqui dentro. Se o
 * casamento ou a similaridade lançarem, a sugestão degrada — vazia ou sem
 * semelhantes — e **nenhuma exceção do motor sai desta Action**. É a regra 3
 * das invioláveis implementada no único ponto onde ninguém pode esquecê-la.
 *
 * Do provider, captura-se **só** `CatalogAiProviderException`, a falha esperada
 * da fronteira (D-CAT-06G-7). Qualquer outra exceção vinda de lá é defeito e
 * sobe: tratar `TypeError` como "provider fora do ar" anunciaria um defeito
 * permanente como falha transitória. Não há nova tentativa (D-CAT-06G-6).
 *
 * ## O desfecho — F-1
 *
 * `comContexto()` devolve, ao lado da sugestão e do contexto, um
 * `ListingOutcome` que diz em que condição a sugestão foi produzida. É por ele —
 * e não por sugestão vazia, `source` ou `missing_information` — que se distingue
 * *"a base não conhece este item"* de *"a inteligência falhou"*, sem reabrir a
 * forma da §3.4. Os estados são exaustivos: ver `ListingOutcomeState`.
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
        private readonly SuggestionPolicy $politica,
        private readonly CatalogAiProvider $provider,
        private readonly PromptGuard $guard,
        private readonly GuardedPromptRedactor $redator,
        private readonly ProviderResponseValidator $validador,
        private readonly KnowledgeNormalizer $normalizador,
    ) {}

    /**
     * A sugestão, o contexto que a produziu e o desfecho (D-CAT-06B-1, D-CAT-06G-3).
     *
     * A ordem das decisões:
     *
     * 1. completa o contexto e compõe a sugestão interna — sempre;
     * 2. se a etapa de conhecimento falhou, termina em `InternalIntelligenceFailed`
     *    sem consultar o provider: a lacuna de conhecimento que a política veria
     *    seria produto da falha, não um fato do item, e consultar fora por causa
     *    dela seria pagar pela queda da base (D-CAT-06G-4);
     * 3. a política decide — `Sufficient` e `AwaitsMerchant` terminam aqui;
     * 4. `ExternalMayHelp` segue para `consultarProvider()`.
     *
     * @param  Product|null  $produto  O item salvo, quando houver; nulo no cadastro em andamento.
     * @return array{0: ListingSuggestion, 1: ListingContext, 2: ListingOutcome}
     */
    public function comContexto(ListingContext $contexto, ?Product $produto = null): array
    {
        [$completo, $conhecimentoFalhou] = $this->completar($contexto, $produto);

        $interna = $this->compor($completo);

        if ($conhecimentoFalhou) {
            return [$interna, $completo, ListingOutcome::de(ListingOutcomeState::InternalIntelligenceFailed)];
        }

        $veredito = ($this->politica)($completo);

        if ($veredito->justificaConsultaExterna()) {
            [$sugestao, $desfecho] = $this->consultarProvider($completo, $interna);

            return [$sugestao, $completo, $desfecho];
        }

        return [$interna, $completo, ListingOutcome::de(match ($veredito) {
            KnowledgeSufficiency::Sufficient => ListingOutcomeState::InternalKnowledgeSufficient,
            KnowledgeSufficiency::AwaitsMerchant => ListingOutcomeState::InternalKnowledgeInsufficient,
        })];
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
     *
     * Diz também se a etapa de conhecimento falhou (CAT-06G): é o que separa
     * `InternalIntelligenceFailed` de uma base que simplesmente não conhece o
     * item. A similaridade não entra nessa conta — é acessória (D-CAT-05F-2).
     *
     * @return array{0: ListingContext, 1: bool} O contexto completado, e se o conhecimento falhou.
     */
    private function completar(ListingContext $contexto, ?Product $produto): array
    {
        $completo = $contexto;
        $conhecimentoFalhou = false;

        try {
            $completo = $completo->comConhecimento(
                ($this->matcher)($contexto->paraBuscaDeConhecimento())
            );
        } catch (Throwable $falha) {
            $this->registrarDegradacao('conhecimento', $falha);
            $conhecimentoFalhou = true;
        }

        if ($produto === null) {
            return [$completo, $conhecimentoFalhou];
        }

        try {
            $completo = $completo->comSemelhantes(
                ($this->semelhantes)($produto, self::SEMELHANTES_NO_CONTEXTO)
            );
        } catch (Throwable $falha) {
            $this->registrarDegradacao('semelhantes', $falha);
        }

        return [$completo, $conhecimentoFalhou];
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

    /**
     * A consulta externa, com a fronteira protegida e a resposta desconfiada.
     *
     * Só chega aqui com o veredito `ExternalMayHelp`. Cada saída tem o seu
     * desfecho, e todas devolvem uma sugestão — a interna, quando nada de fora
     * pode ser aproveitado.
     *
     * Os dois `try` envolvem **só** as chamadas ao provider. O prompt é montado
     * fora deles: guard, redator e validador nunca lançam por contrato, e um
     * defeito em qualquer um tem de aparecer como defeito, não como falha do
     * provider. A tentativa é uma só (D-CAT-06G-6).
     *
     * @return array{0: ListingSuggestion, 1: ListingOutcome}
     */
    private function consultarProvider(ListingContext $contexto, ListingSuggestion $interna): array
    {
        try {
            $disponivel = $this->provider->isAvailable();
        } catch (CatalogAiProviderException $falha) {
            return [$interna, $this->falhaDoProvider('provider_disponibilidade', $falha)];
        }

        if (! $disponivel) {
            return [$interna, ListingOutcome::de(ListingOutcomeState::ProviderUnavailable)];
        }

        $prompt = ($this->redator)(($this->guard)($contexto));

        try {
            $resposta = $this->provider->suggest($prompt);
        } catch (CatalogAiProviderException $falha) {
            return [$interna, $this->falhaDoProvider('provider_sugestao', $falha)];
        }

        $violacoes = $this->validador->violacoes($resposta);

        if ($violacoes !== []) {
            return [$interna, ListingOutcome::respostaInvalida($violacoes)];
        }

        return $this->complementar($contexto, $interna, $resposta);
    }

    /**
     * Registra a falha esperada do provider e devolve o desfecho dela.
     *
     * Classe do provider, etapa e classe da exceção — e **nada da mensagem**: quem
     * a escreveu foi o adaptador, e uma falha de transporte pode carregar trecho
     * do prompt ou da resposta. É a regra do `mensagemSegura()`, mais estrita,
     * porque aqui não há mensagem que se saiba segura.
     */
    private function falhaDoProvider(string $etapa, CatalogAiProviderException $falha): ListingOutcome
    {
        Log::warning('catalog-intelligence: assistente degradado', [
            'etapa' => $etapa,
            'provider' => $this->provider::class,
            'excecao' => $falha::class,
        ]);

        return ListingOutcome::de(ListingOutcomeState::ProviderFailed);
    }

    /**
     * A resposta externa válida **complementa** a sugestão interna (D-CAT-06G-8).
     *
     * O validador diz se a resposta pode ser usada; esta composição diz o que dela
     * entra. As regras são as que o caminho interno já seguia:
     *
     * - **texto externo só entra onde nada foi escrito nem proposto**: campo que o
     *   lojista preencheu não recebe proposta (D-CAT-05D-4), e texto que a base já
     *   compôs fica — texto curado não é trocado por texto de fora;
     * - o **nome** é decidido em `nomeSugerido()`, e nome equivalente ao atual não
     *   entra;
     * - **palavras-chave internas primeiro**, externas depois, sem repetir o que a
     *   base já trouxe;
     * - **`missing_information` é recalculado** por `oQueFalta()` sobre o que a
     *   sugestão final preenche — a lista do provider é descartada, porque pedir ao
     *   lojista é regra da Feira, e não do fornecedor (D-CAT-05E-6);
     * - `source` vira `External`, e `confidence` vem da resposta, só quando algo
     *   dela entrou.
     *
     * Proposta para campo que já tinha texto é descartada e **não** é violação: o
     * validador olha a forma da resposta (D-CAT-06D-2), e a resposta está bem
     * formada — só não tem onde entrar.
     *
     * Se nada entrar, a sugestão devolvida é a interna, intacta, e o desfecho é
     * `ExternalSuggestionNotUsed` (D-CAT-06G-11): o provider foi consultado e
     * respondeu bem, o que não se confunde com a base não ter bastado.
     *
     * @return array{0: ListingSuggestion, 1: ListingOutcome}
     */
    private function complementar(ListingContext $contexto, ListingSuggestion $interna, ListingSuggestion $externa): array
    {
        $nome = $this->nomeSugerido($contexto, $externa);

        $resumoExterno = $interna->shortDescription === null && $contexto->existingShortDescription === null
            ? $externa->shortDescription
            : null;

        $descricaoExterna = $interna->description === null && $contexto->existingDescription === null
            ? $externa->description
            : null;

        $palavrasExternas = $this->palavrasChaveNovas($interna->keywords, $externa->keywords);

        if ($nome === null && $resumoExterno === null && $descricaoExterna === null && $palavrasExternas === []) {
            return [$interna, ListingOutcome::de(ListingOutcomeState::ExternalSuggestionNotUsed)];
        }

        $resumo = $interna->shortDescription ?? $resumoExterno;
        $descricao = $interna->description ?? $descricaoExterna;

        return [
            new ListingSuggestion(
                suggestedName: $nome,
                shortDescription: $resumo,
                description: $descricao,
                keywords: [...$interna->keywords, ...$palavrasExternas],
                missingInformation: $this->oQueFalta($contexto, $this->preenchidas($resumo, $descricao)),
                source: SuggestionSource::External,
                confidence: $externa->confidence,
            ),
            ListingOutcome::de(ListingOutcomeState::ExternalSuggestionUsed),
        ];
    }

    /**
     * As palavras-chave externas que a base ainda não trouxe.
     *
     * A comparação é pela chave do `KnowledgeNormalizer`, a mesma que decide se
     * dois conceitos são um só: "croche" não entra ao lado de "Crochê" — o mesmo
     * motivo que deixou `alias` fora das palavras-chave (D-CAT-05E-2).
     *
     * @param  array<int, string>  $internas
     * @param  array<int, string>  $externas
     * @return array<int, string>
     */
    private function palavrasChaveNovas(array $internas, array $externas): array
    {
        $vistas = array_map(fn (string $palavra) => $this->normalizador->normalize($palavra), $internas);
        $novas = [];

        foreach ($externas as $palavra) {
            $chave = $this->normalizador->normalize($palavra);

            if ($chave === '' || in_array($chave, $vistas, true)) {
                continue;
            }

            $vistas[] = $chave;
            $novas[] = $palavra;
        }

        return $novas;
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
            suggestedName: $this->nomeSugerido($contexto),
            shortDescription: $resumo,
            description: $descricao,
            keywords: $this->palavrasChave($conceitos),
            missingInformation: $this->oQueFalta($contexto, $this->preenchidas($resumo, $descricao)),
            source: SuggestionSource::Internal,
        );
    }

    /**
     * As lacunas de texto que uma sugestão preenche — para `oQueFalta()` não pedir
     * o que ela já oferece. Serve a sugestão interna e a complementada.
     *
     * @return array<int, string>
     */
    private function preenchidas(?string $resumo, ?string $descricao): array
    {
        return array_keys(array_filter([
            ListingGap::ShortDescription->value => $resumo,
            ListingGap::Description->value => $descricao,
        ], fn ($v) => $v !== null));
    }

    /**
     * O caminho interno **não propõe nome**; nome só vem de fora.
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
     *
     * **CAT-06G.** Quando uma resposta externa válida propõe nome, é aqui que ele
     * entra (D-CAT-06G-8). É a única proposta que não esbarra na D-CAT-05D-4: o
     * nome é obrigatório e está sempre preenchido, então "campo preenchido não
     * recebe proposta" fecharia para sempre o campo que esta sugestão existe para
     * oferecer. Aplicar ou não continua sendo escolha do lojista, na CAT-09.
     *
     * Só entra nome **diferente** do atual (D-CAT-06G-12) — diferente pela chave do
     * `KnowledgeNormalizer`, a mesma que desduplica palavras-chave, e não por byte.
     * "Tapete de Croche" proposto para "Tapete de crochê" é o mesmo nome, e
     * oferecê-lo seria fingir contribuição.
     */
    private function nomeSugerido(ListingContext $contexto, ?ListingSuggestion $externa = null): ?string
    {
        if ($externa?->suggestedName === null) {
            return null;
        }

        return $this->normalizador->normalize($externa->suggestedName) === $this->normalizador->normalize($contexto->name)
            ? null
            : $externa->suggestedName;
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
