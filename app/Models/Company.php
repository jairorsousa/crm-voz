<?php

namespace App\Models;

use App\Enums\CompanySize;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\LeadTemperature;
use App\Enums\PriorityLevel;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legal_name',
        'trade_name',
        'cnpj',
        'segment',
        'site',
        'phone',
        'email',
        'whatsapp',
        'city',
        'state',
        'address',
        'status',
        'lead_source',
        'responsible_user_id',
        'last_interaction_at',
        'average_collection_ticket',
        'overdue_customers_count',
        'total_default_amount',
        'approx_customers_count',
        'current_system',
        'has_internal_collection_team',
        'has_erp_integration',
        'portfolio_notes',
        'company_type',
        'company_size',
        'commercial_potential',
        'lead_temperature',
        'priority',
        'pain_profile',
        'closing_probability',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'company_type' => CompanyType::class,
            'company_size' => CompanySize::class,
            'lead_temperature' => LeadTemperature::class,
            'priority' => PriorityLevel::class,
            'last_interaction_at' => 'datetime',
            'average_collection_ticket' => 'decimal:2',
            'overdue_customers_count' => 'integer',
            'total_default_amount' => 'decimal:2',
            'approx_customers_count' => 'integer',
            'has_internal_collection_team' => 'boolean',
            'has_erp_integration' => 'boolean',
            'closing_probability' => 'integer',
        ];
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function communicationMessages(): HasMany
    {
        return $this->hasMany(CommunicationMessage::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        $digits = preg_replace('/\D+/', '', $search) ?: $search;
        $term = mb_strtolower(trim($search));

        return $query->where(function (Builder $query) use ($digits, $term): void {
            $query
                ->whereRaw('LOWER(legal_name) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$term}%"])
                ->orWhere('cnpj', 'like', "%{$digits}%")
                ->orWhere('phone', 'like', "%{$digits}%")
                ->orWhere('whatsapp', 'like', "%{$digits}%")
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"])
                ->orWhereHas('contacts', function (Builder $query) use ($digits, $term): void {
                    $query
                        ->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"])
                        ->orWhere('phone', 'like', "%{$digits}%")
                        ->orWhere('whatsapp', 'like', "%{$digits}%");
                });
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role?->canManage()) {
            return $query;
        }

        return $query->where('responsible_user_id', $user->id);
    }

    public function displayName(): string
    {
        return $this->trade_name ?: $this->legal_name;
    }
}
