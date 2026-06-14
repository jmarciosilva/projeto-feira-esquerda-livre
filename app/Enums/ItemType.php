<?php

namespace App\Enums;

enum ItemType: string
{
    case Produto = 'produto';
    case Servico = 'servico';
    case Cuidado = 'cuidado';

    public function label(): string
    {
        return match($this) {
            self::Produto => 'Produto',
            self::Servico => 'Serviço',
            self::Cuidado => 'Cuidado & Bem Viver',
        };
    }

    public function emoji(): string
    {
        return match($this) {
            self::Produto => '🛍️',
            self::Servico => '🎯',
            self::Cuidado => '🌿',
        };
    }

    public function routeSlug(): string
    {
        return match($this) {
            self::Produto => 'produtos',
            self::Servico => 'servicos',
            self::Cuidado => 'cuidados',
        };
    }

    public static function fromRouteSlug(string $slug): self
    {
        return match($slug) {
            'produtos'  => self::Produto,
            'servicos'  => self::Servico,
            'cuidados'  => self::Cuidado,
            default     => self::Produto,
        };
    }
}
