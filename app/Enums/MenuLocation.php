<?php

namespace App\Enums;

enum MenuLocation: string
{
    case Header  = 'header';
    case Footer  = 'footer';
    case Sidebar = 'sidebar';
    case Mobile  = 'mobile';

    public function label(): string
    {
        return match($this) {
            self::Header  => 'Cabeçalho',
            self::Footer  => 'Rodapé',
            self::Sidebar => 'Barra Lateral',
            self::Mobile  => 'Mobile',
        };
    }
}
