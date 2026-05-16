<?php

namespace App\Enums;

enum ActivityType: string
{
    case Task = 'task';
    case Meeting = 'meeting';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::Task => 'Tarefa',
            self::Meeting => 'Reunião',
            self::FollowUp => 'Follow-up',
        };
    }
}
