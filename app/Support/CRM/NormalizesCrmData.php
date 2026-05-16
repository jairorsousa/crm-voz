<?php

namespace App\Support\CRM;

trait NormalizesCrmData
{
    protected function normalizeNullableDigits(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return preg_replace('/\D+/', '', $value);
    }

    protected function normalizeNullableEmail(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return mb_strtolower(trim($value));
    }

    protected function normalizeNullableText(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return trim($value);
    }

    protected function normalizeNullableState(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return mb_strtoupper(mb_substr(preg_replace('/[^A-Za-z]/', '', $value), 0, 2));
    }
}
