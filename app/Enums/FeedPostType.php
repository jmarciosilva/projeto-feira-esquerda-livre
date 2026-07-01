<?php

namespace App\Enums;

enum FeedPostType: string
{
    case Feira = 'foto_feira';
    case Produto = 'novo_produto';
    case Aviso = 'aviso';
    case Texto = 'texto_livre';

    public function label(): string
    {
        return match ($this) {
            self::Feira => 'Foto da feira',
            self::Produto => 'Novo produto',
            self::Aviso => 'Aviso',
            self::Texto => 'Texto livre',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Feira => 'background:#dbeafe; color:#1e40af;',
            self::Produto => 'background:#fef3c7; color:#92400e;',
            self::Aviso => 'background:#fee2e2; color:#991b1b;',
            self::Texto => 'background:#dcfce7; color:#166534;',
        };
    }
}
