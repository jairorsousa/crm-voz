<?php

namespace App\Enums;

enum AutomationActionType: string
{
    case CreateActivity = 'create_activity';
    case SendEmail = 'send_email';
    case SendWhatsapp = 'send_whatsapp';
    case NotifyUser = 'notify_user';
    case AddTimelineNote = 'add_timeline_note';

    public function label(): string
    {
        return match ($this) {
            self::CreateActivity => 'Criar tarefa',
            self::SendEmail => 'Enviar e-mail',
            self::SendWhatsapp => 'Enviar WhatsApp',
            self::NotifyUser => 'Notificar usuário interno',
            self::AddTimelineNote => 'Adicionar anotação no histórico',
        };
    }
}
