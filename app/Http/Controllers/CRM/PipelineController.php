<?php

namespace App\Http\Controllers\CRM;

use App\Enums\AutomationTrigger;
use App\Enums\OpportunityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\MoveOpportunityRequest;
use App\Models\Opportunity;
use App\Models\OpportunityStageMovement;
use App\Models\PipelineStage;
use App\Models\User;
use App\Support\CRM\AutomationEngine;
use App\Support\CRM\CrmOptions;
use App\Support\CRM\FormatsCrmData;
use App\Support\CRM\PipelineDefaults;
use App\Support\CRM\Timeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function index(Request $request): Response
    {
        $pipeline = PipelineDefaults::ensureDefaultPipeline();

        $filters = [
            'responsible_user_id' => $request->string('responsible_user_id')->toString(),
            'pipeline_stage_id' => $request->string('pipeline_stage_id')->toString(),
            'source' => $request->string('source')->toString(),
            'lead_temperature' => $request->string('lead_temperature')->toString(),
            'expected_close_from' => $request->string('expected_close_from')->toString(),
            'expected_close_to' => $request->string('expected_close_to')->toString(),
        ];

        $stages = $pipeline->stages()
            ->get()
            ->map(function (PipelineStage $stage) use ($filters, $request): array {
                $baseQuery = Opportunity::query()
                    ->visibleTo($request->user())
                    ->with(['company:id,legal_name,trade_name,lead_temperature', 'responsibleUser:id,name'])
                    ->where('pipeline_stage_id', $stage->id)
                    ->when($filters['responsible_user_id'], fn ($query, string $value) => $query->where('responsible_user_id', $value))
                    ->when($filters['pipeline_stage_id'], fn ($query, string $value) => $query->where('pipeline_stage_id', $value))
                    ->when($filters['source'], fn ($query, string $value) => $query->where('source', $value))
                    ->when($filters['lead_temperature'], fn ($query, string $value) => $query->whereHas('company', fn ($query) => $query->where('lead_temperature', $value)))
                    ->when($filters['expected_close_from'], fn ($query, string $value) => $query->whereDate('expected_close_date', '>=', $value))
                    ->when($filters['expected_close_to'], fn ($query, string $value) => $query->whereDate('expected_close_date', '<=', $value));

                $summary = (clone $baseQuery)
                    ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(estimated_value), 0) as total_value')
                    ->first();

                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'slug' => $stage->slug,
                    'position' => $stage->position,
                    'color' => $stage->color,
                    'is_won' => $stage->is_won,
                    'is_lost' => $stage->is_lost,
                    'total_count' => (int) $summary->total_count,
                    'total_value' => (float) $summary->total_value,
                    'formatted_total_value' => FormatsCrmData::money($summary->total_value),
                    'opportunities' => (clone $baseQuery)
                        ->latest('last_stage_changed_at')
                        ->latest()
                        ->limit(30)
                        ->get()
                        ->map(fn (Opportunity $opportunity): array => $this->kanbanCardPayload($opportunity)),
                ];
            });

        return Inertia::render('Pipeline/Index', [
            'pipeline' => [
                'id' => $pipeline->id,
                'name' => $pipeline->name,
            ],
            'stages' => $stages,
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function move(MoveOpportunityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        abort_unless(Opportunity::query()->visibleTo($request->user())->whereKey($opportunity->id)->exists(), 403);

        $fromStage = $opportunity->stage;
        $toStage = PipelineStage::query()->findOrFail($request->validated()['pipeline_stage_id']);

        if ($fromStage->id === $toStage->id) {
            return back();
        }

        $opportunity->update([
            'pipeline_id' => $toStage->pipeline_id,
            'pipeline_stage_id' => $toStage->id,
            'status' => $this->statusForStage($toStage),
            'lost_reason' => $toStage->is_lost ? $request->validated('lost_reason') : null,
            'closed_value' => $toStage->is_won ? $request->validated('closed_value') : null,
            'closed_at' => $toStage->is_won ? $request->validated('closed_at') : null,
            'last_stage_changed_at' => now(),
        ]);

        $movement = OpportunityStageMovement::query()->create([
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $fromStage->id,
            'to_stage_id' => $toStage->id,
            'user_id' => $request->user()?->id,
            'notes' => $request->validated('movement_notes'),
            'moved_at' => now(),
        ]);

        Timeline::record(
            company: $opportunity->company,
            type: 'opportunity.stage_changed',
            title: 'Oportunidade movimentada',
            description: "{$opportunity->title} saiu de {$fromStage->name} para {$toStage->name}.",
            contact: $opportunity->contact,
            user: $request->user(),
            metadata: [
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $fromStage->id,
                'to_stage_id' => $toStage->id,
            ],
        );

        app(AutomationEngine::class)->handle(AutomationTrigger::OpportunityStageChanged, [
            'company' => $opportunity->company,
            'contact' => $opportunity->contact,
            'opportunity' => $opportunity->refresh()->load(['company.contacts', 'contact', 'responsibleUser', 'stage']),
            'user' => $request->user(),
            'stage' => $toStage,
        ], 'stage-movement:'.$movement->id);

        return back()->with('success', 'Oportunidade movimentada com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        $pipeline = PipelineDefaults::ensureDefaultPipeline();

        return [
            'stages' => $pipeline->stages->map(fn (PipelineStage $stage): array => [
                'value' => $stage->id,
                'label' => $stage->name,
                'is_won' => $stage->is_won,
                'is_lost' => $stage->is_lost,
            ]),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                    'description' => $user->email,
                ]),
            'leadTemperatures' => CrmOptions::all()['leadTemperatures'],
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
    private function kanbanCardPayload(Opportunity $opportunity): array
    {
        return [
            'id' => $opportunity->id,
            'title' => $opportunity->title,
            'formatted_estimated_value' => FormatsCrmData::money($opportunity->estimated_value),
            'probability' => $opportunity->probability,
            'expected_close_date' => $opportunity->expected_close_date?->toDateString(),
            'source' => $opportunity->source,
            'company' => [
                'id' => $opportunity->company->id,
                'display_name' => $opportunity->company->displayName(),
                'lead_temperature' => [
                    'value' => $opportunity->company->lead_temperature->value,
                    'label' => $opportunity->company->lead_temperature->label(),
                ],
            ],
            'responsible_user' => $opportunity->responsibleUser ? [
                'id' => $opportunity->responsibleUser->id,
                'name' => $opportunity->responsibleUser->name,
            ] : null,
        ];
    }
}
