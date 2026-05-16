<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreCompanyRequest;
use App\Http\Requests\CRM\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Support\CRM\CrmOptions;
use App\Support\CRM\FormatsCrmData;
use App\Support\CRM\Timeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'lead_source' => $request->string('lead_source')->toString(),
            'segment' => $request->string('segment')->toString(),
            'responsible_user_id' => $request->string('responsible_user_id')->toString(),
            'lead_temperature' => $request->string('lead_temperature')->toString(),
            'priority' => $request->string('priority')->toString(),
        ];

        $companies = Company::query()
            ->visibleTo($request->user())
            ->with('responsibleUser:id,name')
            ->withCount('contacts')
            ->search($filters['search'])
            ->when($filters['status'], fn ($query, string $value) => $query->where('status', $value))
            ->when($filters['lead_source'], fn ($query, string $value) => $query->where('lead_source', $value))
            ->when($filters['segment'], fn ($query, string $value) => $query->where('segment', $value))
            ->when($filters['responsible_user_id'], fn ($query, string $value) => $query->where('responsible_user_id', $value))
            ->when($filters['lead_temperature'], fn ($query, string $value) => $query->where('lead_temperature', $value))
            ->when($filters['priority'], fn ($query, string $value) => $query->where('priority', $value))
            ->latest('last_interaction_at')
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Company $company): array => $this->companyListPayload($company));

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Companies/Form', [
            'mode' => 'create',
            'company' => null,
            'options' => $this->options(),
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! $request->user()->role?->canManage()) {
            $validated['responsible_user_id'] = $request->user()->id;
        }

        $company = Company::query()->create($validated);

        Timeline::record(
            company: $company,
            type: 'company.created',
            title: 'Empresa cadastrada',
            description: "A empresa {$company->displayName()} foi cadastrada no CRM.",
            user: $request->user(),
        );

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Empresa cadastrada com sucesso.');
    }

    public function show(Company $company): Response
    {
        $this->authorizeCompany($company);

        $company->load([
            'responsibleUser:id,name,email',
            'contacts' => fn ($query) => $query->latest('is_primary')->latest(),
            'opportunities' => fn ($query) => $query
                ->with(['stage:id,name,color,is_won,is_lost', 'responsibleUser:id,name'])
                ->latest('last_stage_changed_at')
                ->latest()
                ->limit(8),
            'activities' => fn ($query) => $query
                ->with(['assignedTo:id,name'])
                ->orderByRaw("CASE WHEN status = 'pending' AND due_at < ? THEN 0 ELSE 1 END", [now()])
                ->orderBy('due_at')
                ->limit(8),
            'communicationMessages' => fn ($query) => $query
                ->with(['contact:id,name', 'user:id,name'])
                ->latest()
                ->limit(8),
            'timelineEvents' => fn ($query) => $query->with('user:id,name')->latest('occurred_at')->limit(12),
        ]);

        return Inertia::render('Companies/Show', [
            'company' => $this->companyDetailPayload($company),
            'options' => $this->options(),
        ]);
    }

    public function edit(Company $company): Response
    {
        $this->authorizeCompany($company);

        return Inertia::render('Companies/Form', [
            'mode' => 'edit',
            'company' => $this->companyFormPayload($company),
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorizeCompany($company);
        $validated = $request->validated();

        if (! $request->user()->role?->canManage()) {
            $validated['responsible_user_id'] = $company->responsible_user_id;
        }

        $company->update($validated);

        Timeline::record(
            company: $company,
            type: 'company.updated',
            title: 'Empresa atualizada',
            description: "Os dados de {$company->displayName()} foram atualizados.",
            user: $request->user(),
        );

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Empresa atualizada com sucesso.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorizeCompany($company);

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', 'Empresa removida com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            ...CrmOptions::all(),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                    'description' => $user->email,
                ]),
            'segments' => Company::query()
                ->visibleTo(request()->user())
                ->whereNotNull('segment')
                ->distinct()
                ->orderBy('segment')
                ->pluck('segment'),
            'leadSources' => Company::query()
                ->visibleTo(request()->user())
                ->whereNotNull('lead_source')
                ->distinct()
                ->orderBy('lead_source')
                ->pluck('lead_source'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companyListPayload(Company $company): array
    {
        return [
            'id' => $company->id,
            'legal_name' => $company->legal_name,
            'trade_name' => $company->trade_name,
            'display_name' => $company->displayName(),
            'cnpj' => $company->cnpj,
            'formatted_cnpj' => FormatsCrmData::cnpj($company->cnpj),
            'segment' => $company->segment,
            'city' => $company->city,
            'state' => $company->state,
            'status' => $this->enumPayload($company->status),
            'lead_temperature' => $this->enumPayload($company->lead_temperature),
            'priority' => $this->enumPayload($company->priority),
            'responsible_user' => $company->responsibleUser ? [
                'id' => $company->responsibleUser->id,
                'name' => $company->responsibleUser->name,
            ] : null,
            'contacts_count' => $company->contacts_count,
            'last_interaction_at' => $company->last_interaction_at?->toISOString(),
            'created_at' => $company->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companyDetailPayload(Company $company): array
    {
        return [
            ...$this->companyFormPayload($company),
            'display_name' => $company->displayName(),
            'formatted_cnpj' => FormatsCrmData::cnpj($company->cnpj),
            'formatted_phone' => FormatsCrmData::phone($company->phone),
            'formatted_whatsapp' => FormatsCrmData::phone($company->whatsapp),
            'status_label' => $company->status->label(),
            'lead_temperature_label' => $company->lead_temperature->label(),
            'priority_label' => $company->priority->label(),
            'responsible_user' => $company->responsibleUser ? [
                'id' => $company->responsibleUser->id,
                'name' => $company->responsibleUser->name,
                'email' => $company->responsibleUser->email,
            ] : null,
            'contacts' => $company->contacts->map(fn ($contact): array => [
                'id' => $contact->id,
                'name' => $contact->name,
                'position' => $contact->position,
                'department' => $contact->department,
                'email' => $contact->email,
                'formatted_phone' => FormatsCrmData::phone($contact->phone),
                'formatted_whatsapp' => FormatsCrmData::phone($contact->whatsapp),
                'type_label' => $contact->type->label(),
                'is_primary' => $contact->is_primary,
                'receives_automations' => $contact->receives_automations,
            ]),
            'opportunities' => $company->opportunities->map(fn ($opportunity): array => [
                'id' => $opportunity->id,
                'title' => $opportunity->title,
                'formatted_estimated_value' => FormatsCrmData::money($opportunity->estimated_value),
                'probability' => $opportunity->probability,
                'expected_close_date' => $opportunity->expected_close_date?->toDateString(),
                'status_label' => $opportunity->status->label(),
                'stage' => [
                    'id' => $opportunity->stage->id,
                    'name' => $opportunity->stage->name,
                    'color' => $opportunity->stage->color,
                ],
                'responsible_user' => $opportunity->responsibleUser ? [
                    'id' => $opportunity->responsibleUser->id,
                    'name' => $opportunity->responsibleUser->name,
                ] : null,
            ]),
            'activities' => $company->activities->map(fn ($activity): array => [
                'id' => $activity->id,
                'title' => $activity->title,
                'type_label' => $activity->type->label(),
                'status_label' => $activity->status->label(),
                'status' => $activity->status->value,
                'priority_label' => $activity->priority->label(),
                'due_at' => $activity->due_at?->toISOString(),
                'is_overdue' => $activity->isOverdue(),
                'assigned_to' => [
                    'id' => $activity->assignedTo->id,
                    'name' => $activity->assignedTo->name,
                ],
            ]),
            'timeline_events' => $company->timelineEvents->map(fn ($event): array => [
                'id' => $event->id,
                'type' => $event->type,
                'title' => $event->title,
                'description' => $event->description,
                'user_name' => $event->user?->name,
                'occurred_at' => $event->occurred_at?->toISOString(),
            ]),
            'communication_messages' => $company->communicationMessages->map(fn ($message): array => [
                'id' => $message->id,
                'channel' => [
                    'value' => $message->channel->value,
                    'label' => $message->channel->label(),
                ],
                'direction' => [
                    'value' => $message->direction->value,
                    'label' => $message->direction->label(),
                ],
                'status' => [
                    'value' => $message->status->value,
                    'label' => $message->status->label(),
                ],
                'subject' => $message->subject,
                'body' => $message->body,
                'to_address' => $message->to_address,
                'error_message' => $message->error_message,
                'created_at' => $message->created_at?->toISOString(),
                'contact' => [
                    'id' => $message->contact->id,
                    'name' => $message->contact->name,
                ],
                'user' => $message->user ? [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                ] : null,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companyFormPayload(Company $company): array
    {
        return [
            'id' => $company->id,
            'legal_name' => $company->legal_name,
            'trade_name' => $company->trade_name,
            'cnpj' => $company->cnpj,
            'segment' => $company->segment,
            'site' => $company->site,
            'phone' => $company->phone,
            'email' => $company->email,
            'whatsapp' => $company->whatsapp,
            'city' => $company->city,
            'state' => $company->state,
            'address' => $company->address,
            'status' => $company->status->value,
            'lead_source' => $company->lead_source,
            'responsible_user_id' => $company->responsible_user_id,
            'average_collection_ticket' => $company->average_collection_ticket,
            'overdue_customers_count' => $company->overdue_customers_count,
            'total_default_amount' => $company->total_default_amount,
            'approx_customers_count' => $company->approx_customers_count,
            'current_system' => $company->current_system,
            'has_internal_collection_team' => $company->has_internal_collection_team,
            'has_erp_integration' => $company->has_erp_integration,
            'portfolio_notes' => $company->portfolio_notes,
            'company_type' => $company->company_type?->value,
            'company_size' => $company->company_size?->value,
            'commercial_potential' => $company->commercial_potential,
            'lead_temperature' => $company->lead_temperature->value,
            'priority' => $company->priority->value,
            'pain_profile' => $company->pain_profile,
            'closing_probability' => $company->closing_probability,
            'last_interaction_at' => $company->last_interaction_at?->toISOString(),
            'created_at' => $company->created_at?->toISOString(),
        ];
    }

    /**
     * @return array{value: string, label: string}
     */
    private function enumPayload(object $enum): array
    {
        return [
            'value' => $enum->value,
            'label' => $enum->label(),
        ];
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless(
            auth()->user()?->role?->canManage()
                || $company->responsible_user_id === auth()->id(),
            403
        );
    }
}
