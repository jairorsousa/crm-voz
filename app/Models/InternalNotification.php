<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalNotification extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'commercial_automation_id',
        'title',
        'body',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(CommercialAutomation::class, 'commercial_automation_id');
    }
}
