<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\PriorityLevel;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'contact_id',
        'opportunity_id',
        'assigned_to_user_id',
        'created_by_user_id',
        'type',
        'status',
        'priority',
        'title',
        'description',
        'due_at',
        'completed_at',
        'canceled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'status' => ActivityStatus::class,
            'priority' => PriorityLevel::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role?->canManage()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('assigned_to_user_id', $user->id)
                ->orWhere('created_by_user_id', $user->id);
        });
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
                ->orWhereRaw('LOWER(description) LIKE ?', ["%{$term}%"])
                ->orWhereHas('company', function (Builder $query) use ($term): void {
                    $query
                        ->whereRaw('LOWER(legal_name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$term}%"]);
                })
                ->orWhereHas('contact', fn (Builder $query) => $query->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]));
        });
    }

    public function isOverdue(): bool
    {
        return $this->status === ActivityStatus::Pending && $this->due_at->isPast();
    }
}
