<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Actions\GenerateListingSuggestion;
use App\CatalogIntelligence\DTOs\GuardedPrompt;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingOutcome;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\ListingOutcomeState;
use App\CatalogIntelligence\Enums\ProviderInstruction;
use App\CatalogIntelligence\Enums\ProviderResponseViolation;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Exceptions\CatalogAiProviderException;
use App\CatalogIntelligence\Support\FreeTextRedactor;
use App\CatalogIntelligence\Support\GuardedPromptRedactor;
use App\CatalogIntelligence\Support\PromptGuard;
use App\CatalogIntelligence\Support\ProviderResponseValidator;
use App\Enums\ItemType;
use App\Services\CatalogAi\CatalogAiSettings;
use App\Services\CatalogAi\OpenAiCatalogAiProvider;
use Error;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;
use Throwable;
use TypeError;

/**
 * CAT-10A — o adaptador da Responses API, sem nenhuma chamada real.
 *
 * Toda resposta vem de `Http::fake`, e qualquer requisição que o fake não cubra é
 * recusada por `preventStrayRequests`. O que se prova:
 *
 * - a requisição: endpoint, credencial só no cabeçalho, schema estrito, `store:
 *   false`, sem ferramentas, e os três canais em lugares separados;
 * - o prazo entregue ao cliente HTTP e uma tentativa só;
 * - o que vira `CatalogAiProviderException` — prazo, conexão, HTTP fora de 2xx,
 *   corpo ilegível, resposta não concluída, recusa, falta de texto estruturado e
 *   JSON fora do contrato —, sempre com mensagem fixa;
 * - o que sobe: defeito do transporte não é falha esperada;
 * - o que passa ao validador: resposta que cabe no DTO e diz algo inválido;
 * - ponta a ponta pelo assistente, com o adaptador resolvido pelo container: os
 *   desfechos, a redação na saída, nada gravado e nada sensível em log.
 */
class AdaptadorOpenAiTest extends TestCase
{
    use RefreshDatabase;

    private const CHAVE = 'sk-teste-CHAVE-SECRETA-0123456789';

    private const MODELO = 'modelo-de-teste';

    private const TELEFONE = '(11) 98765-4321';

    private const EMAIL = 'lojista@exemplo.com.br';

    private int $chamadas = 0;

    /** @var array<int, MessageLogged> */
    private array $registros = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config()->set('catalog-intelligence.fallback.minimum_gaps', 3);
    }

    // ─── Cenário ──────────────────────────────────────────────────────────────

    private function adaptador(float $prazo = 8.0): OpenAiCatalogAiProvider
    {
        return new OpenAiCatalogAiProvider(self::CHAVE, self::MODELO, $prazo);
    }

    /** Um item com dado pessoal na descrição — o que a redação precisa conter. */
    private function itemComDadoPessoal(): ListingContext
    {
        return ListingContext::paraItemNovo(
            ItemType::Produto,
            'Tapete de crochê',
            description: 'Peça em algodão cru. Encomendas pelo '.self::TELEFONE.' ou '.self::EMAIL.'.',
        );
    }

    /** O prompt como o assistente o entrega: guard e redator aplicados. */
    private function prompt(?ListingContext $contexto = null): GuardedPrompt
    {
        return app(GuardedPromptRedactor::class)((new PromptGuard)($contexto ?? $this->itemComDadoPessoal()));
    }

    /**
     * @param  array<string, mixed>  $troca
     * @return array<string, mixed>
     */
    private static function sugestaoValida(array $troca = []): array
    {
        return array_replace([
            'suggested_name' => 'Tapete redondo de crochê',
            'short_description' => 'Tapete redondo feito em crochê.',
            'description' => 'Tapete de crochê para a sala.',
            'keywords' => ['tapete', 'crochê'],
        ], $troca);
    }

    /**
     * Um corpo `completed` da Responses API, com um item de raciocínio antes da mensagem.
     *
     * @param  array<string, mixed>|string  $sugestao
     * @return array<string, mixed>
     */
    private static function corpoConcluido(array|string $sugestao): array
    {
        return [
            'id' => 'resp_teste',
            'object' => 'response',
            'status' => 'completed',
            'output' => [
                ['id' => 'rs_teste', 'type' => 'reasoning', 'summary' => []],
                [
                    'id' => 'msg_teste',
                    'type' => 'message',
                    'role' => 'assistant',
                    'status' => 'completed',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => is_string($sugestao) ? $sugestao : json_encode($sugestao, JSON_UNESCAPED_UNICODE),
                        'annotations' => [],
                    ]],
                ],
            ],
        ];
    }

    /** @param  array<string, mixed>|string  $corpo */
    private function responder(array|string $corpo, int $status = 200): void
    {
        Http::fake(function () use ($corpo, $status) {
            $this->chamadas++;

            return Http::response($corpo, $status);
        });
    }

    /** A falha de conexão como o cURL a entrega ao Guzzle: `errno` 28 é prazo esgotado. */
    private function falharAConexao(int $errno): void
    {
        Http::fake(function (Request $request) use ($errno) {
            $this->chamadas++;

            throw new ConnectException("cURL error {$errno}", $request->toPsrRequest(), null, ['errno' => $errno]);
        });
    }

    /**
     * Executa, exige a falha esperada da fronteira e confere nela o que vale para toda
     * falha: nenhuma exceção de transporte junto, e a chave fora da mensagem.
     */
    private function falhaEsperada(callable $acao): CatalogAiProviderException
    {
        try {
            $acao();
        } catch (CatalogAiProviderException $falha) {
            $this->assertNull($falha->getPrevious(), 'a falha do transporte não viaja junto');
            $this->assertStringNotContainsString(self::CHAVE, $falha->getMessage(), 'a chave apareceu na mensagem');

            return $falha;
        }

        $this->fail('a falha esperada da fronteira não foi sinalizada');
    }

    /** @return array<string, mixed> O corpo JSON da única requisição registrada. */
    private function corpoEnviado(): array
    {
        $registradas = Http::recorded();

        $this->assertCount(1, $registradas, 'uma requisição só');

        return $registradas->first()[0]->data();
    }

    /** Liga o provider como o painel liga — gravado no banco (CAT-10A.1) —, com a trava técnica do phpunit.xml desligada de propósito. */
    private function ligarNoContainer(): void
    {
        config()->set('services.catalog_ai.force_disabled', false);

        app(CatalogAiSettings::class)->salvar(
            ativo: true,
            provider: 'openai',
            modelo: self::MODELO,
            timeout: 8,
            novaChave: self::CHAVE,
        );
    }

    /** @return array{0: ListingSuggestion, 1: ListingContext, 2: ListingOutcome} */
    private function gerar(ListingContext $contexto): array
    {
        return app(GenerateListingSuggestion::class)->comContexto($contexto);
    }

    /** Só o nome: sem conhecimento, resumo, descrição, categoria nem atributos — a política consulta fora. */
    private function itemQueFaltaTexto(): ListingContext
    {
        return ListingContext::paraItemNovo(ItemType::Produto, 'Tapete de crochê');
    }

    // ─── Disponibilidade e resposta válida ────────────────────────────────────

    public function test_configurado_esta_disponivel_e_sem_chave_ou_modelo_nao_esta(): void
    {
        $this->assertTrue($this->adaptador()->isAvailable());
        $this->assertFalse((new OpenAiCatalogAiProvider('  ', self::MODELO, 8.0))->isAvailable());
        $this->assertFalse((new OpenAiCatalogAiProvider(self::CHAVE, '', 8.0))->isAvailable());

        Http::assertNothingSent();
    }

    public function test_resposta_valida_vira_sugestao_externa_no_contrato_da_aplicacao(): void
    {
        $this->responder(self::corpoConcluido(self::sugestaoValida()));

        $sugestao = $this->adaptador()->suggest($this->prompt());

        $this->assertInstanceOf(ListingSuggestion::class, $sugestao);
        $this->assertSame('Tapete redondo de crochê', $sugestao->suggestedName);
        $this->assertSame('Tapete redondo feito em crochê.', $sugestao->shortDescription);
        $this->assertSame('Tapete de crochê para a sala.', $sugestao->description);
        $this->assertSame(['tapete', 'crochê'], $sugestao->keywords);
        $this->assertSame([], $sugestao->missingInformation, 'o assistente recalcula o que falta');
        $this->assertSame(SuggestionSource::External, $sugestao->source);
        $this->assertNull($sugestao->confidence, 'não se pede confiança ao modelo');
        $this->assertSame([], app(ProviderResponseValidator::class)->violacoes($sugestao));
        $this->assertSame(1, $this->chamadas);
    }

    // ─── A requisição ─────────────────────────────────────────────────────────

    public function test_a_requisicao_vai_a_responses_api_com_schema_estrito_store_false_e_sem_ferramentas(): void
    {
        $this->responder(self::corpoConcluido(self::sugestaoValida()));

        $this->adaptador()->suggest($this->prompt());

        /** @var Request $requisicao */
        $requisicao = Http::recorded()->first()[0];
        $corpo = $this->corpoEnviado();

        $this->assertSame('https://api.openai.com/v1/responses', $requisicao->url());
        $this->assertSame('POST', $requisicao->method());
        $this->assertTrue($requisicao->hasHeader('Authorization', 'Bearer '.self::CHAVE), 'a credencial vai só no cabeçalho');

        $this->assertSame(
            ['input', 'instructions', 'model', 'store', 'text'],
            collect($corpo)->keys()->sort()->values()->all(),
            'nada além do necessário: sem tools, conversa, previous_response_id, response_format ou messages',
        );

        $this->assertSame(self::MODELO, $corpo['model']);
        $this->assertFalse($corpo['store'], 'store: false é obrigatório');

        $formato = $corpo['text']['format'];
        $this->assertSame('json_schema', $formato['type']);
        $this->assertTrue($formato['strict']);
        $this->assertNotEmpty($formato['name']);
        $this->assertFalse($formato['schema']['additionalProperties']);
        $this->assertSame(
            ['description', 'keywords', 'short_description', 'suggested_name'],
            collect($formato['schema']['required'])->sort()->values()->all(),
        );
        $this->assertSame(
            collect($formato['schema']['required'])->sort()->values()->all(),
            collect($formato['schema']['properties'])->keys()->sort()->values()->all(),
            'strict exige que toda propriedade seja obrigatória',
        );
        $this->assertArrayNotHasKey('confidence', $formato['schema']['properties'], 'não se pede confiança ao modelo');

        $this->assertStringNotContainsString(self::CHAVE, json_encode($corpo), 'a chave não vai no corpo');
    }

    /** S-1 na fronteira do fornecedor: instrução, contexto e dado em lugares distintos, cada um sozinho. */
    public function test_os_tres_canais_seguem_separados_sem_concatenacao(): void
    {
        $this->responder(self::corpoConcluido(self::sugestaoValida()));
        $prompt = $this->prompt();

        $this->adaptador()->suggest($prompt);

        $corpo = $this->corpoEnviado();

        $this->assertIsString($corpo['instructions']);
        $this->assertStringNotContainsString('Tapete de crochê', $corpo['instructions'], 'o dado do item vazou para a instrução');

        $this->assertCount(2, $corpo['input'], 'contexto e dado, um item cada');

        foreach ($corpo['input'] as $entrada) {
            $this->assertSame('user', $entrada['role'], 'nenhum canal não confiável ganha papel de autoridade');
            $this->assertCount(1, $entrada['content']);
            $this->assertSame('input_text', $entrada['content'][0]['type']);
        }

        [$contexto, $dado] = array_map(fn (array $entrada) => $entrada['content'][0]['text'], $corpo['input']);

        $this->assertSame(['contexto_recuperado' => $prompt->context], json_decode($contexto, true));
        $this->assertSame(['dados_do_item' => $prompt->data], json_decode($dado, true));
        $this->assertStringNotContainsString('dados_do_item', $contexto);
        $this->assertStringNotContainsString('Tapete de crochê', $contexto, 'o dado do item vazou para o canal de contexto');
        $this->assertStringNotContainsString('contexto_recuperado', $dado);
    }

    /** H-11: o texto de cada instrução vem de `match` exaustivo, sem `default`, e cita o que o domínio exige. */
    public function test_a_instrucao_vem_de_match_exaustivo_e_cita_os_canais_e_a_redacao(): void
    {
        foreach (ProviderInstruction::cases() as $caso) {
            $this->responder(self::corpoConcluido(self::sugestaoValida()));

            $this->adaptador()->suggest(new GuardedPrompt($caso, context: [], data: ['name' => 'Tapete']));

            $instrucao = Http::recorded()->last()[0]->data()['instructions'];

            $this->assertNotSame('', trim($instrucao), "{$caso->name} ficou sem texto");
            $this->assertStringContainsString('contexto_recuperado', $instrucao);
            $this->assertStringContainsString('dados_do_item', $instrucao);
            $this->assertStringContainsString(FreeTextRedactor::MARCADOR, $instrucao, 'a instrução precisa conhecer o marcador da redação');
        }

        foreach (token_get_all(file_get_contents((new ReflectionClass(OpenAiCatalogAiProvider::class))->getFileName())) as $token) {
            $this->assertFalse(is_array($token) && $token[0] === T_DEFAULT, 'o match da instrução não pode ter default: caso novo sem texto tem de aparecer');
        }
    }

    /**
     * Fato objetivo sobre o item só vem de `dados_do_item`.
     *
     * Conhecimento curado e itens semelhantes ajudam a escrever, mas não provam nada
     * sobre **este** item: um material ou uma certificação que só aparecem no contexto
     * não podem virar afirmação no anúncio. O comportamento do modelo não é testável
     * aqui; o que se prova é o que a aplicação manda — a característica chega só no
     * canal de contexto, e a regra 1 autoriza fato só a partir do canal de dado.
     */
    public function test_caracteristica_so_do_contexto_nao_e_autorizada_como_fato_do_item(): void
    {
        $this->responder(self::corpoConcluido(self::sugestaoValida()));

        $caracteristica = 'certificação orgânica';

        $this->adaptador()->suggest(new GuardedPrompt(
            ProviderInstruction::SuggestListing,
            context: [
                'knowledge' => [['name' => 'Algodão orgânico', 'type' => 'material', 'description' => "Fibra com {$caracteristica}.", 'terms' => []]],
                'similar_items' => [['name' => "Tapete com {$caracteristica}"]],
            ],
            data: [
                'item_type' => 'produto',
                'name' => 'Tapete de crochê',
                'category_path' => [],
                'existing_short_description' => null,
                'existing_description' => null,
                'known_attributes' => [],
            ],
        ));

        $corpo = $this->corpoEnviado();
        [$contexto, $dado] = array_map(fn (array $entrada) => $entrada['content'][0]['text'], $corpo['input']);

        $this->assertStringContainsString($caracteristica, $contexto, 'a característica chega no canal de contexto');
        $this->assertStringNotContainsString($caracteristica, $dado, 'e só nele');
        $this->assertStringNotContainsString($caracteristica, $corpo['instructions']);

        $regra = collect(explode("\n", $corpo['instructions']))->first(fn (string $linha) => str_starts_with($linha, '1. '));

        $this->assertNotNull($regra, 'a regra 1 sumiu da instrução');
        $this->assertStringContainsString('só podem ser afirmados quando estiverem em "dados_do_item"', $regra, 'fato objetivo só a partir do canal de dado');
        $this->assertStringContainsString('"contexto_recuperado"', $regra);
        $this->assertStringContainsString('nunca como prova de uma característica específica do item', $regra);
        $this->assertStringContainsString('Na dúvida, omita.', $regra);

        foreach (['Material', 'medidas', 'origem', 'técnica', 'quantidade', 'prazo', 'garantia', 'certificação'] as $fato) {
            $this->assertStringContainsString($fato, $regra, "a regra 1 deixou de nomear {$fato}");
        }

        $this->assertDoesNotMatchRegularExpression(
            '/(podem|pode) (aparecer|ser afirmad\w*)[^.]*contexto/iu',
            $corpo['instructions'],
            'alguma regra voltou a autorizar o contexto recuperado como fonte de fato do item',
        );
    }

    // ─── Prazo e tentativa única ──────────────────────────────────────────────

    public function test_o_prazo_total_e_o_de_conexao_sao_os_do_adaptador(): void
    {
        $opcoes = [];

        Http::fake(function ($request, array $options) use (&$opcoes) {
            $opcoes = $options;

            return Http::response(self::corpoConcluido(self::sugestaoValida()));
        });

        $this->adaptador(5.0)->suggest($this->prompt());

        $this->assertSame(5.0, $opcoes['timeout']);
        $this->assertSame(5.0, $opcoes['connect_timeout']);
    }

    public function test_prazo_esgotado_vira_tempo_esgotado_com_uma_tentativa(): void
    {
        $this->falharAConexao(28);

        $falha = $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));

        $this->assertSame(CatalogAiProviderException::tempoEsgotado()->getMessage(), $falha->getMessage());
        $this->assertNull($falha->getPrevious(), 'a falha do transporte não viaja junto');
        $this->assertSame(1, $this->chamadas, 'nenhuma nova tentativa');
    }

    public function test_falha_de_conexao_que_nao_e_prazo_vira_falha_do_provider_com_uma_tentativa(): void
    {
        $this->falharAConexao(6);

        $falha = $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));

        $this->assertNotSame(CatalogAiProviderException::tempoEsgotado()->getMessage(), $falha->getMessage());
        $this->assertNull($falha->getPrevious());
        $this->assertSame(1, $this->chamadas);

        Http::fake(function () {
            $this->chamadas++;

            throw new ConnectionException('conexão recusada');
        });

        $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));
        $this->assertSame(2, $this->chamadas);
    }

    // ─── HTTP e corpo ─────────────────────────────────────────────────────────

    /** @return array<string, array{0: int}> */
    public static function statusDeErro(): array
    {
        return ['400' => [400], '401' => [401], '403' => [403], '404' => [404], '429' => [429], '500' => [500], '503' => [503]];
    }

    /** O erro do fornecedor pode ecoar parte da chave: a mensagem é fixa, e só o status entra nela. */
    #[DataProvider('statusDeErro')]
    public function test_http_fora_de_2xx_vira_falha_com_mensagem_fixa_e_uma_tentativa(int $status): void
    {
        $this->responder([
            'error' => [
                'message' => 'Incorrect API key provided: '.substr(self::CHAVE, 0, 12).'***6789. Prompt: '.self::TELEFONE,
                'type' => 'invalid_request_error',
            ],
        ], $status);

        $falha = $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));

        $this->assertSame("o provider recusou a requisição com HTTP {$status}", $falha->getMessage());
        $this->assertNull($falha->getPrevious());
        $this->assertSame(1, $this->chamadas, 'nenhuma nova tentativa, nem para 429 ou 5xx');
    }

    public function test_corpo_ilegivel_vira_falha(): void
    {
        foreach (['<html>Bad gateway</html>', '"só um texto"', ''] as $corpo) {
            $this->responder($corpo);

            $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));
        }
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function respostasNaoConcluidas(): array
    {
        $parcial = self::corpoConcluido('{"suggested_name": "Tap');

        return [
            'incompleta por limite de tokens' => [['status' => 'incomplete', 'incomplete_details' => ['reason' => 'max_output_tokens']] + $parcial],
            'incompleta por filtro de conteúdo' => [['status' => 'incomplete', 'incomplete_details' => ['reason' => 'content_filter']] + $parcial],
            'falhou' => [['status' => 'failed', 'error' => ['code' => 'server_error'], 'output' => []]],
            'em andamento' => [['status' => 'in_progress', 'output' => []]],
            'sem status' => [['output' => self::corpoConcluido(self::sugestaoValida())['output']]],
        ];
    }

    /** @param  array<string, mixed>  $corpo */
    #[DataProvider('respostasNaoConcluidas')]
    public function test_resposta_nao_concluida_vira_falha(array $corpo): void
    {
        $this->responder($corpo);

        $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));
        $this->assertSame(1, $this->chamadas);
    }

    public function test_recusa_do_modelo_vira_falha(): void
    {
        $this->responder([
            'status' => 'completed',
            'output' => [[
                'type' => 'message',
                'content' => [['type' => 'refusal', 'refusal' => 'Não posso ajudar com isso.']],
            ]],
        ]);

        $falha = $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));

        $this->assertStringNotContainsString('Não posso ajudar', $falha->getMessage());
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function saidasSemTextoEstruturado(): array
    {
        $mensagem = fn (array $conteudo) => ['type' => 'message', 'content' => $conteudo];
        $texto = ['type' => 'output_text', 'text' => json_encode(self::sugestaoValida())];

        return [
            'output vazio' => [['status' => 'completed', 'output' => []]],
            'output ausente' => [['status' => 'completed']],
            'só raciocínio' => [['status' => 'completed', 'output' => [['type' => 'reasoning', 'summary' => []]]]],
            'mensagem sem conteúdo' => [['status' => 'completed', 'output' => [$mensagem([])]]],
            'texto que não é texto' => [['status' => 'completed', 'output' => [$mensagem([['type' => 'output_text', 'text' => null]])]]],
            'dois textos' => [['status' => 'completed', 'output' => [$mensagem([$texto, $texto])]]],
        ];
    }

    /** @param  array<string, mixed>  $corpo */
    #[DataProvider('saidasSemTextoEstruturado')]
    public function test_saida_sem_exatamente_um_texto_estruturado_vira_falha(array $corpo): void
    {
        $this->responder($corpo);

        $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));
    }

    // ─── Estrutura × conteúdo ─────────────────────────────────────────────────

    /** @return array<string, array{0: string}> */
    public static function jsonsForaDoContrato(): array
    {
        $json = fn (mixed $valor) => json_encode($valor, JSON_UNESCAPED_UNICODE);
        $sem = fn (string $chave) => $json(array_diff_key(self::sugestaoValida(), [$chave => true]));

        return [
            'não é JSON' => ['isto não é json'],
            'lista em vez de objeto' => [$json(['a', 'b', 'c', 'd'])],
            'objeto vazio' => ['{}'],
            'sem keywords' => [$sem('keywords')],
            'sem descrição' => [$sem('description')],
            'chave a mais' => [$json(self::sugestaoValida(['confidence' => 0.9]))],
            'nome numérico' => [$json(self::sugestaoValida(['suggested_name' => 42]))],
            'descrição como objeto' => [$json(self::sugestaoValida(['description' => ['texto' => 'x']]))],
            'keywords como texto' => [$json(self::sugestaoValida(['keywords' => 'tapete, crochê']))],
            'keywords nulo' => [$json(self::sugestaoValida(['keywords' => null]))],
        ];
    }

    /** Não cabe no DTO: é falha esperada da fronteira, e não resposta inválida. */
    #[DataProvider('jsonsForaDoContrato')]
    public function test_json_que_nao_cabe_no_contrato_vira_falha(string $texto): void
    {
        $this->responder(self::corpoConcluido($texto));

        $falha = $this->falhaEsperada(fn () => $this->adaptador()->suggest($this->prompt()));

        $this->assertStringNotContainsString('tapete', mb_strtolower($falha->getMessage()), 'a mensagem não ecoa a resposta');
    }

    /** Cabe no DTO mas diz algo inválido: segue para o validador, que é quem recusa. */
    public function test_resposta_representavel_mas_invalida_chega_ao_validador(): void
    {
        $this->responder(self::corpoConcluido(self::sugestaoValida([
            'short_description' => '   ',
            'keywords' => ['tapete', '', 3],
        ])));

        $sugestao = $this->adaptador()->suggest($this->prompt());

        $violacoes = app(ProviderResponseValidator::class)->violacoes($sugestao);

        $this->assertContains(ProviderResponseViolation::TextoEmBranco, $violacoes);
        $this->assertContains(ProviderResponseViolation::KeywordsMalformadas, $violacoes);
    }

    // ─── Defeito sobe ─────────────────────────────────────────────────────────

    /** @return array<string, array{0: Throwable}> */
    public static function defeitos(): array
    {
        return [
            'RuntimeException' => [new RuntimeException('defeito do transporte')],
            'TypeError' => [new TypeError('defeito do transporte')],
            'Error' => [new Error('defeito do transporte')],
        ];
    }

    #[DataProvider('defeitos')]
    public function test_defeito_do_transporte_nao_vira_falha_esperada(Throwable $defeito): void
    {
        Http::fake(fn () => throw $defeito);

        try {
            $this->adaptador()->suggest($this->prompt());
            $this->fail('o defeito deveria ter subido');
        } catch (Throwable $capturado) {
            $this->assertNotInstanceOf(CatalogAiProviderException::class, $capturado, 'defeito mascarado como falha do provider');
            $this->assertSame($defeito::class, $capturado::class);
        }
    }

    // ─── Ponta a ponta pelo assistente ────────────────────────────────────────

    public function test_ponta_a_ponta_a_resposta_externa_e_aproveitada(): void
    {
        $this->ligarNoContainer();
        $this->responder(self::corpoConcluido(self::sugestaoValida()));

        [$sugestao, , $desfecho] = $this->gerar($this->itemQueFaltaTexto());

        $this->assertSame(ListingOutcomeState::ExternalSuggestionUsed, $desfecho->state);
        $this->assertSame(SuggestionSource::External, $sugestao->source);
        $this->assertSame('Tapete redondo de crochê', $sugestao->suggestedName);
        $this->assertSame('Tapete redondo feito em crochê.', $sugestao->shortDescription);
        $this->assertNull($sugestao->confidence);
        $this->assertSame(1, $this->chamadas);
    }

    /** @return array<string, array{0: string}> */
    public static function falhasPontaAPonta(): array
    {
        return ['http 500' => ['http'], 'prazo esgotado' => ['prazo'], 'json fora do contrato' => ['json'], 'recusa' => ['recusa']];
    }

    #[DataProvider('falhasPontaAPonta')]
    public function test_ponta_a_ponta_falha_esperada_preserva_a_sugestao_interna(string $falha): void
    {
        $this->ligarNoContainer();

        match ($falha) {
            'http' => $this->responder(['error' => ['message' => 'indisponível']], 500),
            'prazo' => $this->falharAConexao(28),
            'json' => $this->responder(self::corpoConcluido('{"suggested_name": 1}')),
            'recusa' => $this->responder(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal', 'refusal' => 'não']]]]]),
        };

        [$sugestao, , $desfecho] = $this->gerar($this->itemQueFaltaTexto());

        $this->assertSame(ListingOutcomeState::ProviderFailed, $desfecho->state);
        $this->assertSame(SuggestionSource::Internal, $sugestao->source, 'a sugestão interna é preservada');
        $this->assertFalse($sugestao->temAlgoAPropor());
        $this->assertSame(1, $this->chamadas, 'uma tentativa, também pelo assistente');
    }

    public function test_ponta_a_ponta_resposta_invalida_termina_em_provider_response_invalid(): void
    {
        $this->ligarNoContainer();
        $this->responder(self::corpoConcluido(self::sugestaoValida(['short_description' => '   '])));

        [$sugestao, , $desfecho] = $this->gerar($this->itemQueFaltaTexto());

        $this->assertSame(ListingOutcomeState::ProviderResponseInvalid, $desfecho->state);
        $this->assertContains(ProviderResponseViolation::TextoEmBranco, $desfecho->violations);
        $this->assertSame(SuggestionSource::Internal, $sugestao->source);
    }

    /** C-2 na fronteira do fornecedor: o texto livre sai redigido, pelo redator que já existia. */
    public function test_ponta_a_ponta_o_texto_livre_sai_redigido(): void
    {
        $this->ligarNoContainer();
        $this->responder(self::corpoConcluido(self::sugestaoValida(['description' => null])));

        $this->gerar($this->itemComDadoPessoal());

        $enviado = json_encode($this->corpoEnviado(), JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString(FreeTextRedactor::MARCADOR, $enviado);
        $this->assertStringNotContainsString('98765-4321', $enviado, 'telefone saiu da aplicação');
        $this->assertStringNotContainsString(self::EMAIL, $enviado, 'e-mail saiu da aplicação');
    }

    /** O fallback: desligado, o assistente segue com o Null e nada sai da aplicação. */
    public function test_ponta_a_ponta_desligado_nao_envia_nada(): void
    {
        [$sugestao, , $desfecho] = $this->gerar($this->itemQueFaltaTexto());

        $this->assertSame(ListingOutcomeState::ProviderUnavailable, $desfecho->state);
        $this->assertSame(SuggestionSource::Internal, $sugestao->source);

        Http::assertNothingSent();
    }

    public function test_ponta_a_ponta_nada_e_gravado_no_banco(): void
    {
        $this->ligarNoContainer();
        $this->responder(self::corpoConcluido(self::sugestaoValida()));

        $escritas = [];

        DB::listen(function (QueryExecuted $consulta) use (&$escritas) {
            if (preg_match('/^\s*(insert|update|delete|replace|truncate|create|alter|drop)\b/i', $consulta->sql) === 1) {
                $escritas[] = $consulta->sql;
            }
        });

        $this->gerar($this->itemComDadoPessoal());

        $this->assertSame([], $escritas, 'prompt e resposta não são persistidos');
    }

    /** A chave, o dado pessoal, o texto do item e o corpo do fornecedor não chegam ao log — nem na falha. */
    public function test_ponta_a_ponta_segredo_e_conteudo_sensivel_nao_vao_para_o_log(): void
    {
        $this->ligarNoContainer();

        Event::listen(MessageLogged::class, function (MessageLogged $registro) {
            $this->registros[] = $registro;
        });

        $cenarios = [
            fn () => $this->responder(['error' => ['message' => 'Incorrect API key provided: '.self::CHAVE]], 401),
            fn () => $this->falharAConexao(28),
            fn () => $this->responder(self::corpoConcluido('resposta com '.self::TELEFONE.' que não é json')),
            fn () => $this->responder(self::corpoConcluido(self::sugestaoValida())),
        ];

        foreach ($cenarios as $preparar) {
            $preparar();
            $this->gerar($this->itemComDadoPessoal());
        }

        $this->assertNotEmpty($this->registros, 'as falhas deveriam ter sido registradas — sem isso a asserção abaixo não prova nada');

        $log = json_encode(array_map(fn (MessageLogged $r) => [$r->level, $r->message, $r->context], $this->registros), JSON_UNESCAPED_UNICODE);

        foreach ([self::CHAVE, 'sk-teste', 'Incorrect API key', '98765-4321', self::EMAIL, 'Peça em algodão cru', 'Tapete de crochê'] as $sensivel) {
            $this->assertStringNotContainsString($sensivel, $log, "\"{$sensivel}\" chegou ao log");
        }
    }
}
