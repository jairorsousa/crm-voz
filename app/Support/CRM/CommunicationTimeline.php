<?php

namespace App\Support\CRM;

use App\Enums\CommunicationChannel;
use App\Models\CommunicationMessage;

class CommunicationTimeline
{
    public static function record(CommunicationMessage $message, ?string $title = null, ?string $description = null): void
    {
        $message->loadMissing(['company', 'contact', 'user']);

        Timeline::record(
            company: $message->company,
            type: 'communication.'.$message->channel->value.'.'.$message->status->value,
            title: $title ?? self::title($message),
            description: $description ?? self::description($message),
            contact: $message->contact,
            user: $message->user,
            metadata: [
                'communication_message_id' => $message->id,
                'channel' => $message->channel->value,
                'status' => $message->status->value,
                'direction' => $message->direction->value,
                'origin' => $message->origin->value,
            ],
        );
    }

    private static function title(CommunicationMessage $message): string
    {
        return match ($message->channel) {
            CommunicationChannel::Call => 'Ligação registrada',
            CommunicationChannel::Email => 'E-mail registrado',
            CommunicationChannel::Whatsapp => 'WhatsApp registrado',
        };
    }

    private static function description(CommunicationMessage $message): string
    {
        $target = $message->contact->name;

        return match ($message->channel) {
            CommunicationChannel::Call => "Ligação para {$target}: {$message->status->label()}.",
            CommunicationChannel::Email => "E-mail para {$target}: {$message->subject}.",
            CommunicationChannel::Whatsapp => "Mensagem WhatsApp para {$target}.",
        };
    }
}
