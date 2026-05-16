<?php

namespace App\Support\CRM;

use App\Enums\CommunicationChannel;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;

class CommunicationOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function for(CommunicationChannel $channel, User $user): array
    {
        return [
            ...CrmOptions::all(),
            'channels' => CommunicationChannelResolver::optionsFor($channel, $user),
            'companies' => Company::query()
                ->visibleTo($user)
                ->orderBy('trade_name')
                ->orderBy('legal_name')
                ->get(['id', 'legal_name', 'trade_name', 'cnpj', 'phone', 'whatsapp', 'email'])
                ->map(fn (Company $company): array => [
                    'value' => $company->id,
                    'label' => $company->displayName(),
                    'description' => FormatsCrmData::cnpj($company->cnpj),
                    'phone' => $company->phone,
                    'whatsapp' => $company->whatsapp,
                    'email' => $company->email,
                ]),
            'contacts' => Contact::query()
                ->visibleTo($user)
                ->with('company:id,legal_name,trade_name')
                ->orderBy('name')
                ->get(['id', 'company_id', 'name', 'email', 'phone', 'whatsapp'])
                ->map(fn (Contact $contact): array => [
                    'value' => $contact->id,
                    'label' => $contact->name,
                    'description' => $contact->company->displayName(),
                    'company_id' => $contact->company_id,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'whatsapp' => $contact->whatsapp,
                ]),
            'opportunities' => Opportunity::query()
                ->visibleTo($user)
                ->orderBy('title')
                ->get(['id', 'company_id', 'title'])
                ->map(fn (Opportunity $opportunity): array => [
                    'value' => $opportunity->id,
                    'label' => $opportunity->title,
                    'company_id' => $opportunity->company_id,
                ]),
            'templates' => CommunicationTemplate::query()
                ->where('channel', $channel)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'subject', 'body'])
                ->map(fn (CommunicationTemplate $template): array => [
                    'value' => $template->id,
                    'label' => $template->name,
                    'subject' => $template->subject,
                    'body' => $template->body,
                ]),
        ];
    }
}
