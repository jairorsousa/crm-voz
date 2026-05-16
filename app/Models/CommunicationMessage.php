<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDirection;
use App\Enums\CommunicationOrigin;
use App\Enums\CommunicationStatus;
use Database\Factories\CommunicationMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationMessage extends Model
{
    /** @use HasFactory<CommunicationMessageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'contact_id',
        'opportunity_id',
        'user_id',
        'communication_template_id',
        'communication_channel_id',
        'channel',
        'direction',
        'status',
        'origin',
        'provider',
        'external_id',
        'from_address',
        'to_address',
        'cc',
        'bcc',
        'subject',
        'body',
        'notes',
        'attachments',
        'provider_payload',
        'error_message',
        'duration_seconds',
        'queued_at',
        'sent_at',
        'delivered_at',
        'received_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => CommunicationChannel::class,
            'direction' => CommunicationDirection::class,
            'status' => CommunicationStatus::class,
            'origin' => CommunicationOrigin::class,
            'cc' => 'array',
            'bcc' => 'array',
            'attachments' => 'array',
            'provider_payload' => 'array',
            'duration_seconds' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'communication_template_id');
    }

    public function communicationChannel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CommunicationChannel::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role?->canManage()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('user_id', $user->id)
                ->orWhereHas('company', fn (Builder $query) => $query->where('responsible_user_id', $user->id))
                ->orWhereHas('opportunity', fn (Builder $query) => $query->where('responsible_user_id', $user->id));
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
                ->whereRaw('LOWER(subject) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(body) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(notes) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(to_address) LIKE ?', ["%{$term}%"])
                ->orWhereHas('company', function (Builder $query) use ($term): void {
                    $query
                        ->whereRaw('LOWER(legal_name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$term}%"]);
                })
                ->orWhereHas('contact', fn (Builder $query) => $query->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]));
        });
    }
}
