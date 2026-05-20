<?php

namespace App\Models;

use App\Enums\OpportunityStatus;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pipeline_id',
        'pipeline_stage_id',
        'company_id',
        'contact_id',
        'responsible_user_id',
        'title',
        'estimated_value',
        'probability',
        'expected_close_date',
        'source',
        'status',
        'products_interests',
        'notes',
        'lost_reason',
        'closed_value',
        'closed_at',
        'last_stage_changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OpportunityStatus::class,
            'estimated_value' => 'decimal:2',
            'closed_value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'closed_at' => 'date',
            'last_stage_changed_at' => 'datetime',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function stageMovements(): HasMany
    {
        return $this->hasMany(OpportunityStageMovement::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function communicationMessages(): HasMany
    {
        return $this->hasMany(CommunicationMessage::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        $term = mb_strtolower(trim($search));

        return $query->where(function (Builder $query) use ($term): void {
            $query
                ->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(source) LIKE ?', ["%{$term}%"])
                ->orWhereHas('company', function (Builder $query) use ($term): void {
                    $query
                        ->whereRaw('LOWER(legal_name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$term}%"]);
                })
                ->orWhereHas('contact', fn (Builder $query) => $query->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]));
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role?->canManage()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('responsible_user_id', $user->id)
                ->orWhereHas('company', fn (Builder $query) => $query->where('responsible_user_id', $user->id));
        });
    }
}
