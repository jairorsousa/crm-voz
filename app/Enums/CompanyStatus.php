<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case NewLead = 'new_lead';
    case Prospecting = 'prospecting';
    case Qualified = 'qualified';
    case Negotiation = 'negotiation';
    case ActiveClient = 'active_client';
    case LostClient = 'lost_client';
    case NoFit = 'no_fit';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::NewLead => 'Lead novo',
            self::Prospecting => 'Em prospecção',
            self::Qualified => 'Qualificado',
            self::Negotiation => 'Em negociação',
            self::ActiveClient => 'Cliente ativo',
            self::LostClient => 'Cliente perdido',
            self::NoFit => 'Sem fit',
            self::Inactive => 'Inativo',
        };
    }
}
