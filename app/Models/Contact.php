<?php

namespace App\Models;

use App\Enums\ContactType;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'position',
        'department',
        'email',
        'phone',
        'whatsapp',
        'linkedin_url',
        'type',
        'is_primary',
        'receives_automations',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
            'is_primary' => 'boolean',
            'receives_automations' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
                ->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(position) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(department) LIKE ?', ["%{$term}%"])
                ->orWhere('phone', 'like', "%{$digits}%")
                ->orWhere('whatsapp', 'like', "%{$digits}%")
                ->orWhereHas('company', function (Builder $query) use ($digits, $term): void {
                    $query
                        ->whereRaw('LOWER(legal_name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$term}%"])
                        ->orWhere('cnpj', 'like', "%{$digits}%");
                });
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role?->canManage()) {
            return $query;
        }

        return $query->whereHas('company', fn (Builder $query) => $query->where('responsible_user_id', $user->id));
    }
}
