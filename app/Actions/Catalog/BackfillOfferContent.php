<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * CAT-DOM-02D — popula a estrutura de conteúdo por oferta a partir do legado.
 *
 * ## Duas execuções, com regras diferentes
 *
 * | | `MODO_INICIAL` | `MODO_RECONCILIAR` |
 * |---|---|---|
 * | Quando | ao implementar a 02D | imediatamente antes de a 02E trocar writers |
 * | Destino já preenchido | **não sobrescreve** | compara e, se divergiu, **substitui** |
 * | Pode apagar? | nunca | sim, e só o que o próprio backfill criou |
 *
 * Confundir as duas foi o defeito da primeira versão da especificação. A regra
 * "pular se o destino já está preenchido" é correta para a execução normal e
 * **inutiliza a reconciliação**: entre a 02D e a 02E o lojista continua
 * escrevendo pelo caminho antigo, que segue sendo o único ativo, e tudo o que
 * ele mudou nessa janela ficaria para trás.
 *
 * ## Por que isto não mora numa migration
 *
 * O filesystem não participa da transação SQL. Um `down()` desfaria o banco e
 * deixaria os arquivos, criando a aparência de rollback atômico que o sistema
 * não tem — pior do que não ter rollback, porque induz a confiar nele. E a
 * reconciliação acontece **semanas depois** da migration: dentro dela, não
 * teria como rodar de novo.
 *
 * ## A premissa que autoriza apagar
 *
 * Enquanto a 02E não habilitar seus writers, `product_offers.images` e
 * `product_offer_faqs` são **propriedade exclusiva deste backfill**: nenhum
 * caminho da aplicação escreve neles, logo todo arquivo que referenciam foi
 * criado aqui, e apagá-lo não destrói trabalho de ninguém. A premissa deixa de
 * valer no instante em que o primeiro writer da 02E entrar em operação — por
 * isso a reconciliação exige confirmação explícita de quem a dispara (D11-C).
 */
final class BackfillOfferContent
{
    public const MODO_INICIAL = 'inicial';

    public const MODO_RECONCILIAR = 'reconciliar';

    /** @var array<string, int> */
    private array $metricas = [];

    /** @var list<string> */
    private array $erros = [];

    /**
     * @return array{metricas: array<string,int>, erros: list<string>, sucesso: bool}
     */
    public function __invoke(string $modo, bool $simular = false): array
    {
        $this->metricas = array_fill_keys([
            'ofertas_elegiveis',
            'imagens_ofertas_populadas',
            'imagens_ofertas_preservadas',
            'imagens_ofertas_substituidas',
            'imagens_arquivos_copiados',
            'imagens_arquivos_removidos',
            'imagens_fontes_ausentes',
            'imagens_falhas',
            'faq_origem',
            'faq_destino_criadas',
            'faq_destino_removidas',
            'faq_ofertas_preservadas',
            'faq_nao_resolvidas',
            'perguntas_resolvidas',
            'perguntas_nao_resolvidas',
        ], 0);
        $this->erros = [];

        $this->processarImagens($modo, $simular);
        $this->processarFaqs($modo, $simular);
        $this->processarPerguntas($simular);

        return [
            'metricas' => $this->metricas,
            'erros' => $this->erros,
            'sucesso' => $this->erros === [],
        ];
    }

    /**
     * Produtos com **exatamente uma** oferta — a única cardinalidade em que a
     * associação é determinística.
     *
     * Nunca inferir por `expositor_id`, por delegação canônica ou pela primeira
     * oferta encontrada: atribuir conteúdo à loja errada faz um comerciante
     * responder pelo outro, e o custo de errar é maior que o de deixar pendente.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProductOffer>
     */
    private function ofertasDeterministicas()
    {
        return ProductOffer::query()
            ->whereIn('product_id', $this->produtosComOfertaUnica())
            ->with('product')
            ->orderBy('id')
            ->get();
    }

    /** Subconsulta reutilizada: ids de produto com exatamente uma oferta. */
    private function produtosComOfertaUnica(): \Closure
    {
        return function ($query) {
            $query->from('product_offers')
                ->select('product_id')
                ->groupBy('product_id')
                ->havingRaw('COUNT(*) = 1');
        };
    }

    // ---------------------------------------------------------------- imagens

    private function processarImagens(string $modo, bool $simular): void
    {
        foreach ($this->ofertasDeterministicas() as $oferta) {
            $this->metricas['ofertas_elegiveis']++;

            $fonte = $oferta->product?->images ?? [];
            $atual = $oferta->images ?? [];

            if ($modo === self::MODO_INICIAL && $atual !== []) {
                $this->metricas['imagens_ofertas_preservadas']++;

                continue;
            }

            if ($fonte === []) {
                continue;
            }

            if ($modo === self::MODO_RECONCILIAR && $atual !== [] && $this->projecaoAindaFiel($fonte, $atual)) {
                $this->metricas['imagens_ofertas_preservadas']++;

                continue;
            }

            $this->projetarImagens($oferta, $fonte, $atual, $simular);
        }
    }

    /**
     * A projeção ainda representa a fonte?
     *
     * **Não se compara path** — eles nunca são iguais, por construção (§17). A
     * comparação é por **conteúdo**: o hash dos arquivos da origem contra o dos
     * arquivos que a oferta referencia. Exata, e o volume torna o custo de ler
     * disco irrelevante. Comparar `updated_at` seria mais barato e menos
     * preciso, produzindo recópias por qualquer edição do produto.
     *
     * @param  list<array<string,string>>  $fonte
     * @param  list<array<string,string>>  $destino
     */
    private function projecaoAindaFiel(array $fonte, array $destino): bool
    {
        if (count($fonte) !== count($destino)) {
            return false;
        }

        $disk = Storage::disk('public');

        foreach ($fonte as $i => $entrada) {
            foreach (['thumb', 'medium'] as $chave) {
                $origem = $entrada[$chave] ?? null;
                $copia = $destino[$i][$chave] ?? null;

                if ($origem === null && $copia === null) {
                    continue;
                }

                if ($origem === null || $copia === null) {
                    return false;
                }

                if (! $disk->exists($origem) || ! $disk->exists($copia)) {
                    return false;
                }

                if (md5($disk->get($origem)) !== md5($disk->get($copia))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Copia os arquivos, valida o conjunto e só então persiste.
     *
     * A ordem é a compensação: banco e disco não compartilham transação, então
     * o JSON só é gravado depois de **todos** os arquivos existirem. Se algo
     * falhar antes disso, as cópias daquela tentativa são removidas e a oferta
     * fica exatamente como estava — nunca apontando para um conjunto
     * parcialmente copiado como se estivesse íntegro. A origem jamais é tocada.
     *
     * @param  list<array<string,string>>  $fonte
     * @param  list<array<string,string>>  $anteriores
     */
    private function projetarImagens(ProductOffer $oferta, array $fonte, array $anteriores, bool $simular): void
    {
        $disk = Storage::disk('public');
        $criados = [];
        $projecao = [];

        try {
            foreach ($fonte as $entrada) {
                $novo = [];

                // `thumb` e `medium` da mesma entrada apontando para o mesmo
                // arquivo geram **uma** cópia, reaproveitada nas duas chaves. O
                // compartilhamento fica dentro da entrada, e as duas morrem
                // juntas — diferente do que o §17 proíbe, que é compartilhar
                // entre o produto e a oferta.
                $porOrigem = [];

                foreach (['thumb', 'medium'] as $chave) {
                    $origem = $entrada[$chave] ?? null;

                    if ($origem === null || $origem === '') {
                        continue;
                    }

                    if (! $disk->exists($origem)) {
                        $this->metricas['imagens_fontes_ausentes']++;

                        throw new RuntimeException("arquivo de origem inexistente: {$origem}");
                    }

                    if (isset($porOrigem[$origem])) {
                        $novo[$chave] = $porOrigem[$origem];

                        continue;
                    }

                    $destino = $this->nomeDeDestino($origem, $chave);

                    if (! $simular) {
                        $disk->put($destino, $disk->get($origem));
                        $criados[] = $destino;
                        $this->metricas['imagens_arquivos_copiados']++;
                    }

                    $porOrigem[$origem] = $destino;
                    $novo[$chave] = $destino;
                }

                if ($novo !== []) {
                    $projecao[] = $novo;
                }
            }

            if ($projecao === []) {
                throw new RuntimeException('projecao vazia a partir de fonte nao vazia');
            }

            if (! $simular) {
                DB::transaction(fn () => $oferta->forceFill(['images' => $projecao])->save());
            }
        } catch (Throwable $e) {
            // Limpa só o que ESTA tentativa criou. Jamais toca na origem.
            foreach ($criados as $path) {
                $disk->delete($path);
            }

            $this->metricas['imagens_falhas']++;
            $this->erros[] = "oferta #{$oferta->id}: {$e->getMessage()}";

            return;
        }

        if ($anteriores === []) {
            $this->metricas['imagens_ofertas_populadas']++;

            return;
        }

        $this->metricas['imagens_ofertas_substituidas']++;

        // Só agora, com a projeção nova persistida, as cópias antigas podem
        // sair. Qualquer path antigo ainda referenciado pela projeção nova ou
        // pela fonte permanece: prefere-se órfão temporário a perda de arquivo.
        $this->removerCopiasAntigas($anteriores, $projecao, $fonte, $simular);
    }

    /**
     * @param  list<array<string,string>>  $anteriores
     * @param  list<array<string,string>>  $novos
     * @param  list<array<string,string>>  $fonte
     */
    private function removerCopiasAntigas(array $anteriores, array $novos, array $fonte, bool $simular): void
    {
        $preservar = array_merge($this->paths($novos), $this->paths($fonte));
        $disk = Storage::disk('public');

        foreach (array_unique($this->paths($anteriores)) as $path) {
            if (in_array($path, $preservar, true)) {
                continue;
            }

            if (! $simular) {
                $disk->delete($path);
            }

            $this->metricas['imagens_arquivos_removidos']++;
        }
    }

    /**
     * @param  list<array<string,string>>  $entradas
     * @return list<string>
     */
    private function paths(array $entradas): array
    {
        $paths = [];

        foreach ($entradas as $entrada) {
            if (! is_array($entrada)) {
                continue;
            }

            foreach (['thumb', 'medium'] as $chave) {
                if (! empty($entrada[$chave])) {
                    $paths[] = $entrada[$chave];
                }
            }
        }

        return $paths;
    }

    /**
     * Nome novo no padrão do `ImageService` — UUID, sufixo `_thumb`/`_medium`,
     * mesmo diretório da origem —, para que a listagem e a exclusão futuras não
     * precisem distinguir de onde o arquivo veio.
     *
     * A extensão da origem é preservada: o backfill **copia bytes**, não
     * reencoda, e batizar um PNG de `.webp` faria o servidor anunciar um
     * `Content-Type` que o arquivo não tem.
     */
    private function nomeDeDestino(string $origem, string $chave): string
    {
        $dir = trim(str_replace('\\', '/', dirname($origem)), './');
        $ext = pathinfo($origem, PATHINFO_EXTENSION) ?: 'webp';
        $nome = Str::uuid()->toString();

        return ($dir === '' ? '' : "{$dir}/")."{$nome}_{$chave}.{$ext}";
    }

    // -------------------------------------------------------------------- FAQ

    private function processarFaqs(string $modo, bool $simular): void
    {
        $this->metricas['faq_origem'] = DB::table('product_faqs')->count();

        // Toda FAQ cujo produto não tem exatamente uma oferta é FAQ LEGADA NÃO
        // RESOLVIDA: não migra, não é apagada, não vira canônica por omissão.
        // A contagem > 0 é o que bloqueia o cutover da 02E.
        $this->metricas['faq_nao_resolvidas'] = DB::table('product_faqs')
            ->whereNotIn('product_id', $this->produtosComOfertaUnica())
            ->count();

        foreach ($this->ofertasDeterministicas() as $oferta) {
            $origem = DB::table('product_faqs')
                ->where('product_id', $oferta->product_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['question', 'answer']);

            $destinoAtual = DB::table('product_offer_faqs')
                ->where('product_offer_id', $oferta->id)
                ->count();

            if ($modo === self::MODO_INICIAL) {
                // Conservador e aditivo: destino já povoado não é tocado.
                if ($destinoAtual > 0) {
                    $this->metricas['faq_ofertas_preservadas']++;

                    continue;
                }

                if ($origem->isEmpty()) {
                    continue;
                }
            }

            if ($simular) {
                $this->metricas['faq_destino_removidas'] += $destinoAtual;
                $this->metricas['faq_destino_criadas'] += $origem->count();

                continue;
            }

            $this->substituirConjuntoDeFaqs($oferta->id, $origem, $destinoAtual);
        }
    }

    /**
     * Substitui o conjunto inteiro, em vez de calcular a diferença.
     *
     * Três razões, e a terceira decide. É **exata por construção**: criação,
     * edição, remoção, redução, reordenação e limpeza total caem no mesmo
     * caminho, sem caso especial — origem vazia termina com destino vazio
     * porque não há o que inserir. É o **mesmo formato do writer legado**, que
     * já faz `delete()` seguido de `create()`. E um diff posicional colidiria
     * com a `UNIQUE(product_offer_id, sort_order)`: trocar `A[0] B[1]` por
     * `B[0] A[1]` exigiria pôr A em 1 enquanto B ainda o ocupa, e o MySQL
     * valida unicidade por *statement*, não no commit.
     *
     * Os `product_faqs.id` da origem não são identidade: o writer legado apaga
     * e recria a cada salvamento. O que importa é o conjunto ordenado atual.
     *
     * @param  Collection<int, object>  $origem
     */
    private function substituirConjuntoDeFaqs(int $ofertaId, $origem, int $destinoAtual): void
    {
        DB::transaction(function () use ($ofertaId, $origem, $destinoAtual) {
            DB::table('product_offer_faqs')->where('product_offer_id', $ofertaId)->delete();
            $this->metricas['faq_destino_removidas'] += $destinoAtual;

            $agora = now();
            $linhas = [];

            foreach ($origem->values() as $i => $faq) {
                $linhas[] = [
                    'product_offer_id' => $ofertaId,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'sort_order' => $i,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }

            if ($linhas !== []) {
                DB::table('product_offer_faqs')->insert($linhas);
                $this->metricas['faq_destino_criadas'] += count($linhas);
            }
        });
    }

    // -------------------------------------------------------------- perguntas

    /**
     * Preenche `product_offer_id` só onde a associação é determinística.
     *
     * O filtro `WHERE product_offer_id IS NULL` é seguro e suficiente **porque
     * o writer legado nunca escreve a coluna**: toda linha nula é, por
     * construção, linha ainda não reconciliada. Não há drift possível aqui, e a
     * segunda execução é naturalmente no-op.
     */
    private function processarPerguntas(bool $simular): void
    {
        $pendentes = DB::table('product_questions')->whereNull('product_offer_id')->count();

        $resolviveis = DB::table('product_questions')
            ->whereNull('product_offer_id')
            ->whereIn('product_id', $this->produtosComOfertaUnica())
            ->count();

        $this->metricas['perguntas_nao_resolvidas'] = $pendentes - $resolviveis;

        if ($simular || $resolviveis === 0) {
            $this->metricas['perguntas_resolvidas'] = $simular ? $resolviveis : 0;

            return;
        }

        DB::table('product_questions')
            ->whereNull('product_offer_id')
            ->orderBy('id')
            ->chunkById(200, function ($linhas) {
                foreach ($linhas as $linha) {
                    $ofertas = DB::table('product_offers')
                        ->where('product_id', $linha->product_id)
                        ->pluck('id');

                    // 0 ou >1 ofertas: permanece nula. Nunca inferir.
                    if ($ofertas->count() !== 1) {
                        continue;
                    }

                    DB::table('product_questions')
                        ->where('id', $linha->id)
                        ->update(['product_offer_id' => $ofertas->first()]);

                    $this->metricas['perguntas_resolvidas']++;
                }
            });
    }

    /**
     * Passo 6 do cutover (CAT-DOM-02E): tira de `product_faqs` a FAQ comercial
     * que já chegou inteira ao destino.
     *
     * A tabela passou a significar **FAQ canônica**. Conteúdo comercial que
     * sobrevivesse nela viraria afirmação do catálogo por omissão — o mesmo
     * texto existindo simultaneamente como palavra do vendedor e da plataforma,
     * que é exatamente o que a D-CAT-16 separou.
     *
     * ## Por que não é um `DELETE FROM product_faqs`
     *
     * `product_faqs` **não tem autoria**: nada na linha diz se ela foi escrita
     * por um lojista ou pela curadoria. Apagar por tabela destruiria FAQ
     * canônica legítima junto com a comercial.
     *
     * Então a remoção é por **prova de correspondência**: só sai a linha cujo
     * par `(question, answer)` existe na FAQ da oferta determinística daquele
     * produto. O que não tem correspondente **fica**, e é reportado — pode ser
     * canônica, e na dúvida preserva-se.
     *
     * Produto com 0 ou mais de uma oferta não é tocado de forma alguma: sem
     * destino determinístico não há o que provar.
     *
     * @return array{removidas: int, preservadas: int, detalhes: list<string>}
     */
    public function limparFaqComercialLegada(bool $simular = false): array
    {
        $removidas = 0;
        $preservadas = 0;
        $detalhes = [];

        $deterministicos = $this->ofertasDeterministicas()->keyBy('product_id');

        foreach (DB::table('product_faqs')->orderBy('id')->get() as $legada) {
            $oferta = $deterministicos->get($legada->product_id);

            if ($oferta === null) {
                $preservadas++;
                $detalhes[] = "FAQ #{$legada->id}: produto #{$legada->product_id} sem oferta determinística — preservada";

                continue;
            }

            $temPar = DB::table('product_offer_faqs')
                ->where('product_offer_id', $oferta->id)
                ->where('question', $legada->question)
                ->where('answer', $legada->answer)
                ->exists();

            if (! $temPar) {
                $preservadas++;
                $detalhes[] = "FAQ #{$legada->id}: sem correspondente na oferta #{$oferta->id} — preservada (pode ser canônica)";

                continue;
            }

            if (! $simular) {
                DB::table('product_faqs')->where('id', $legada->id)->delete();
            }

            $removidas++;
        }

        return ['removidas' => $removidas, 'preservadas' => $preservadas, 'detalhes' => $detalhes];
    }

    /**
     * Paridade origem × destino da FAQ, exigida antes do cutover da 02E.
     *
     * Comparada **por conteúdo, nunca por chave primária** — os
     * `product_faqs.id` não sobrevivem a um salvamento do writer legado.
     *
     * @return list<string> divergências; vazio significa projeção exata
     */
    public function divergenciasDeFaq(): array
    {
        $divergencias = [];

        foreach ($this->ofertasDeterministicas() as $oferta) {
            $origem = DB::table('product_faqs')
                ->where('product_id', $oferta->product_id)
                ->orderBy('sort_order')->orderBy('id')
                ->get(['question', 'answer'])
                ->values()
                ->map(fn ($f, $i) => $i.'|'.$f->question.'|'.$f->answer)
                ->all();

            $destino = DB::table('product_offer_faqs')
                ->where('product_offer_id', $oferta->id)
                ->orderBy('sort_order')
                ->get(['sort_order', 'question', 'answer'])
                ->map(fn ($f) => $f->sort_order.'|'.$f->question.'|'.$f->answer)
                ->values()
                ->all();

            if ($origem !== $destino) {
                $divergencias[] = sprintf(
                    'oferta #%d: origem tem %d FAQ(s), destino tem %d — conjuntos diferentes',
                    $oferta->id,
                    count($origem),
                    count($destino),
                );
            }
        }

        return $divergencias;
    }

    /**
     * Nenhum arquivo físico compartilhado entre o produto e a oferta (§17).
     *
     * `ImageService::delete()` apaga por caminho, sem contar referências: um
     * path compartilhado faria o lojista apagar a imagem do catálogo ao remover
     * a dele. Silenciosamente, sem erro e sem recuperação.
     *
     * @return list<string>
     */
    public function pathsCompartilhados(): array
    {
        $compartilhados = [];

        foreach (Product::query()->whereNotNull('images')->with('offers')->cursor() as $produto) {
            $doProduto = $this->paths($produto->images ?? []);

            if ($doProduto === []) {
                continue;
            }

            foreach ($produto->offers as $oferta) {
                foreach (array_intersect($this->paths($oferta->images ?? []), $doProduto) as $path) {
                    $compartilhados[] = "produto #{$produto->id} x oferta #{$oferta->id}: {$path}";
                }
            }
        }

        return $compartilhados;
    }
}
