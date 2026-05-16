<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use Database\Factories\CommunicationTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationTemplate extends Model
{
    /** @use HasFactory<CommunicationTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'name',
        'subject',
        'body',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => CommunicationChannel::class,
            'is_active' => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunicationMessage::class);
    }
}
