<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Rascunho',
            self::Published => 'Publicado',
            self::Archived  => 'Arquivado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'yellow',
            self::Published => 'green',
            self::Archived  => 'gray',
        };
    }
}
