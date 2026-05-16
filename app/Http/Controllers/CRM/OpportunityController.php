<?php

namespace App\Http\Controllers\CRM;

use App\Enums\OpportunityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreOpportunityRequest;
use App\Http\Requests\CRM\UpdateOpportunityRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\User;
use App\Support\CRM\CrmOptions;
use App\Support\CRM\FormatsCrmData;
use App\Support\CRM\PipelineDefaults;
use App\Support\CRM\Timeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OpportunityController extends Controller
{
    public function index(Request $request): Response
    {
        PipelineDefaults::ensureDefaultPipeline();

        $filters = [
            'search' => $request->string('search')->toString(),
            'responsible_user_id' => $request->string('responsible_user_id')->toString(),
            'pipeline_stage_id' => $request->string('pipeline_stage_id')->toString(),
            'source' => $request->string('source')->toString(),
            'status' => $request->string('status')->toString(),
            'expected_close_from' => $request->string('expected_close_from')->toString(),
            'expected_close_to' => $request->string('expected_close_to')->toString(),
        ];

        $opportunities = Opportunity::query()
            ->visibleTo($request->user())
            ->with(['company:id,legal_name,trade_name,cnpj', 'stage:id,name,color,is_won,is_lost', 'responsibleUser:id,name'])
            ->search($filters['search'])
            ->when($filters['responsible_user_id'], fn ($query, string $value) => $query->where('responsible_user_id', $value))
            ->when($filters['pipeline_stage_id'], fn ($query, string $value) => $query->where('pipeline_stage_id', $value))
            ->when($filters['source'], fn ($query, string $value) => $query->where('source', $value))
            ->when($filters['status'], fn ($query, string $value) => $query->where('status', $value))
            ->when($filters['expected_close_from'], fn ($query, string $value) => $query->whereDate('expected_close_date', '>=', $value))
            ->when($filters['expected_close_to'], fn ($query, string $value) => $query->whereDate('expected_close_date', '<=', $value))
            ->latest('last_stage_changed_at')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Opportunity $opportunity): array => $this->opportunityListPayload($opportunity));

        return Inertia::render('Opportunities/Index', [
            'opportunities' => $opportunities,
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Opportunities/Form', [
            'mode' => 'create',
            'opportunity' => null,
            'selectedCompanyId' => $request->integer('company_id') ?: null,
            'options' => $this->options(),
        ]);
    }

    public function store(StoreOpportunityRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        abort_unless(Company::query()->visibleTo($request->user())->whereKey($validated['company_id'])->exists(), 403);

        if (! $request->user()->role?->canManage()) {
            $validated['responsible_user_id'] = $request->user()->id;
        }

        $stage = PipelineStage::query()->findOrFail($validated['pipeline_stage_id']);
        $opportunity = Opportunity::query()->create([
            ...$validated,
            'status' => $this->statusForStage($stage),
            'last_stage_changed_at' => now(),
        ]);

        Timeline::record(
            company: $opportunity->company,
            type: 'opportunity.created',
            title: 'Oportunidade criada',
            description: "A oportunidade {$opportunity->title} foi criada em {$stage->name}.",
            contact: $opportunity->contact,
            user: $request->user(),
            metadata: ['opportunity_id' => $opportunity->id],
        );

        return redirect()
            ->route('opportunities.index')
            ->with('success', 'Oportunidade cadastrada com sucesso.');
    }

    public function edit(Opportunity $opportunity): Response
    {
        $this->authorizeOpportunity($opportunity);

        return Inertia::render('Opportunities/Form', [
            'mode' => 'edit',
            'opportunity' => $this->opportunityFormPayload($opportunity),
            'selectedCompanyId' => null,
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $this->authorizeOpportunity($opportunity);
        $validated = $request->validated();
        abort_unless(Company::query()->visibleTo($request->user())->whereKey($validated['company_id'])->exists(), 403);

        if (! $request->user()->role?->canManage()) {
            $validated['responsible_user_id'] = $opportunity->responsible_user_id;
        }

        $oldStageId = $opportunity->pipeline_stage_id;
        $stage = PipelineStage::query()->findOrFail($validated['pipeline_stage_id']);

        $opportunity->update([
            ...$validated,
            'status' => $this->statusForStage($stage),
            'last_stage_changed_at' => $oldStageId === $stage->id ? $opportunity->last_stage_changed_at : now(),
        ]);

        Timeline::record(
            company: $opportunity->company,
            type: 'opportunity.updated',
            title: 'Oportunidade atualizada',
            description: "A oportunidade {$opportunity->title} foi atualizada.",
            contact: $opportunity->contact,
            user: $request->user(),
            metadata: ['opportunity_id' => $opportunity->id],
        );

        return redirect()
            ->route('opportunities.index')
            ->with('success', 'Oportunidade atualizada com sucesso.');
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $this->authorizeOpportunity($opportunity);

        $opportunity->delete();

        return back()->with('success', 'Oportunidade removida com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        $pipeline = PipelineDefaults::ensureDefaultPipeline();

        return [
            ...CrmOptions::all(),
            'stages' => $pipeline->stages->map(fn (PipelineStage $stage): array => [
                'value' => $stage->id,
                'label' => $stage->name,
                'color' => $stage->color,
                'is_won' => $stage->is_won,
                'is_lost' => $stage->is_lost,
            ]),
            'companies' => Company::query()
                ->visibleTo(request()->user())
                ->orderBy('trade_name')
                ->orderBy('legal_name')
                ->get(['id', 'legal_name', 'trade_name', 'cnpj'])
                ->map(fn (Company $company): array => [
                    'value' => $company->id,
                    'label' => $company->displayName(),
                    'description' => FormatsCrmData::cnpj($company->cnpj),
                ]),
            'contacts' => Contact::query()
                ->visibleTo(request()->user())
                ->with('company:id,legal_name,trade_name')
                ->orderBy('name')
                ->get(['id', 'company_id', 'name', 'email'])
                ->map(fn (Contact $contact): array => [
                    'value' => $contact->id,
                    'label' => $contact->name,
                    'description' => $contact->company?->displayName(),
                    'company_id' => $contact->company_id,
                ]),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                    'description' => $user->email,
                ]),
            'sources' => Opportunity::query()
                ->visibleTo(request()->user())
                ->whereNotNull('source')
                ->distinct()
                ->orderBy('source')
                ->pluck('source'),
        ];
    }

    private function statusForStage(PipelineStage $stage): OpportunityStatus
    {
        if ($stage->is_won) {
            return OpportunityStatus::Won;
        }

        if ($stage->is_lost) {
            return OpportunityStatus::Lost;
        }

        return OpportunityStatus::Open;
    }

    /**
     * @return array<string, mixed>
     */
    private function opportunityListPayload(Opportunity $opportunity): array
    {
        return [
            'id' => $opportunity->id,
            'title' => $opportunity->title,
            'estimated_value' => $opportunity->estimated_value,
            'formatted_estimated_value' => FormatsCrmData::money($opportunity->estimated_value),
            'probability' => $opportunity->probability,
            'expected_close_date' => $opportunity->expected_close_date?->toDateString(),
            'source' => $opportunity->source,
            'status' => [
                'value' => $opportunity->status->value,
                'label' => $opportunity->status->label(),
            ],
            'company' => [
                'id' => $opportunity->company->id,
                'display_name' => $opportunity->company->displayName(),
            ],
            'stage' => [
                'id' => $opportunity->stage->id,
                'name' => $opportunity->stage->name,
                'color' => $opportunity->stage->color,
                'is_won' => $opportunity->stage->is_won,
                'is_lost' => $opportunity->stage->is_lost,
            ],
            'responsible_user' => $opportunity->responsibleUser ? [
                'id' => $opportunity->responsibleUser->id,
                'name' => $opportunity->responsibleUser->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function opportunityFormPayload(Opportunity $opportunity): array
    {
        return [
            'id' => $opportunity->id,
            'company_id' => $opportunity->company_id,
            'contact_id' => $opportunity->contact_id,
            'responsible_user_id' => $opportunity->responsible_user_id,
            'pipeline_stage_id' => $opportunity->pipeline_stage_id,
            'title' => $opportunity->title,
            'estimated_value' => $opportunity->estimated_value,
            'probability' => $opportunity->probability,
            'expected_close_date' => $opportunity->expected_close_date?->toDateString(),
            'source' => $opportunity->source,
            'products_interests' => $opportunity->products_interests,
            'notes' => $opportunity->notes,
            'lost_reason' => $opportunity->lost_reason,
            'closed_value' => $opportunity->closed_value,
            'closed_at' => $opportunity->closed_at?->toDateString(),
        ];
    }

    private function authorizeOpportunity(Opportunity $opportunity): void
    {
        abort_unless(Opportunity::query()->visibleTo(auth()->user())->whereKey($opportunity->id)->exists(), 403);
    }
}
