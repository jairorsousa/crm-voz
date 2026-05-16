<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationWebhookEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'event_type',
        'external_event_id',
        'external_message_id',
        'payload',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
