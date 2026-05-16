<?php

namespace App\Enums;

enum LeadTemperature: string
{
    case Cold = 'cold';
    case Warm = 'warm';
    case Hot = 'hot';

    public function label(): string
    {
        return match ($this) {
            self::Cold => 'Frio',
            self::Warm => 'Morno',
            self::Hot => 'Quente',
        };
    }
}
