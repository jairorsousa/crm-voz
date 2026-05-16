<?php

namespace App\Enums;

enum CompanyType: string
{
    case School = 'school';
    case College = 'college';
    case Course = 'course';
    case ServiceProvider = 'service_provider';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::School => 'Escola',
            self::College => 'Faculdade',
            self::Course => 'Curso',
            self::ServiceProvider => 'Prestadora de serviços',
            self::Other => 'Outro',
        };
    }
}
