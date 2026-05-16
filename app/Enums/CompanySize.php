<?php

namespace App\Enums;

enum CompanySize: string
{
    case Micro = 'micro';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Micro => 'Micro',
            self::Small => 'Pequena',
            self::Medium => 'Média',
            self::Large => 'Grande',
            self::Enterprise => 'Enterprise',
        };
    }
}
