<?php

namespace App\Models;

use App\Enums\CommunicationChannel as CommunicationChannelType;
use Database\Factories\CommunicationChannelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class CommunicationChannel extends Model
{
    /** @use HasFactory<CommunicationChannelFactory> */
    use HasFactory;

    /**
     * @var array<string, list<string>>
     */
    private const SENSITIVE_FIELDS = [
        'twilio' => ['auth_token', 'webhook_token'],
        'evolution' => ['key', 'webhook_token'],
        'smtp' => ['password'],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'provider',
        'config',
        'is_active',
        'is_shared',
        'is_default',
        'last_tested_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CommunicationChannelType::class,
            'is_active' => 'boolean',
            'is_shared' => 'boolean',
            'is_default' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<array<string, mixed>, array<string, mixed>|null>
     */
    protected function config(): Attribute
    {
        return Attribute::make(
            get: fn ($value): array => $this->decryptConfig(json_decode($value ?: '[]', true) ?: []),
            set: fn (?array $value): string => json_encode($this->encryptConfig($value ?? []), JSON_THROW_ON_ERROR),
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunicationMessage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, CommunicationChannelType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeAvailableTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('is_shared', true)
                ->orWhereHas('users', fn (Builder $query) => $query->whereKey($user->id));
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function safeConfig(): array
    {
        $config = $this->config;

        foreach (self::SENSITIVE_FIELDS[$this->provider] ?? [] as $field) {
            if (filled($config[$field] ?? null)) {
                $config[$field] = null;
            }
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return [
            ...$this->config,
            'provider' => $this->provider,
            'channel_id' => $this->id,
            'channel_name' => $this->name,
        ];
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            'twilio' => 'Twilio',
            'evolution' => 'Evolution API',
            'smtp' => 'SMTP',
            default => $this->provider,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function encryptConfig(array $config): array
    {
        foreach (self::SENSITIVE_FIELDS[$this->provider] ?? [] as $field) {
            if (! filled($config[$field] ?? null) || str_starts_with((string) $config[$field], 'encrypted:')) {
                continue;
            }

            $config[$field] = 'encrypted:'.Crypt::encryptString((string) $config[$field]);
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function decryptConfig(array $config): array
    {
        foreach (self::SENSITIVE_FIELDS[$this->provider] ?? [] as $field) {
            $value = $config[$field] ?? null;

            if (! is_string($value) || ! str_starts_with($value, 'encrypted:')) {
                continue;
            }

            $config[$field] = Crypt::decryptString(mb_substr($value, 10));
        }

        return $config;
    }
}
