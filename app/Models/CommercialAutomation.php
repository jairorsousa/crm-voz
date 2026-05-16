<?php

namespace App\Models;

use App\Enums\AutomationTrigger;
use Database\Factories\CommercialAutomationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CommercialAutomation extends Model
{
    /** @use HasFactory<CommercialAutomationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'created_by_user_id',
        'name',
        'description',
        'trigger',
        'conditions',
        'actions',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => AutomationTrigger::class,
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AutomationExecution::class);
    }

    public function latestExecution(): HasOne
    {
        return $this->hasOne(AutomationExecution::class)->latestOfMany();
    }
}
