<?php

namespace App\Enums;

enum SectionType: string
{
    case Hero              = 'hero';
    case Banner            = 'banner';
    case Cards             = 'cards';
    case Text              = 'text';
    case Gallery           = 'gallery';
    case Video             = 'video';
    case Faq               = 'faq';
    case Testimonials      = 'testimonials';
    case ProductsHighlight = 'products_highlight';
    case EventsHighlight   = 'events_highlight';

    public function label(): string
    {
        return match($this) {
            self::Hero              => 'Hero / Cabeçalho',
            self::Banner            => 'Banner',
            self::Cards             => 'Cards',
            self::Text              => 'Texto',
            self::Gallery           => 'Galeria',
            self::Video             => 'Vídeo',
            self::Faq               => 'FAQ',
            self::Testimonials      => 'Depoimentos',
            self::ProductsHighlight => 'Destaque de Produtos',
            self::EventsHighlight   => 'Destaque de Eventos',
        };
    }
}
