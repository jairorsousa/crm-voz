<?php

namespace App\Support\CRM;

class ValidatesCnpj
{
    public static function passes(?string $value): bool
    {
        $cnpj = preg_replace('/\D+/', '', (string) $value);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $digits = array_map('intval', str_split($cnpj));
        $firstWeights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $secondWeights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $firstDigit = self::calculateDigit(array_slice($digits, 0, 12), $firstWeights);
        $secondDigit = self::calculateDigit(array_slice($digits, 0, 13), $secondWeights);

        return $digits[12] === $firstDigit && $digits[13] === $secondDigit;
    }

    /**
     * @param  array<int, int>  $digits
     * @param  array<int, int>  $weights
     */
    private static function calculateDigit(array $digits, array $weights): int
    {
        $sum = array_sum(array_map(fn (int $digit, int $weight): int => $digit * $weight, $digits, $weights));
        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
