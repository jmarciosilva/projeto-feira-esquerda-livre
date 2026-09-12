<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\Support\FreeTextRedactor;
use App\Enums\ItemType;
use Tests\TestCase;

/**
 * CAT-06E — o redator de texto livre, e o gate C-2.
 *
 * ## As três faixas da D-CAT-06B-2, cada uma com os dois lados
 *
 * | Faixa | Positivo | Negativo |
 * |---|---|---|
 * | Sempre redigir — telefone, e-mail, CPF/CNPJ, CEP | o dado some | o número comercial parecido sobrevive |
 * | Nunca redigir — medidas, preço, quantidade | — | sobrevive intacto, inclusive ao lado de dado redigido |
 * | Não redigir por padrão — URL, `@handle` | — | sobrevive intacto |
 *
 * *"Uma categoria de não-redação sem teste negativo não é decisão, é acidente
 * esperando um regex"* (CAT-06B §3.3). Por isso os casos de não-redação não são
 * apêndice dos de redação: têm teste próprio, e o que eles guardam é
 * justamente o que um padrão ganancioso quebraria.
 *
 * ## Sem banco, sem rede, sem dublê
 *
 * O redator recebe `string` e devolve `string`. Nenhum caso usa
 * `RefreshDatabase`; o único que monta `ListingContext` é o que prova que o
 * caminho interno continua sem redação.
 *
 * ## Os números de documento são válidos de propósito
 *
 * `529.982.247-25`, `11.222.333/0001-81` e `12.ABC.345/01DE-35` têm dígito
 * verificador correto — é o dígito que separa documento sem máscara de código
 * de produto. Os controles negativos usam números com a mesma quantidade de
 * dígitos e verificador **inválido**.
 */
class FreeTextRedactorTest extends TestCase
{
    private const M = FreeTextRedactor::MARCADOR;

    private function redator(): FreeTextRedactor
    {
        return new FreeTextRedactor;
    }

    /**
     * Cada trecho sai inteiro como o marcador, e o texto em volta fica.
     *
     * @param  array<int, string>  $trechos
     */
    private function assertRedigeCadaUm(array $trechos, string $categoria): void
    {
        foreach ($trechos as $trecho) {
            $this->assertSame(
                'Contato: '.self::M.'.',
                $this->redator()->redigir("Contato: {$trecho}."),
                "{$categoria} \"{$trecho}\" atravessou a fronteira sem redação (D-CAT-06B-2: sempre redigir).",
            );
        }
    }

    /**
     * Cada texto sai byte a byte igual.
     *
     * @param  array<int, string>  $textos
     */
    private function assertPreservaCadaUm(array $textos, string $motivo): void
    {
        foreach ($textos as $texto) {
            $this->assertSame(
                $texto,
                $this->redator()->redigir($texto),
                "\"{$texto}\" foi alterado — {$motivo}",
            );
        }
    }

    // ─── Texto sem dado sensível ──────────────────────────────────────────────

    public function test_texto_comercial_sem_dado_sensivel_sai_identico(): void
    {
        $texto = "Tapete de crochê feito à mão com barbante de algodão.\n"
            ."Medidas: 60 x 90 cm · peso 1,2 kg · R$ 189,90 (10% no Pix).\n"
            .'Coleção 2024, tamanho único. Kit com 2 peças — produção artesanal em São Paulo.';

        $this->assertSame($texto, $this->redator()->redigir($texto));
    }

    public function test_texto_vazio_e_so_espacos_saem_identicos(): void
    {
        $this->assertSame('', $this->redator()->redigir(''));
        $this->assertSame("   \n\t ", $this->redator()->redigir("   \n\t "), 'o redator não apara nem normaliza espaço');
    }

    // ─── Sempre redigir ───────────────────────────────────────────────────────

    public function test_email_e_redigido(): void
    {
        $this->assertRedigeCadaUm([
            'vendedor@example.com',
            'maria.silva+loja@ceramica.com.br',
            'joão_artesã@feira-livre.org',
            'CONTATO@LOJA.COM',
        ], 'e-mail');
    }

    public function test_o_ponto_final_da_frase_nao_e_engolido_pelo_email(): void
    {
        $this->assertSame(
            'Escreva para '.self::M.'. Respondo em 24 horas.',
            $this->redator()->redigir('Escreva para vendedor@example.com. Respondo em 24 horas.'),
        );
    }

    public function test_telefone_com_ddd_e_redigido_em_qualquer_mascara(): void
    {
        $this->assertRedigeCadaUm([
            '(11) 98765-4321',
            '(11)98765-4321',
            '( 11 ) 98765-4321',
            '11 98765-4321',
            '11-98765-4321',
            '11.98765.4321',
            '(21) 9 8765-4321',
            '11987654321',
            '(11) 3456-7890',
            '11 3456-7890',
            '(55) 2345-6789',
        ], 'telefone com DDD');
    }

    public function test_telefone_com_55_e_redigido(): void
    {
        $this->assertRedigeCadaUm([
            '+55 11 98765-4321',
            '+55 (11) 98765-4321',
            '+5511987654321',
            '+55 11 3456-7890',
            '55 11 98765-4321',
            '5511987654321',
        ], 'telefone com +55');
    }

    public function test_celular_sem_ddd_separado_em_blocos_e_redigido(): void
    {
        $this->assertRedigeCadaUm(['98765-4321', '9 8765-4321', '9 8765 4321'], 'celular sem DDD');
    }

    public function test_cpf_e_redigido_com_e_sem_pontuacao(): void
    {
        $this->assertRedigeCadaUm([
            '529.982.247-25',
            '529982247-25',
            '52998224725',
            // Mascarado, a forma basta: CPF digitado com verificador errado
            // continua sendo CPF de alguém.
            '123.456.789-00',
        ], 'CPF');
    }

    public function test_cnpj_e_redigido_com_e_sem_pontuacao(): void
    {
        $this->assertRedigeCadaUm([
            '11.222.333/0001-81',
            '11222333/0001-81',
            '11222333000181',
        ], 'CNPJ');
    }

    public function test_cnpj_alfanumerico_e_redigido(): void
    {
        $this->assertRedigeCadaUm([
            '12.ABC.345/01DE-35',
            '12.abc.345/01de-35',
            '12ABC34501DE35',
        ], 'CNPJ alfanumérico');
    }

    public function test_cep_e_redigido(): void
    {
        $this->assertRedigeCadaUm(['01310-100', '01.310-100'], 'CEP');

        // Sem máscara, só com o rótulo — e o rótulo fica.
        $this->assertSame('Retirada no CEP '.self::M, $this->redator()->redigir('Retirada no CEP 01310100'));
        $this->assertSame('cep: '.self::M, $this->redator()->redigir('cep: 01310100'));
    }

    // ─── Sempre redigir: os controles negativos ───────────────────────────────

    /**
     * Números com a quantidade de dígitos de um documento e **sem** o sinal que
     * o identifica — verificador inválido, forma parcial com letra.
     */
    public function test_codigo_com_digitos_de_documento_e_verificador_invalido_sobrevive(): void
    {
        $this->assertPreservaCadaUm([
            'Código interno 12345678901',
            'GTIN-14 17891234567892',
            'Referência 00000000000',
            'Referência 00000000000000',
            'CAMISETA/AZUL-38',
        ], 'sem máscara, só dígito verificador válido identifica CPF ou CNPJ.');
    }

    public function test_numero_com_forma_parecida_com_telefone_sobrevive(): void
    {
        $this->assertPreservaCadaUm([
            'Coleção 2019-2023',
            'Lote 987654321',
            'EAN 7891234567895',
            'ISBN 978-85-359-0277-8',
            'NCM 6109.10.00',
            'SKU TAP-2024-0001',
            'Resolução 1920x1080',
            'Tamanhos 34 36 38 40 42',
            'Pack 5000 9999',
            'SAC 0800 123 4567',
        ], 'não tem DDD, nem 9 de celular, nem separador que o identifique como telefone.');
    }

    /**
     * Limitação deliberada, e não esquecimento: dez dígitos soltos têm a forma
     * de um fixo com DDD e a de um ISBN-10 ou código interno. Sem máscara não há
     * como distinguir, e a D-CAT-06B-2 pesa o número comercial igual ao dado
     * pessoal.
     *
     * Se a decisão mudar, este é o caso que quebra — e deve quebrar.
     */
    public function test_dez_digitos_sem_mascara_nao_sao_tratados_como_telefone_fixo(): void
    {
        $this->assertPreservaCadaUm(
            ['ISBN 8535902775', 'Código 1134567890', 'Ramal 3456-7890'],
            'fixo sem máscara e fixo sem DDD são limitação declarada no FreeTextRedactor.',
        );
    }

    public function test_oito_digitos_sem_rotulo_nao_sao_cep(): void
    {
        $this->assertPreservaCadaUm(
            ['NCM 61091000', 'EAN-8 96385074', 'Lançamento 20260912'],
            'oito dígitos soltos só são CEP logo depois da palavra CEP.',
        );
    }

    // ─── Nunca redigir: medidas, preço, quantidade ────────────────────────────

    public function test_medidas_nunca_sao_redigidas(): void
    {
        $this->assertPreservaCadaUm([
            '30 x 40 cm', '120x60x75 cm', '1,5 m', '500 g', '2 kg', '350 ml',
            'aro 29', 'tamanho 38', 'numeração 34 a 44', 'tela de 6,7 polegadas',
            '5000 mAh', '127/220 V', '128 GB',
        ], 'medida é conteúdo do catálogo (D-CAT-06B-2: nunca redigir).');
    }

    public function test_preco_e_percentual_nunca_sao_redigidos(): void
    {
        $this->assertPreservaCadaUm([
            'R$ 49,90', 'R$ 1.299,90', 'R$ 12.345.678,00', 'de R$ 250 por R$ 199',
            '15% de desconto', '3x sem juros',
        ], 'preço é conteúdo do catálogo (D-CAT-06B-2: nunca redigir).');
    }

    public function test_quantidade_ano_e_data_nunca_sao_redigidos(): void
    {
        $this->assertPreservaCadaUm([
            'kit com 12 unidades', '1.000 unidades', 'caixa com 24', 'pacote de 100',
            'modelo 2024', 'safra 2025/2026', 'garantia de 12 meses', 'feira em 12/09/2026',
        ], 'quantidade é conteúdo do catálogo (D-CAT-06B-2: nunca redigir).');
    }

    // ─── Não redigir por padrão: URL e @handle ───────────────────────────────

    public function test_url_e_handle_nao_sao_redigidos_por_padrao(): void
    {
        $this->assertPreservaCadaUm([
            'https://feiraesquerdalivre.com.br/loja/ceramica-da-maria',
            'www.lojadamaria.com.br',
            'instagram.com/ceramicadamaria',
            'Siga @ceramica.da.maria',
            'https://medium.com/@loja',
        ], 'URL e @handle são divulgação intencional (D-CAT-06B-2: não redigir por padrão).');
    }

    /**
     * A regra da categoria prevalece sobre a da URL: o link fica, o dado
     * pessoal dentro dele não.
     */
    public function test_dado_pessoal_dentro_de_url_continua_redigido(): void
    {
        $this->assertSame('https://wa.me/'.self::M, $this->redator()->redigir('https://wa.me/5511987654321'));
        $this->assertSame('mailto:'.self::M, $this->redator()->redigir('mailto:maria@loja.com.br'));
    }

    // ─── Texto real: vários dados, várias linhas ──────────────────────────────

    public function test_o_exemplo_da_fase_preserva_o_comercial_e_redige_o_contato(): void
    {
        $this->assertSame(
            "Tênis para corrida.\nContato do fornecedor: ".self::M.".\nCabedal respirável e solado de borracha.",
            $this->redator()->redigir(
                "Tênis para corrida.\nContato do fornecedor: vendedor@example.com.\nCabedal respirável e solado de borracha."
            ),
        );
    }

    public function test_varios_dados_no_mesmo_texto_multilinha(): void
    {
        $original = "Bolsa de palha de buriti — 35 x 25 cm, R$ 149,90.\r\n"
            ."Chama no zap (11) 98765-4321 ou escreva para ana.artesa@gmail.com 🧶\n"
            ."Emitimos nota: CNPJ 11.222.333/0001-81. Retirada no CEP 01310-100.\n"
            .'Kit com 3 unidades, coleção 2019-2023. Loja: @palhadeburiti';

        $esperado = "Bolsa de palha de buriti — 35 x 25 cm, R$ 149,90.\r\n"
            .'Chama no zap '.self::M.' ou escreva para '.self::M." 🧶\n"
            .'Emitimos nota: CNPJ '.self::M.'. Retirada no CEP '.self::M.".\n"
            .'Kit com 3 unidades, coleção 2019-2023. Loja: @palhadeburiti';

        $this->assertSame($esperado, $this->redator()->redigir($original));
    }

    /**
     * O separador de telefone é só horizontal. Se a quebra de linha contasse, a
     * quantidade no fim da linha de cima viraria DDD e sumiria junto.
     */
    public function test_quebra_de_linha_nunca_e_engolida_por_um_telefone(): void
    {
        $this->assertSame(
            "Quantidade: 12\n".self::M,
            $this->redator()->redigir("Quantidade: 12\n98765-4321"),
        );
    }

    // ─── Idempotência e texto já redigido ─────────────────────────────────────

    public function test_texto_ja_redigido_sai_identico(): void
    {
        $this->assertPreservaCadaUm(
            [self::M, 'Contato: '.self::M.'.', 'Telefone [REDACTED], CPF ***'],
            'marcador não tem dígito nem arroba, e nenhuma regra o reconhece.',
        );
    }

    public function test_redigir_duas_vezes_da_o_mesmo_que_redigir_uma(): void
    {
        $corpus = [
            'Chama no zap (11) 98765-4321 ou escreva para ana.artesa@gmail.com',
            "CPF 529.982.247-25\nCNPJ 11222333000181\nCEP 01310100",
            '+5511987654321 · https://wa.me/5511987654321 · 12.ABC.345/01DE-35',
            'vendedor@example.com11987654321',
            'R$ 49,90 · 30 x 40 cm · kit com 12 · coleção 2019-2023 · @loja',
            "abc \xC3\x28 def",
            '',
        ];

        foreach ($corpus as $texto) {
            $uma = $this->redator()->redigir($texto);

            $this->assertSame($uma, $this->redator()->redigir($uma), 'a segunda passada mudou o texto');
        }
    }

    // ─── Falha fechado ────────────────────────────────────────────────────────

    /**
     * Texto que o redator não consegue ler não é texto que ele possa declarar
     * seguro. Sai inteiro como marcador — sem exceção, que carregaria o texto
     * na mensagem.
     */
    public function test_utf8_invalido_falha_fechado(): void
    {
        $this->assertSame(self::M, $this->redator()->redigir("Contato \xC3\x28 (11) 98765-4321"));
    }

    public function test_texto_longo_nao_faz_o_redator_desistir(): void
    {
        $trecho = 'Tapete de crochê feito à mão, 60 x 90 cm, R$ 189,90. ';

        $this->assertSame(
            str_repeat($trecho, 2000).self::M,
            $this->redator()->redigir(str_repeat($trecho, 2000).'maria@loja.com.br'),
            'um texto de ~110 KB saiu diferente — se virou só o marcador, o motor de regex desistiu e o redator falhou fechado',
        );
    }

    // ─── Fronteiras da peça ───────────────────────────────────────────────────

    /**
     * D-CAT-06B-2: a redação é **só** da fronteira de saída. O caminho interno
     * continua recebendo o texto como o lojista o escreveu.
     */
    public function test_o_context_sanitizer_continua_sem_redigir_o_caminho_interno(): void
    {
        $contexto = ListingContext::paraItemNovo(
            ItemType::Produto,
            'Tapete de crochê',
            description: 'Chama no zap (11) 98765-4321.',
        );

        $this->assertSame('Chama no zap (11) 98765-4321.', $contexto->existingDescription);
    }

    /**
     * Mesmo instrumento de `test_a_suggestion_policy_existe_e_nao_conhece_provider`:
     * a asserção é sobre importações e dependências, não sobre a prosa, que fala
     * de provider para explicar que não conhece nenhum.
     */
    public function test_o_redator_nao_depende_de_nada_nem_registra_nada(): void
    {
        $fonte = file_get_contents(app_path('CatalogIntelligence/Support/FreeTextRedactor.php'));

        preg_match_all('/^use\s+([^;]+);/m', $fonte, $importacoes);

        $this->assertSame([], $importacoes[1], 'o redator recebe string e devolve string — não importa nada');
        $this->assertNull(
            (new \ReflectionClass(FreeTextRedactor::class))->getConstructor(),
            'o redator ganhou dependência de construtor',
        );

        foreach (['Log::', 'logger(', 'error_log', 'config(', 'env(', 'Cache::', 'DB::', 'throw '] as $marca) {
            $this->assertStringNotContainsString(
                $marca,
                $fonte,
                "o redator contém \"{$marca}\": ele não lê config, não guarda, não registra e não lança — ".
                'log ou exceção carregariam o texto que ele existe para proteger.',
            );
        }
    }
}
