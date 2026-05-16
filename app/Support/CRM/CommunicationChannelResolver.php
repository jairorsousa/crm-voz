<?php

namespace App\Support\CRM;

use App\Enums\CommunicationChannel as CommunicationChannelType;
use App\Models\CommunicationChannel;
use App\Models\CommunicationMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class CommunicationChannelResolver
{
    public static function forMessage(CommunicationMessage $message): ?CommunicationChannel
    {
        return $message->communicationChannel;
    }

    public static function availableQuery(CommunicationChannelType $type, User $user): Builder
    {
        return CommunicationChannel::query()
            ->active()
            ->ofType($type)
            ->availableTo($user)
            ->orderByDesc('is_default')
            ->orderBy('name');
    }

    public static function defaultFor(CommunicationChannelType $type, User $user): ?CommunicationChannel
    {
        return self::availableQuery($type, $user)->first();
    }

    public static function authorizedFor(int|string|null $id, CommunicationChannelType $type, User $user): CommunicationChannel
    {
        $query = self::availableQuery($type, $user);

        $channel = filled($id)
            ? $query->whereKey($id)->first()
            : self::defaultFor($type, $user);

        if (! $channel) {
            throw new RuntimeException("Nenhum canal ativo de {$type->label()} disponível para este usuário.");
        }

        return $channel;
    }

    /**
     * @return list<array{value: int, label: string, description: string}>
     */
    public static function optionsFor(CommunicationChannelType $type, User $user): array
    {
        return self::availableQuery($type, $user)
            ->get()
            ->map(fn (CommunicationChannel $channel): array => [
                'value' => $channel->id,
                'label' => $channel->name,
                'description' => $channel->providerLabel(),
            ])
            ->all();
    }
}
