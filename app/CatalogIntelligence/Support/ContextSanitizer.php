<?php

namespace App\CatalogIntelligence\Support;

use App\Actions\Catalog\SaveProductWithOffer;
use App\CatalogIntelligence\DTOs\KnowledgeCandidate;
use App\CatalogIntelligence\DTOs\SimilarProduct;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A minimização do contexto, feita **na construção** e não confiada a quem
 * chama (§5.1 do documento arquitetural).
 *
 * A regra da trilha é que o assistente veja o mínimo necessário para entender
 * *o que o item é*. Se a filtragem morasse em quem monta o contexto, cada nova
 * superfície — painel, API, comando, job — teria a sua própria versão da
 * regra, e a primeira que esquecesse um campo vazaria sem que nada quebrasse.
 * Aqui a fronteira é uma coisa só, testável, e o `ListingContext` não tem
 * construtor público que a contorne.
 *
 * ## Duas proteções diferentes, e convém não confundi-las
 *
 * **O contexto inteiro é lista de permissão.** `ListingContext` tem campos
 * fixos, construtor privado e nenhum parâmetro que aceite `ProductOffer` ou
 * `Expositor`. Não existe caminho por onde um campo não previsto entre: ele
 * teria de ser declarado antes. Vale para tudo o que vem de `products`, e para
 * a redução de `KnowledgeCandidate` e `SimilarProduct` a escalares —
 * `conhecimento()` e `semelhantes()` **nomeiam** o que sai, e o resto do model
 * fica para trás.
 *
 * **`knownAttributes` é a exceção, e é lista de proibição.** Ele existe para
 * receber o que o formulário informou, e o domínio ainda não tem campo
 * estruturado de material, técnica ou cor (a CAT-02 os deixou fora de
 * `products` de propósito). Sem vocabulário fechado não há como escrever uma
 * whitelist: qualquer chave é potencialmente legítima.
 *
 * A consequência precisa estar dita, porque denylist protege contra o que
 * alguém lembrou de listar: uma chave sensível com nome que não está em
 * `CAMPOS_PROIBIDOS` passa. A mitigação é que o valor precisa ser escalar, que
 * a lista de campos da oferta vem do domínio e não de uma cópia, e que quem
 * preenche `knownAttributes` é código nosso — não um payload aberto vindo do
 * lojista. No dia em que existir vocabulário de atributos, isto vira whitelist
 * e a exceção acaba.
 *
 * **Essa última mitigação é uma obrigação, não uma observação** (dívida C-1):
 * quem for popular `knownAttributes` a partir de formulário — a CAT-09 — deve
 * mapear campo a campo, e nunca repassar `$request->all()` nem o array de
 * propriedades do Livewire em bloco. A regra está escrita por extenso em
 * `ListingContext::paraItemNovo()`, ao lado do parâmetro que a exige, e
 * rastreada em `CAT_05C_LISTING_CONTEXT_E_SANITIZER.md`.
 *
 * ## O que a CAT-DOM-02H tornou estrutural
 *
 * Boa parte desta proteção deixou de depender de vigilância. Com os doze
 * espelhos comerciais removidos de `products`, um contexto montado a partir do
 * produto **não tem** preço nem estoque para vazar: eles não estão na tabela.
 * O que resta a defender é a superfície por onde alguém *passa* dados —
 * `knownAttributes`, que vem de fora — e a redução de models a escalares.
 *
 * ## Limitação conhecida: texto livre não é redigido
 *
 * Esta classe filtra **campos**. Ela não varre `name`, `short_description` ou
 * `description` procurando um telefone ou um e-mail que o lojista tenha
 * escrito dentro da própria descrição — "chama no meu zap" continua entrando
 * no contexto, porque é conteúdo que ele publicou de propósito no catálogo
 * público.
 *
 * Registrado aqui porque a §5.1 lista telefone e e-mail entre o que nunca sai
 * para provider externo, e essa promessa só é integralmente verdadeira no
 * nível de campo. Enquanto não houver provider (CAT-06), a diferença é teórica
 * — o texto não sai da aplicação. Decidir se a redação de texto livre entra, e
 * onde, é assunto da CAT-05F ou da CAT-10.
 */
class ContextSanitizer
{
    /**
     * As categorias de dado que nunca entram no contexto, nomeadas uma a uma.
     *
     * Vem do §5.2 da CAT-05B, que por sua vez vem do §5.1 do documento
     * arquitetural. Está agrupada por categoria, e não achatada numa lista
     * única, porque é assim que os testes a percorrem: um caso por categoria,
     * e não um caso genérico que passaria mesmo se metade da lista sumisse.
     *
     * Governa apenas `knownAttributes` — o resto do contexto é protegido por
     * declaração de campo, não por esta lista.
     *
     * @var array<string, array<int, string>>
     */
    public const CAMPOS_PROIBIDOS = [
        'identidade_pessoal' => [
            'user_id', 'user', 'user_name', 'nome_do_usuario', 'username',
            'first_name', 'last_name', 'cpf', 'cnpj', 'documento', 'rg',
        ],
        'contato' => [
            'email', 'e_mail', 'telefone', 'phone', 'celular', 'whatsapp',
        ],
        'endereco' => [
            'endereco', 'address', 'logradouro', 'numero', 'complemento',
            'bairro', 'cidade', 'estado', 'cep', 'zip', 'zipcode',
        ],
        'rastreamento' => [
            'ip', 'ip_address', 'cookie', 'cookies', 'visitor_uuid',
            'session_uuid', 'session_id', 'user_agent',
        ],
        'pedido' => [
            'order_id', 'order', 'pedido_id', 'order_item_id', 'total',
            'payment', 'pagamento', 'mercado_pago_id', 'split',
        ],
    ];

    /**
     * Tudo o que pertence à oferta, e não ao item.
     *
     * A lista **não é escrita aqui**: vem de `SaveProductWithOffer`, que é onde
     * o domínio decide o que é condição de venda. Repetir os nomes criaria uma
     * segunda definição que envelheceria na primeira coluna nova de
     * `product_offers` — e o vazamento seria silencioso, porque o campo novo
     * simplesmente não estaria na cópia.
     *
     * Os doze espelhos legados entram junto por precaução: eles não existem
     * mais em `products`, mas podem chegar por `knownAttributes` vindos de um
     * payload antigo, e o lugar de recusá-los é aqui.
     *
     * @return array<int, string>
     */
    public static function camposDaOferta(): array
    {
        return array_values(array_unique(array_merge(
            SaveProductWithOffer::CAMPOS_DA_OFERTA,
            SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS,
            // Colunas de `product_offers` que não passam pelo formulário e por
            // isso não estão nas constantes acima.
            ['product_id', 'expositor_id', 'offer_id', 'product_offer_id', 'reserved_quantity'],
        )));
    }

    /** @return array<int, string> */
    public static function todosOsCamposProibidos(): array
    {
        return array_merge(
            array_merge(...array_values(self::CAMPOS_PROIBIDOS)),
            self::camposDaOferta(),
        );
    }

    /**
     * Atributos informados por quem chama, reduzidos ao que é seguro e útil.
     *
     * Este é o único ponto do contexto por onde entra dado que a aplicação não
     * leu de `products` — e portanto o único que precisa de filtro de verdade.
     *
     * Três coisas acontecem aqui, nesta ordem:
     *
     * 1. **chave proibida some**, comparada sem acento e sem maiúscula, para
     *    que `E-mail`, `email` e `e_mail` caiam do mesmo jeito;
     * 2. **valor não escalar some** — array, objeto e model não são atributo de
     *    item, e um model aqui seria a porta por onde relação carregada entra
     *    inteira;
     * 3. **valor vazio some**, porque `knownAttributes` significa *o que foi
     *    informado*, e string vazia não é informação.
     *
     * O que ele **não** faz é inventar: nenhum valor é derivado, inferido ou
     * completado. Um atributo que não foi passado não existe, e a ausência é o
     * que a CAT-05E vai transformar em `missing_information` em vez de em
     * texto adivinhado.
     *
     * @param  array<string, mixed>  $atributos
     * @return array<string, scalar>
     */
    public function atributos(array $atributos): array
    {
        $proibidos = array_map(
            fn (string $c) => $this->chaveNormalizada($c),
            self::todosOsCamposProibidos(),
        );

        $limpos = [];

        foreach ($atributos as $chave => $valor) {
            if (! is_string($chave) || in_array($this->chaveNormalizada($chave), $proibidos, true)) {
                continue;
            }

            if ($valor instanceof Model || ! is_scalar($valor)) {
                continue;
            }

            if (is_string($valor) && trim($valor) === '') {
                continue;
            }

            $limpos[$chave] = $valor;
        }

        return $limpos;
    }

    /**
     * O campo é proibido? Pergunta pública para que os testes e as fases
     * seguintes não reimplementem a comparação.
     */
    public function campoEhProibido(string $campo): bool
    {
        return in_array(
            $this->chaveNormalizada($campo),
            array_map(fn (string $c) => $this->chaveNormalizada($c), self::todosOsCamposProibidos()),
            true,
        );
    }

    /**
     * Conceitos relevantes, reduzidos a texto.
     *
     * Só entra o que está **aprovado**. É a mesma regra que o matcher já aplica
     * na consulta, repetida aqui de propósito: o contexto pode ser montado a
     * partir de candidatos vindos de qualquer lugar — inclusive de uma fase
     * futura que os traga de outro caminho —, e um conceito em rascunho
     * influenciando o texto sugerido a um lojista é exatamente o que a CAT-03
     * criou `status` para impedir.
     *
     * O `KnowledgeEntry` fica de fora: sai nome, tipo e descrição curada, que é
     * o que serve de insumo. Devolver o model deixaria relações, timestamps e
     * proveniência ao alcance de quem consumisse o contexto.
     *
     * @param  Collection<int, KnowledgeCandidate>|array<int, KnowledgeCandidate>  $candidatos
     * @return array<int, array{name: string, type: string, description: string|null}>
     */
    public function conhecimento(Collection|array $candidatos): array
    {
        return collect($candidatos)
            ->filter(fn (KnowledgeCandidate $c) => $c->entry->status === KnowledgeStatus::Approved)
            ->map(fn (KnowledgeCandidate $c) => [
                'name' => (string) $c->entry->name,
                'type' => $c->entry->type->value,
                'description' => $c->entry->description,
            ])
            ->values()
            ->all();
    }

    /**
     * Itens semelhantes, reduzidos a referência.
     *
     * `SimilarProduct` carrega o `Product` inteiro — é o que a tela da CAT-09
     * vai querer, e é exatamente o que o contexto não pode ter. Sobram nome,
     * conceitos compartilhados e a razão em português; some tudo o mais,
     * inclusive `product_id`, que não ajuda a escrever texto e é referência
     * interna.
     *
     * **A vigência não é conferida aqui.** Ela já foi decidida e aplicada em
     * `FindSimilarProducts` (D-CAT-05B-2), que é a única consulta autorizada a
     * responder quem se parece com quem. Reconferir criaria a segunda
     * definição de vigência que a CAT-DOM-01 existiu para eliminar — e a
     * segunda definição é sempre a que envelhece.
     *
     * @param  Collection<int, SimilarProduct>|array<int, SimilarProduct>  $semelhantes
     * @return array<int, array{name: string, shared_concepts: array<int, string>, reasons: array<int, string>}>
     */
    public function semelhantes(Collection|array $semelhantes): array
    {
        return collect($semelhantes)
            ->map(fn (SimilarProduct $s) => [
                'name' => (string) $s->product->name,
                'shared_concepts' => array_values(array_unique($s->sharedConcepts)),
                'reasons' => array_map(fn ($r) => $r->description, $s->reasons),
            ])
            ->values()
            ->all();
    }

    /**
     * Texto de catálogo, normalizado para ausência.
     *
     * Não redige nem reescreve — ver a limitação declarada no cabeçalho da
     * classe. Só resolve a diferença entre "não informado" e "informado em
     * branco", que precisa ser uma coisa só para a CAT-05E saber o que virou
     * `missing_information`.
     */
    public function texto(?string $valor): ?string
    {
        $limpo = trim((string) $valor);

        return $limpo === '' ? null : $limpo;
    }

    /** Minúsculas, sem acento e sem separador: `E-mail`, `email` e `e_mail` viram a mesma chave. */
    private function chaveNormalizada(string $chave): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($chave))) ?? '';
    }
}
