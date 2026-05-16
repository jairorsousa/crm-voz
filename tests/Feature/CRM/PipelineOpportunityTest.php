<?php

namespace Tests\Feature\CRM;

use App\Enums\OpportunityStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\User;
use App\Support\CRM\PipelineDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineOpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_pipeline_stages_are_created(): void
    {
        $pipeline = PipelineDefaults::ensureDefaultPipeline();

        $this->assertCount(9, $pipeline->stages);
        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Fechado ganho',
            'is_won' => true,
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Fechado perdido',
            'is_lost' => true,
        ]);
    }

    public function test_user_can_create_opportunity_for_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'responsible_user_id' => $user->id,
        ]);
        $contact = Contact::factory()->for($company)->create();
        $stage = $this->stage('Lead novo');

        $response = $this
            ->actingAs($user)
            ->post(route('opportunities.store'), [
                'company_id' => $company->id,
                'contact_id' => $contact->id,
                'responsible_user_id' => $user->id,
                'pipeline_stage_id' => $stage->id,
                'title' => 'Projeto cobrança VOZ',
                'estimated_value' => '25000.00',
                'probability' => 35,
                'expected_close_date' => '2026-07-10',
                'source' => 'Outbound',
                'products_interests' => 'Régua de cobrança',
                'notes' => 'Primeira oportunidade.',
            ]);

        $response->assertRedirect(route('opportunities.index'));

        $this->assertDatabaseHas('opportunities', [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'pipeline_stage_id' => $stage->id,
            'status' => OpportunityStatus::Open->value,
            'title' => 'Projeto cobrança VOZ',
        ]);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $company->id,
            'type' => 'opportunity.created',
        ]);
    }

    public function test_moving_opportunity_registers_movement_and_timeline(): void
    {
        $user = User::factory()->create();
        $fromStage = $this->stage('Lead novo');
        $toStage = $this->stage('Qualificação');
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $fromStage->pipeline_id,
            'pipeline_stage_id' => $fromStage->id,
            'responsible_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('pipeline.move', $opportunity), [
                'pipeline_stage_id' => $toStage->id,
                'movement_notes' => 'Lead qualificado.',
            ]);

        $response->assertRedirect();

        $opportunity->refresh();
        $this->assertSame($toStage->id, $opportunity->pipeline_stage_id);
        $this->assertSame(OpportunityStatus::Open, $opportunity->status);
        $this->assertDatabaseHas('opportunity_stage_movements', [
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $fromStage->id,
            'to_stage_id' => $toStage->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $opportunity->company_id,
            'type' => 'opportunity.stage_changed',
        ]);
    }

    public function test_lost_stage_requires_lost_reason(): void
    {
        $user = User::factory()->create();
        $fromStage = $this->stage('Negociação');
        $lostStage = $this->stage('Fechado perdido');
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $fromStage->pipeline_id,
            'pipeline_stage_id' => $fromStage->id,
            'responsible_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('pipeline.index'))
            ->patch(route('pipeline.move', $opportunity), [
                'pipeline_stage_id' => $lostStage->id,
            ]);

        $response->assertRedirect(route('pipeline.index'));
        $response->assertSessionHasErrors('lost_reason');
    }

    public function test_won_stage_requires_and_saves_closing_data(): void
    {
        $user = User::factory()->create();
        $fromStage = $this->stage('Negociação');
        $wonStage = $this->stage('Fechado ganho');
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $fromStage->pipeline_id,
            'pipeline_stage_id' => $fromStage->id,
            'responsible_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('pipeline.move', $opportunity), [
                'pipeline_stage_id' => $wonStage->id,
                'closed_value' => '42000.00',
                'closed_at' => '2026-08-01',
            ]);

        $response->assertRedirect();

        $opportunity->refresh();
        $this->assertSame($wonStage->id, $opportunity->pipeline_stage_id);
        $this->assertSame(OpportunityStatus::Won, $opportunity->status);
        $this->assertSame('42000.00', $opportunity->closed_value);
        $this->assertSame('2026-08-01', $opportunity->closed_at->toDateString());
    }

    private function stage(string $name): PipelineStage
    {
        $pipeline = PipelineDefaults::ensureDefaultPipeline();

        return $pipeline->stages->firstWhere('name', $name);
    }
}
