<?php

namespace App\Enums;

enum PostType: string
{
    case Post     = 'post';
    case News     = 'news';
    case Campaign = 'campaign';

    public function label(): string
    {
        return match($this) {
            self::Post     => 'Post',
            self::News     => 'Notícia',
            self::Campaign => 'Campanha',
        };
    }
}
