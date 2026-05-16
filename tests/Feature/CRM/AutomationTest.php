<?php

namespace Tests\Feature\CRM;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\AutomationTrigger;
use App\Enums\CompanyStatus;
use App\Enums\OpportunityStatus;
use App\Enums\PriorityLevel;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\CommercialAutomation;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use App\Support\CRM\AutomationEngine;
use App\Support\CRM\PipelineDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_manager_can_view_and_toggle_automations(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        $automation = CommercialAutomation::factory()->create([
            'name' => 'Criar tarefa no funil',
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->get(route('automations.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Automations/Index')
            ->has('automations', 1)
            ->where('automations.0.name', 'Criar tarefa no funil')
            ->where('automations.0.is_active', true));

        $this->actingAs($manager)->patch(route('automations.toggle', $automation))->assertRedirect();

        $this->assertDatabaseHas('commercial_automations', [
            'id' => $automation->id,
            'is_active' => false,
        ]);
    }

    public function test_opportunity_stage_change_runs_automation(): void
    {
        $user = User::factory()->create(['role' => UserRole::CommercialManager]);
        $pipeline = PipelineDefaults::ensureDefaultPipeline();
        $fromStage = $pipeline->stages->firstWhere('slug', 'lead-novo');
        $proposalStage = $pipeline->stages->firstWhere('slug', 'proposta-enviada');
        $company = Company::factory()->create(['responsible_user_id' => $user->id]);
        $contact = Contact::factory()->for($company)->create();
        $opportunity = Opportunity::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'responsible_user_id' => $user->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $fromStage->id,
            'status' => OpportunityStatus::Open,
        ]);

        CommercialAutomation::factory()->create([
            'trigger' => AutomationTrigger::OpportunityStageChanged,
            'conditions' => ['to_stage_slug' => 'proposta-enviada'],
            'actions' => [
                [
                    'type' => 'create_activity',
                    'activity_type' => 'follow_up',
                    'priority' => 'high',
                    'assigned_to' => 'responsible',
                    'title' => 'Follow-up automático {{empresa}}',
                    'description' => 'Retomar {{oportunidade}}.',
                    'due_in_days' => 1,
                ],
            ],
        ]);

        $this->actingAs($user)->patch(route('pipeline.move', $opportunity), [
            'pipeline_stage_id' => $proposalStage->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'opportunity_id' => $opportunity->id,
            'assigned_to_user_id' => $user->id,
            'type' => ActivityType::FollowUp->value,
            'priority' => PriorityLevel::High->value,
            'title' => 'Follow-up automático '.$company->displayName(),
        ]);
        $this->assertDatabaseHas('automation_executions', [
            'company_id' => $company->id,
            'opportunity_id' => $opportunity->id,
            'trigger' => AutomationTrigger::OpportunityStageChanged->value,
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $company->id,
            'type' => 'automation.executed',
        ]);
    }

    public function test_automation_engine_is_idempotent_for_same_event(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['responsible_user_id' => $user->id]);
        $automation = CommercialAutomation::factory()->create([
            'trigger' => AutomationTrigger::LeadNoInteraction,
            'conditions' => [],
            'actions' => [
                [
                    'type' => 'create_activity',
                    'activity_type' => 'task',
                    'priority' => 'medium',
                    'assigned_to' => 'responsible',
                    'title' => 'Retomar lead {{empresa}}',
                    'description' => 'Sem interação.',
                    'due_in_days' => 0,
                ],
            ],
        ]);

        $engine = app(AutomationEngine::class);
        $context = [
            'company' => $company,
            'user' => $user,
        ];

        $engine->handle(AutomationTrigger::LeadNoInteraction, $context, 'same-event-key');
        $engine->handle(AutomationTrigger::LeadNoInteraction, $context, 'same-event-key');

        $this->assertSame(1, $automation->executions()->count());
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_recurring_checks_notify_overdue_task_once_per_day(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['responsible_user_id' => $user->id]);
        Activity::factory()->create([
            'company_id' => $company->id,
            'assigned_to_user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'status' => ActivityStatus::Pending,
            'due_at' => now()->subDay(),
        ]);
        CommercialAutomation::factory()->create([
            'trigger' => AutomationTrigger::TaskOverdue,
            'conditions' => [],
            'actions' => [
                [
                    'type' => 'notify_user',
                    'recipient' => 'activity_assignee',
                    'title' => 'Tarefa vencida',
                    'body' => 'Existe uma tarefa vencida em {{empresa}}.',
                ],
            ],
        ]);

        Artisan::call('crm:run-automation-checks');
        Artisan::call('crm:run-automation-checks');

        $this->assertDatabaseCount('automation_executions', 1);
        $this->assertDatabaseCount('internal_notifications', 1);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $company->id,
            'type' => 'automation.executed',
        ]);
    }

    public function test_recurring_checks_create_task_for_lead_without_interaction(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'responsible_user_id' => $user->id,
            'status' => CompanyStatus::NewLead,
            'last_interaction_at' => now()->subDays(10),
        ]);
        CommercialAutomation::factory()->create([
            'trigger' => AutomationTrigger::LeadNoInteraction,
            'conditions' => ['days_without_interaction' => 7, 'company_statuses' => ['new_lead']],
            'actions' => [
                [
                    'type' => 'create_activity',
                    'activity_type' => 'task',
                    'priority' => 'high',
                    'assigned_to' => 'responsible',
                    'title' => 'Retomar lead {{empresa}}',
                    'description' => 'Lead parado.',
                    'due_in_days' => 0,
                ],
            ],
        ]);

        Artisan::call('crm:run-automation-checks');

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Retomar lead '.$company->displayName(),
        ]);
    }
}
