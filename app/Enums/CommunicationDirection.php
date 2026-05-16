<?php

namespace App\Enums;

enum CommunicationDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';

    public function label(): string
    {
        return match ($this) {
            self::Inbound => 'Recebida',
            self::Outbound => 'Enviada',
        };
    }
}
