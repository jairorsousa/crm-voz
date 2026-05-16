<?php

namespace App\Http\Controllers\CRM;

use App\Enums\AutomationActionType;
use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationTrigger;
use App\Http\Controllers\Controller;
use App\Models\AutomationExecution;
use App\Models\CommercialAutomation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class CommercialAutomationController extends Controller
{
    public function index(): Response
    {
        $automations = CommercialAutomation::query()
            ->with('latestExecution')
            ->withCount('executions')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (CommercialAutomation $automation): array => $this->automationPayload($automation));

        $executions = AutomationExecution::query()
            ->with(['automation:id,name', 'company:id,legal_name,trade_name', 'user:id,name'])
            ->latest('executed_at')
            ->limit(20)
            ->get()
            ->map(fn (AutomationExecution $execution): array => $this->executionPayload($execution));

        return Inertia::render('Automations/Index', [
            'automations' => $automations,
            'executions' => $executions,
            'options' => [
                'triggers' => $this->enumOptions(AutomationTrigger::cases()),
                'actions' => $this->enumOptions(AutomationActionType::cases()),
                'statuses' => $this->enumOptions(AutomationExecutionStatus::cases()),
            ],
        ]);
    }

    public function toggle(CommercialAutomation $automation): RedirectResponse
    {
        $automation->update([
            'is_active' => ! $automation->is_active,
        ]);

        return back()->with('success', $automation->is_active ? 'Automação ativada.' : 'Automação pausada.');
    }

    public function runChecks(): RedirectResponse
    {
        Artisan::call('crm:run-automation-checks');

        return back()->with('success', 'Checks de automação executados.');
    }

    /**
     * @return array<string, mixed>
     */
    private function automationPayload(CommercialAutomation $automation): array
    {
        return [
            'id' => $automation->id,
            'name' => $automation->name,
            'description' => $automation->description,
            'trigger' => [
                'value' => $automation->trigger->value,
                'label' => $automation->trigger->label(),
            ],
            'conditions' => $automation->conditions ?? [],
            'actions' => collect($automation->actions ?? [])->map(fn (array $action): array => [
                ...$action,
                'label' => AutomationActionType::from($action['type'])->label(),
            ])->all(),
            'is_active' => $automation->is_active,
            'executions_count' => $automation->executions_count,
            'latest_execution' => $automation->latestExecution ? [
                'id' => $automation->latestExecution->id,
                'status' => [
                    'value' => $automation->latestExecution->status->value,
                    'label' => $automation->latestExecution->status->label(),
                ],
                'executed_at' => $automation->latestExecution->executed_at?->toISOString(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function executionPayload(AutomationExecution $execution): array
    {
        return [
            'id' => $execution->id,
            'automation_name' => $execution->automation->name,
            'trigger' => [
                'value' => $execution->trigger->value,
                'label' => $execution->trigger->label(),
            ],
            'status' => [
                'value' => $execution->status->value,
                'label' => $execution->status->label(),
            ],
            'error_message' => $execution->error_message,
            'executed_at' => $execution->executed_at?->toISOString(),
            'company' => $execution->company ? [
                'id' => $execution->company->id,
                'display_name' => $execution->company->displayName(),
            ] : null,
            'user' => $execution->user ? [
                'id' => $execution->user->id,
                'name' => $execution->user->name,
            ] : null,
        ];
    }

    /**
     * @param  array<int, object>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(array $cases): array
    {
        return array_map(fn (object $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
        ], $cases);
    }
}
