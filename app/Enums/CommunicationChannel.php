<?php

namespace App\Enums;

enum CommunicationChannel: string
{
    case Call = 'call';
    case Email = 'email';
    case Whatsapp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Ligação',
            self::Email => 'E-mail',
            self::Whatsapp => 'WhatsApp',
        };
    }
}
