<?php

namespace App\CatalogIntelligence\Enums;

/**
 * Que tipo de coisa é este conceito.
 *
 * A lista nasceu da auditoria dos 75 itens reais do catálogo, não de uma
 * ontologia genérica: há técnicas (bordado, costura, xilogravura), materiais
 * (barro, ervas, sementes), tipos de item (bolsa, vaso, kit), contextos de uso
 * (casa, presente), temas (artesanato, bem viver) e atributos (feito à mão).
 *
 * `style` e `audience` foram deliberadamente deixados de fora: nenhum item do
 * catálogo atual os exigiria, e um tipo sem uso é convite a classificação
 * arbitrária. Acrescentar um caso depois é barato — a coluna é string.
 */
enum KnowledgeEntryType: string
{
    case ProductType = 'product_type';
    case Technique = 'technique';
    case Material = 'material';
    case Context = 'context';
    case Theme = 'theme';
    case Attribute = 'attribute';

    public function label(): string
    {
        return match ($this) {
            self::ProductType => 'Tipo de item',
            self::Technique => 'Técnica',
            self::Material => 'Material',
            self::Context => 'Contexto de uso',
            self::Theme => 'Tema',
            self::Attribute => 'Atributo',
        };
    }
}
