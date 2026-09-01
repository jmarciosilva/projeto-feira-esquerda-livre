<?php

namespace App\CatalogIntelligence\Enums;

/**
 * Uma lacuna do item, e o pedido que ela vira para o lojista.
 *
 * É a tradução que a §3.4 do documento arquitetural exige e que a CAT-05D
 * ainda não tinha: *"`missing_information` é o mecanismo antialucinação: em vez
 * de inventar material, a inteligência devolve **'informe o material'**"*.
 *
 * Até aqui `missing_information` devolvia nome técnico de coluna —
 * `short_description`, `attributes` —, que é diagnóstico interno e não pedido.
 * Um lojista não sabe o que é `attributes`, e uma lista de nomes de campo não
 * cumpre a promessa da regra 1 das invioláveis: *"na dúvida: omitir e **pedir a
 * informação ao lojista**"*.
 *
 * ## Por que enum, e não um array de tradução
 *
 * O conjunto de lacunas é **fechado** — ele vem de `ListingContext::lacunas()`,
 * que enumera exatamente cinco. Um `match` sobre enum falha em tempo de
 * compilação quando alguém acrescentar a sexta; um array associativo devolveria
 * `null` silenciosamente e a lacuna nova sumiria da sugestão sem ninguém notar.
 *
 * `tryFrom()` é o que liga os dois lados: `lacunas()` devolve string, e uma
 * string desconhecida vira `null` e é descartada em vez de virar pedido vazio.
 *
 * ## O texto fala com o lojista, não com o programador
 *
 * Cada pedido diz **o que informar** e, quando ajuda, **por que aquilo
 * importa** — um resumo existe para aparecer no card, uma categoria existe para
 * o item ser encontrado. Sem isso o pedido vira cobrança sem motivo, e a
 * primeira coisa que um lojista faz com cobrança sem motivo é ignorá-la.
 */
enum ListingGap: string
{
    case ShortDescription = 'short_description';
    case Description = 'description';
    case Category = 'category';
    case Attributes = 'attributes';
    case Knowledge = 'knowledge';

    /**
     * O pedido, na linguagem de quem cadastra.
     *
     * `Attributes` é o caso que a §3.4 cita textualmente, e por isso nomeia os
     * fatos objetivos que a regra 1 lista — material, medidas, origem — em vez
     * de pedir "atributos". A inteligência não os inventa; ela os pede.
     */
    public function pedido(): string
    {
        return match ($this) {
            self::ShortDescription => 'Escreva um resumo curto do item: é ele que aparece nos cards do catálogo e na busca.',
            self::Description => 'Descreva o item com as suas palavras — o que é, como foi feito e para que serve.',
            self::Category => 'Escolha a categoria do item, para que ele apareça no lugar certo do catálogo.',
            self::Attributes => 'Informe o material, as medidas e a origem da peça. A Feira não preenche esses dados por você: eles só entram no texto se você contar.',
            self::Knowledge => 'Dê mais detalhe ao texto do item: a Feira ainda não reconheceu nenhum tema conhecido nele, e sem isso não há o que sugerir.',
        };
    }

    /**
     * O assistente pode preencher esta lacuna sozinho?
     *
     * Só o resumo e a descrição — e mesmo esses apenas quando há conhecimento
     * de onde compor. As outras três dependem de alguém: categoria é escolha do
     * lojista, atributo é fato que só ele sabe, e conhecimento é trabalho da
     * curadoria.
     *
     * Serve para que `missing_information` não peça o que a própria sugestão
     * está oferecendo — pedir "escreva um resumo" ao lado de um resumo pronto
     * é ruído que faz o lojista desconfiar dos outros pedidos.
     */
    public function podeSerPreenchidaPelaSugestao(): bool
    {
        return $this === self::ShortDescription || $this === self::Description;
    }
}
