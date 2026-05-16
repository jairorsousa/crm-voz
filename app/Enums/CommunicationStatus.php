<?php

namespace App\Enums;

enum CommunicationStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Received = 'received';
    case Completed = 'completed';
    case Failed = 'failed';
    case NoAnswer = 'no_answer';
    case Busy = 'busy';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Na fila',
            self::Sent => 'Enviada',
            self::Delivered => 'Entregue',
            self::Received => 'Recebida',
            self::Completed => 'Concluída',
            self::Failed => 'Falhou',
            self::NoAnswer => 'Não atendeu',
            self::Busy => 'Ocupado',
            self::Canceled => 'Cancelada',
        };
    }
}
