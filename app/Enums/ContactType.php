<?php

namespace App\Enums;

enum ContactType: string
{
    case DecisionMaker = 'decision_maker';
    case Influencer = 'influencer';
    case Financial = 'financial';
    case Operations = 'operations';
    case It = 'it';
    case Legal = 'legal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DecisionMaker => 'Decisor',
            self::Influencer => 'Influenciador',
            self::Financial => 'Financeiro',
            self::Operations => 'Operacional',
            self::It => 'TI',
            self::Legal => 'Jurídico',
            self::Other => 'Outro',
        };
    }
}
