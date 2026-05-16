<?php

namespace App\Support\CRM;

use App\Models\Company;
use App\Models\Contact;
use App\Models\TimelineEvent;
use App\Models\User;

class Timeline
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        Company $company,
        string $type,
        string $title,
        ?string $description = null,
        ?Contact $contact = null,
        ?User $user = null,
        array $metadata = [],
    ): TimelineEvent {
        $event = TimelineEvent::query()->create([
            'company_id' => $company->id,
            'contact_id' => $contact?->id,
            'user_id' => $user?->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);

        $company->forceFill(['last_interaction_at' => now()])->save();

        return $event;
    }
}
