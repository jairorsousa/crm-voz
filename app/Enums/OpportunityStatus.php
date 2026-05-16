<?php

namespace App\Enums;

enum OpportunityStatus: string
{
    case Open = 'open';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::Won => 'Fechada ganha',
            self::Lost => 'Fechada perdida',
        };
    }
}
