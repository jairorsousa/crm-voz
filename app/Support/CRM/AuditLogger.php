<?php

namespace App\Support\CRM;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        string $event,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null,
        array $metadata = [],
    ): void {
        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'event' => $event,
            'description' => $description,
            'old_values' => self::redact($oldValues),
            'new_values' => self::redact($newValues),
            'metadata' => self::redact($metadata),
            'ip_address' => Request::ip(),
            'user_agent' => mb_substr((string) Request::userAgent(), 0, 500),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function redact(array $values): array
    {
        $sensitive = ['password', 'secret', 'token', 'auth_token', 'api_key', 'key', 'webhook_token'];

        return collect($values)
            ->map(function (mixed $value, string|int $key) use ($sensitive): mixed {
                $key = (string) $key;

                if (in_array($key, $sensitive, true) || str_contains($key, 'token') || str_contains($key, 'secret')) {
                    return filled($value) ? '[redacted]' : null;
                }

                if (is_array($value)) {
                    return self::redact($value);
                }

                return $value;
            })
            ->all();
    }
}
