<?php

namespace App\CatalogIntelligence\Support;

/**
 * O conteúdo que não pode atravessar a fronteira de saída — gate **C-2**.
 *
 * CAT-06E. Implementa a D-CAT-06B-2, sem reabri-la: telefone, e-mail, CPF/CNPJ
 * e CEP são **sempre** redigidos; medidas, preço e quantidade **nunca**; URL e
 * `@handle` **não** por padrão.
 *
 * ## Onde isto roda, e onde não roda
 *
 * Só na fronteira de saída para o provider. **Nunca** dentro do
 * `ContextSanitizer`: aquele filtra *campos* e serve também o assistente
 * interno, cujo texto não sai da aplicação — redigir ali degradaria a sugestão
 * interna sem proteger fronteira nenhuma.
 *
 * Nesta subfase a classe existe, tem teste, e **ninguém a chama**. Não há saída
 * ligada para chamá-la: ligar a saída é a CAT-06G, e é lá que o texto livre
 * passa por aqui antes de ir embora. Chamar agora seria antecipar o acoplamento
 * que a D-CAT-06B-6 pôs depois do redator e do guard.
 *
 * ## Recebe texto, devolve texto
 *
 * Uma `string`, e só. Não conhece `ListingContext`, provider, config, log nem
 * banco, e não guarda o original nem o trecho removido. Mesma entrada, mesma
 * saída.
 *
 * Tudo o que não casa com uma regra sai **byte a byte igual** — espaço, quebra
 * de linha, acento, pontuação. O redator troca o trecho sensível e mais nada:
 * normalizar o resto seria reescrever o texto do lojista.
 *
 * Aplicar duas vezes dá o mesmo que aplicar uma. O marcador não tem dígito nem
 * arroba, e toda regra numérica exige que o trecho não esteja colado a letra ou
 * dígito — como o marcador começa e termina em colchete, trocar um trecho não
 * cria vizinhança nova que uma segunda passada reconheceria.
 *
 * ## Conservador do lado dos números comerciais
 *
 * "Nunca redigir preço e quantidade" tem o mesmo peso da redação, e o que a
 * quebra é uma regra de telefone gananciosa. Por isso nenhuma regra trata uma
 * sequência de dígitos como dado pessoal sem um sinal a mais:
 *
 * | Categoria | Redige quando |
 * |---|---|
 * | E-mail | há `local@domínio.tld` |
 * | CNPJ | numérico com qualquer máscara; alfanumérico com a máscara inteira; sem máscara, só com dígito verificador válido |
 * | CPF | com qualquer máscara; sem máscara, só com dígito verificador válido |
 * | Telefone | celular com DDD, com ou sem máscara; fixo com DDD, só com máscara ou `+55`; celular sem DDD, só separado em blocos |
 * | CEP | com máscara (`01310-100`); oito dígitos soltos, só logo depois da palavra CEP |
 *
 * O dígito verificador é o sinal que separa CPF e CNPJ sem máscara de código de
 * produto: um GTIN-14 tem os mesmos catorze dígitos de um CNPJ.
 *
 * ## Limitações declaradas
 *
 * - **Fixo sem máscara** (`1134567890`) e **fixo sem DDD** (`3456-7890`) não são
 *   redigidos: o primeiro tem a forma de qualquer código de dez dígitos, o
 *   segundo a de uma faixa de anos (`2019-2023`).
 * - **Código com a forma exata de CPF, CNPJ ou CEP mascarado** é redigido — a
 *   forma é o sinal, e não há outro.
 * - **Dado pessoal dentro de URL** é redigido e a URL fica: `wa.me/5511…` perde
 *   o número. A regra da categoria prevalece sobre a da URL.
 * - **Credencial, token e segredo** não estão na D-CAT-06B-2 e não são
 *   procurados aqui.
 * - **Dado escrito por extenso ou disfarçado** ("maria arroba gmail") passa.
 *
 * ## Falha fechado, e em silêncio
 *
 * Texto que não é UTF-8 válido, ou em que o motor de expressão regular desiste,
 * sai **inteiro** como o marcador. Um redator que não conseguiu ler o texto não
 * pode afirmar que ele é seguro, e devolver o original seria afirmar isso.
 *
 * Nenhuma exceção é lançada e nada é registrado: a mensagem de uma exceção ou
 * de um log carregaria justamente o texto que não se sabe se é seguro.
 */
class FreeTextRedactor
{
    /**
     * O que fica no lugar do trecho redigido.
     *
     * Um só, para todas as categorias: dizer *qual* dado saiu não ajuda a
     * escrever descrição de item. A palavra é a mesma do `PropertySanitizer` do
     * Customer Intelligence, sem depender dele — os dois módulos não se
     * conhecem.
     */
    public const MARCADOR = '[redigido]';

    /** `local@domínio.tld`. O TLD exige letras, então o ponto final da frase fica fora. */
    private const EMAIL = '/[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}\-]+(?:\.[\p{L}\p{N}\-]+)*\.\p{L}{2,}/u';

    /** Catorze caracteres na forma 2-3-3-4-2, máscara opcional. Quem decide é `ehCnpj()`. */
    private const CNPJ = '/(?<![\p{L}\p{N}])[A-Z0-9]{2}\.?[A-Z0-9]{3}\.?[A-Z0-9]{3}\/?[A-Z0-9]{4}-?[0-9]{2}(?![\p{L}\p{N}])/iu';

    /** Onze dígitos na forma 3-3-3-2, máscara opcional. Quem decide é `ehCpf()`. */
    private const CPF = '/(?<![\p{L}\p{N}])[0-9]{3}\.?[0-9]{3}\.?[0-9]{3}-?[0-9]{2}(?![\p{L}\p{N}])/u';

    /**
     * Telefone brasileiro: `+55` e DDD opcionais, assinante de celular (9 e mais
     * oito dígitos) ou de fixo (2 a 5 e mais sete). Quem decide é `ehTelefone()`.
     *
     * O `+55` só vale junto do DDD, e o separador é só horizontal: uma quebra de
     * linha nunca é engolida, senão a quantidade da linha de cima viraria DDD.
     */
    private const TELEFONE = '/
        (?<![\p{L}\p{N}+])
        (?:
            (?<ddi>\+\h?55[\h.\-]?|55[\h.\-]?)?
            (?<ddd>\(\h?[1-9]{2}\h?\)|[1-9]{2})
            [\h.\-]?
        )?
        (?<assinante>9[\h.]?[0-9]{4}|[2-5][0-9]{3})
        (?<separador>[\h.\-]?)
        [0-9]{4}
        (?![\p{L}\p{N}])
    /xu';

    /** `01310-100` ou `01.310-100`. */
    private const CEP_MASCARADO = '/(?<![\p{L}\p{N}])[0-9]{2}\.?[0-9]{3}-[0-9]{3}(?![\p{L}\p{N}])/u';

    /** Oito dígitos soltos logo depois de "CEP". Sem o rótulo, são NCM, EAN-8 ou data. */
    private const CEP_ROTULADO = '/\bCEP\h*[:.\-]?\h*\K[0-9]{8}(?![\p{L}\p{N}])/iu';

    /** A versão do texto que pode atravessar a fronteira de saída. */
    public function redigir(string $texto): string
    {
        if ($texto === '') {
            return $texto;
        }

        if (! mb_check_encoding($texto, 'UTF-8')) {
            return self::MARCADOR;
        }

        // A ordem importa: o e-mail sai antes de seus dígitos parecerem
        // telefone, e CNPJ e CPF saem antes de um pedaço deles parecer
        // telefone ou CEP.
        $etapas = [
            [self::EMAIL, fn (array $m): bool => true],
            [self::CNPJ, fn (array $m): bool => $this->ehCnpj($m[0])],
            [self::CPF, fn (array $m): bool => $this->ehCpf($m[0])],
            [self::TELEFONE, fn (array $m): bool => $this->ehTelefone($m)],
            [self::CEP_MASCARADO, fn (array $m): bool => true],
            [self::CEP_ROTULADO, fn (array $m): bool => true],
        ];

        foreach ($etapas as [$padrao, $redige]) {
            $texto = preg_replace_callback(
                $padrao,
                fn (array $m): string => $redige($m) ? self::MARCADOR : $m[0],
                $texto,
                flags: PREG_UNMATCHED_AS_NULL,
            );

            if ($texto === null) {
                return self::MARCADOR;
            }
        }

        return $texto;
    }

    /**
     * CNPJ numérico conta com qualquer máscara. O alfanumérico, que a Receita
     * Federal passou a emitir, só com a máscara inteira ou sem máscara nenhuma:
     * `CAMISETA/AZUL-38` tem a forma parcial e é tamanho de camiseta.
     */
    private function ehCnpj(string $trecho): bool
    {
        $caracteres = str_replace(['.', '/', '-'], '', $trecho);
        $mascarado = $caracteres !== $trecho;

        if (ctype_digit($caracteres)) {
            return $mascarado || $this->cnpjValido($caracteres);
        }

        if (preg_match('/^[A-Z0-9]{2}\.[A-Z0-9]{3}\.[A-Z0-9]{3}\/[A-Z0-9]{4}-[0-9]{2}$/i', $trecho) === 1) {
            return true;
        }

        return ! $mascarado && $this->cnpjValido($caracteres);
    }

    private function ehCpf(string $trecho): bool
    {
        $digitos = str_replace(['.', '-'], '', $trecho);

        return $digitos !== $trecho || $this->cpfValido($digitos);
    }

    /** @param  array<int|string, string|null>  $m */
    private function ehTelefone(array $m): bool
    {
        $celular = str_starts_with((string) $m['assinante'], '9');

        if ($m['ddd'] === null) {
            // Sem DDD, só o celular separado em blocos: `2019-2023` tem a forma
            // de fixo, e `987654321` a de qualquer código.
            return $celular && $m['separador'] !== '';
        }

        // Com DDD, o celular se reconhece até sem máscara — onze dígitos com o 9
        // na terceira posição. O fixo sem máscara é qualquer código de dez.
        return $celular || preg_match('/[^0-9]/', (string) $m[0]) === 1;
    }

    /** Dígitos verificadores do CPF. Onze dígitos iguais passam na conta e não são CPF. */
    private function cpfValido(string $cpf): bool
    {
        if (strlen(count_chars($cpf, 3)) === 1) {
            return false;
        }

        foreach ([9, 10] as $posicao) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * ($posicao + 1 - $i);
            }

            if ((int) $cpf[$posicao] !== ($soma * 10) % 11 % 10) {
                return false;
            }
        }

        return true;
    }

    /**
     * Dígitos verificadores do CNPJ, numérico ou alfanumérico.
     *
     * A conta é a mesma para os dois: cada caractere vale o código ASCII menos
     * 48, então `0`–`9` valem o próprio dígito e `A` vale 17. Pesos de 2 a 9, da
     * direita para a esquerda, recomeçando.
     */
    private function cnpjValido(string $cnpj): bool
    {
        if (preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $cnpj) !== 1 || strlen(count_chars($cnpj, 3)) === 1) {
            return false;
        }

        foreach ([12, 13] as $posicao) {
            $soma = 0;
            $peso = $posicao - 7;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (ord($cnpj[$i]) - 48) * $peso;
                $peso = $peso === 2 ? 9 : $peso - 1;
            }

            $resto = $soma % 11;

            if ((int) $cnpj[$posicao] !== ($resto < 2 ? 0 : 11 - $resto)) {
                return false;
            }
        }

        return true;
    }
}
