<?php

namespace App\Enums;

enum VisibilitySlotType: string
{
    case HomeFeatured = 'home_featured';
    case HomeRotation = 'home_rotation';

    public function label(): string
    {
        return match ($this) {
            self::HomeFeatured => 'Destaque Pago',
            self::HomeRotation => 'Rotação Democrática',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::HomeFeatured => 'yellow',
            self::HomeRotation => 'blue',
        };
    }
}
