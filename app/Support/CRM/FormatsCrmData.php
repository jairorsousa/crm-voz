<?php

namespace App\Support\CRM;

class FormatsCrmData
{
    public static function cnpj(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $value);
    }

    public static function phone(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (strlen($value) === 11) {
            return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $value);
        }

        if (strlen($value) === 10) {
            return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $value);
        }

        return $value;
    }

    public static function money(null|string|int|float $value): string
    {
        if ($value === null || $value === '') {
            return 'R$ 0,00';
        }

        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }
}
