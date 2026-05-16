<?php

namespace App\Console\Commands;

use App\Enums\ActivityStatus;
use App\Enums\AutomationTrigger;
use App\Enums\CompanyStatus;
use App\Enums\OpportunityStatus;
use App\Models\Activity;
use App\Models\CommercialAutomation;
use App\Models\Company;
use App\Models\Opportunity;
use App\Support\CRM\AutomationEngine;
use Illuminate\Console\Command;

class RunCommercialAutomationChecks extends Command
{
    protected $signature = 'crm:run-automation-checks';

    protected $description = 'Executa checks recorrentes de automacoes comerciais.';

    public function handle(AutomationEngine $engine): int
    {
        $this->runProposalNoResponse($engine);
        $this->runLeadNoInteraction($engine);
        $this->runTaskOverdue($engine);

        $this->components->info('Checks de automacoes comerciais executados.');

        return self::SUCCESS;
    }

    private function runProposalNoResponse(AutomationEngine $engine): void
    {
        $minDays = $this->minConditionDays(AutomationTrigger::ProposalNoResponse, 'days_without_response', 3);
        $stageSlug = $this->conditionValue(AutomationTrigger::ProposalNoResponse, 'stage_slug', 'proposta-enviada');

        Opportunity::query()
            ->with(['company.contacts', 'contact', 'responsibleUser', 'stage'])
            ->where('status', OpportunityStatus::Open)
            ->whereHas('stage', fn ($query) => $query->where('slug', $stageSlug))
            ->where('last_stage_changed_at', '<=', now()->subDays($minDays))
            ->chunkById(100, function ($opportunities) use ($engine): void {
                foreach ($opportunities as $opportunity) {
                    $engine->handle(AutomationTrigger::ProposalNoResponse, [
                        'company' => $opportunity->company,
                        'contact' => $opportunity->contact,
                        'opportunity' => $opportunity,
                        'user' => $opportunity->responsibleUser,
                        'stage' => $opportunity->stage,
                    ], 'proposal-no-response:'.$opportunity->id.':'.now()->toDateString());
                }
            });
    }

    private function runLeadNoInteraction(AutomationEngine $engine): void
    {
        $minDays = $this->minConditionDays(AutomationTrigger::LeadNoInteraction, 'days_without_interaction', 7);

        Company::query()
            ->with(['responsibleUser', 'contacts'])
            ->whereIn('status', [CompanyStatus::NewLead, CompanyStatus::Prospecting])
            ->where(function ($query) use ($minDays): void {
                $query
                    ->whereNull('last_interaction_at')
                    ->orWhere('last_interaction_at', '<=', now()->subDays($minDays));
            })
            ->chunkById(100, function ($companies) use ($engine): void {
                foreach ($companies as $company) {
                    $engine->handle(AutomationTrigger::LeadNoInteraction, [
                        'company' => $company,
                        'contact' => $company->contacts->first(),
                        'user' => $company->responsibleUser,
                    ], 'lead-no-interaction:'.$company->id.':'.now()->toDateString());
                }
            });
    }

    private function runTaskOverdue(AutomationEngine $engine): void
    {
        Activity::query()
            ->with(['company.contacts', 'contact', 'opportunity', 'assignedTo'])
            ->where('status', ActivityStatus::Pending)
            ->where('due_at', '<', now())
            ->chunkById(100, function ($activities) use ($engine): void {
                foreach ($activities as $activity) {
                    $engine->handle(AutomationTrigger::TaskOverdue, [
                        'company' => $activity->company,
                        'contact' => $activity->contact,
                        'opportunity' => $activity->opportunity,
                        'activity' => $activity,
                        'user' => $activity->assignedTo,
                    ], 'task-overdue:'.$activity->id.':'.now()->toDateString());
                }
            });
    }

    private function minConditionDays(AutomationTrigger $trigger, string $key, int $default): int
    {
        return (int) CommercialAutomation::query()
            ->where('trigger', $trigger->value)
            ->where('is_active', true)
            ->get()
            ->map(fn (CommercialAutomation $automation): int => (int) data_get($automation->conditions, $key, $default))
            ->filter(fn (int $value): bool => $value > 0)
            ->min() ?: $default;
    }

    private function conditionValue(AutomationTrigger $trigger, string $key, string $default): string
    {
        return (string) CommercialAutomation::query()
            ->where('trigger', $trigger->value)
            ->where('is_active', true)
            ->get()
            ->map(fn (CommercialAutomation $automation): ?string => data_get($automation->conditions, $key))
            ->filter()
            ->first() ?: $default;
    }
}
