<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case CommercialManager = 'commercial_manager';
    case Sdr = 'sdr';
    case Closer = 'closer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::CommercialManager => 'Gestor Comercial',
            self::Sdr => 'SDR',
            self::Closer => 'Closer',
        };
    }

    public function canManage(): bool
    {
        return in_array($this, [self::Admin, self::CommercialManager], true);
    }
}
