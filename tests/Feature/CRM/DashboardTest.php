<?php

namespace Tests\Feature\CRM;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\CompanyStatus;
use App\Enums\OpportunityStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use App\Support\CRM\PipelineDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_manager_sees_team_dashboard_metrics(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);
        $closer = User::factory()->create(['role' => UserRole::Closer]);
        $pipeline = PipelineDefaults::ensureDefaultPipeline();
        $firstStage = $pipeline->stages->first();
        $wonStage = $pipeline->stages->firstWhere('is_won', true);
        $lostStage = $pipeline->stages->firstWhere('is_lost', true);

        $firstCompany = Company::factory()->create([
            'responsible_user_id' => $sdr->id,
            'status' => CompanyStatus::NewLead,
            'total_default_amount' => 100000,
            'average_collection_ticket' => 1000,
            'overdue_customers_count' => 10,
        ]);
        $secondCompany = Company::factory()->create([
            'responsible_user_id' => $closer->id,
            'status' => CompanyStatus::Negotiation,
            'total_default_amount' => 200000,
            'average_collection_ticket' => 2000,
            'overdue_customers_count' => 20,
        ]);

        Opportunity::factory()->create([
            'company_id' => $firstCompany->id,
            'responsible_user_id' => $sdr->id,
            'pipeline_stage_id' => $firstStage->id,
            'status' => OpportunityStatus::Open,
            'estimated_value' => 50000,
            'last_stage_changed_at' => now()->subDays(9),
        ]);
        Opportunity::factory()->create([
            'company_id' => $secondCompany->id,
            'responsible_user_id' => $closer->id,
            'pipeline_stage_id' => $wonStage->id,
            'status' => OpportunityStatus::Won,
            'estimated_value' => 30000,
            'closed_value' => 25000,
            'last_stage_changed_at' => now(),
        ]);
        Opportunity::factory()->create([
            'company_id' => $secondCompany->id,
            'responsible_user_id' => $closer->id,
            'pipeline_stage_id' => $lostStage->id,
            'status' => OpportunityStatus::Lost,
            'estimated_value' => 10000,
            'last_stage_changed_at' => now(),
        ]);

        Activity::factory()->create([
            'company_id' => $firstCompany->id,
            'assigned_to_user_id' => $sdr->id,
            'created_by_user_id' => $manager->id,
            'type' => ActivityType::Meeting,
            'status' => ActivityStatus::Completed,
            'due_at' => now()->subHour(),
        ]);
        Activity::factory()->create([
            'company_id' => $secondCompany->id,
            'assigned_to_user_id' => $closer->id,
            'created_by_user_id' => $manager->id,
            'status' => ActivityStatus::Pending,
            'due_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('profile.role', UserRole::CommercialManager->value)
            ->where('cards.0.value', '2')
            ->where('cards.1.value', '1')
            ->where('cards.2.value', '1')
            ->where('cards.3.value', '1 / 1')
            ->where('portfolio.total_default_amount', 'R$ 300.000,00')
            ->where('portfolio.overdue_customers_count', 30)
            ->has('pipeline', 9)
            ->has('productivity', 3)
            ->has('stalledOpportunities', 1)
            ->has('cache.generated_at'));
    }

    public function test_sdr_dashboard_is_scoped_to_own_responsibilities(): void
    {
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);
        $other = User::factory()->create(['role' => UserRole::Closer]);
        $pipeline = PipelineDefaults::ensureDefaultPipeline();
        $firstStage = $pipeline->stages->first();

        $ownCompany = Company::factory()->create([
            'responsible_user_id' => $sdr->id,
            'status' => CompanyStatus::NewLead,
            'total_default_amount' => 100000,
        ]);
        $otherCompany = Company::factory()->create([
            'responsible_user_id' => $other->id,
            'status' => CompanyStatus::NewLead,
            'total_default_amount' => 900000,
        ]);

        Opportunity::factory()->create([
            'company_id' => $ownCompany->id,
            'responsible_user_id' => $sdr->id,
            'pipeline_stage_id' => $firstStage->id,
            'status' => OpportunityStatus::Open,
            'estimated_value' => 50000,
        ]);
        Opportunity::factory()->create([
            'company_id' => $otherCompany->id,
            'responsible_user_id' => $other->id,
            'pipeline_stage_id' => $firstStage->id,
            'status' => OpportunityStatus::Open,
            'estimated_value' => 900000,
        ]);

        Activity::factory()->today()->create([
            'company_id' => $ownCompany->id,
            'assigned_to_user_id' => $sdr->id,
            'created_by_user_id' => $other->id,
        ]);
        Activity::factory()->today()->create([
            'company_id' => $otherCompany->id,
            'assigned_to_user_id' => $other->id,
            'created_by_user_id' => $other->id,
        ]);

        $response = $this->actingAs($sdr)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('profile.role', UserRole::Sdr->value)
            ->where('cards.0.value', '1')
            ->where('cards.1.value', '1')
            ->where('cards.2.value', '1')
            ->where('portfolio.total_default_amount', 'R$ 100.000,00')
            ->has('todayActivities', 1)
            ->where('todayActivities.0.company.display_name', $ownCompany->displayName())
            ->has('productivity', 1));
    }
}
