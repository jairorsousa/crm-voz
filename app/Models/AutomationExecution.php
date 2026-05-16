<?php

namespace App\Models;

use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationTrigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationExecution extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'commercial_automation_id',
        'company_id',
        'contact_id',
        'opportunity_id',
        'activity_id',
        'user_id',
        'trigger',
        'idempotency_key',
        'status',
        'payload',
        'result',
        'error_message',
        'executed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => AutomationTrigger::class,
            'status' => AutomationExecutionStatus::class,
            'payload' => 'array',
            'result' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(CommercialAutomation::class, 'commercial_automation_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
