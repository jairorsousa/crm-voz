<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class CrmSetting extends Model
{
    /**
     * @var array<string, list<string>>
     */
    private const SENSITIVE_FIELDS = [
        'integrations.twilio' => ['auth_token', 'webhook_token'],
        'integrations.evolution' => ['key', 'webhook_token'],
        'integrations.mail' => ['password'],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'label',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function valueFor(string $key, array $default = []): array
    {
        return Cache::rememberForever(
            "crm_setting:{$key}",
            fn (): array => self::decryptSensitiveFields($key, self::query()->where('key', $key)->first()?->value ?? $default),
        );
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function putValue(string $group, string $key, string $label, array $value): self
    {
        Cache::forget("crm_setting:{$key}");

        return self::query()->updateOrCreate([
            'key' => $key,
        ], [
            'group' => $group,
            'label' => $label,
            'value' => self::encryptSensitiveFields($key, $value),
        ]);
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private static function encryptSensitiveFields(string $key, array $value): array
    {
        foreach (self::SENSITIVE_FIELDS[$key] ?? [] as $field) {
            if (! filled($value[$field] ?? null) || str_starts_with((string) $value[$field], 'encrypted:')) {
                continue;
            }

            $value[$field] = 'encrypted:'.Crypt::encryptString((string) $value[$field]);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private static function decryptSensitiveFields(string $key, array $value): array
    {
        foreach (self::SENSITIVE_FIELDS[$key] ?? [] as $field) {
            $fieldValue = $value[$field] ?? null;

            if (! is_string($fieldValue) || ! str_starts_with($fieldValue, 'encrypted:')) {
                continue;
            }

            $value[$field] = Crypt::decryptString(mb_substr($fieldValue, 10));
        }

        return $value;
    }
}
