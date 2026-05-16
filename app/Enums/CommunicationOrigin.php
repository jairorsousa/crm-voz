<?php

namespace App\Enums;

enum CommunicationOrigin: string
{
    case Manual = 'manual';
    case Automated = 'automated';
    case Webhook = 'webhook';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Automated => 'Automática',
            self::Webhook => 'Webhook',
        };
    }
}
