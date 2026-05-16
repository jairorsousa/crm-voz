<?php

namespace App\Enums;

enum AutomationExecutionStatus: string
{
    case Success = 'success';
    case Skipped = 'skipped';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Sucesso',
            self::Skipped => 'Ignorada',
            self::Failed => 'Falhou',
        };
    }
}
