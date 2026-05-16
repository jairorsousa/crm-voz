<?php

namespace Tests\Feature\CRM;

use App\Enums\ActivityStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Company;
use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_activity_and_register_timeline_event(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'responsible_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('activities.store'), [
                'company_id' => $company->id,
                'contact_id' => null,
                'opportunity_id' => null,
                'assigned_to_user_id' => $user->id,
                'type' => 'follow_up',
                'priority' => 'high',
                'title' => 'Retomar conversa com decisor',
                'description' => 'Enviar resumo da reunião.',
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]);

        $response->assertRedirect(route('activities.index'));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'assigned_to_user_id' => $user->id,
            'type' => 'follow_up',
            'status' => ActivityStatus::Pending->value,
            'title' => 'Retomar conversa com decisor',
        ]);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $company->id,
            'type' => 'activity.created',
        ]);
    }

    public function test_activity_can_be_completed_rescheduled_and_canceled(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'assigned_to_user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'status' => ActivityStatus::Pending,
            'due_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->patch(route('activities.complete', $activity))->assertRedirect();
        $activity->refresh();
        $this->assertSame(ActivityStatus::Completed, $activity->status);
        $this->assertNotNull($activity->completed_at);

        $this->actingAs($user)->patch(route('activities.reschedule', $activity), [
            'due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect();
        $activity->refresh();
        $this->assertSame(ActivityStatus::Pending, $activity->status);
        $this->assertNull($activity->completed_at);

        $this->actingAs($user)->patch(route('activities.cancel', $activity))->assertRedirect();
        $activity->refresh();
        $this->assertSame(ActivityStatus::Canceled, $activity->status);
        $this->assertNotNull($activity->canceled_at);

        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $activity->company_id,
            'type' => 'activity.completed',
        ]);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $activity->company_id,
            'type' => 'activity.rescheduled',
        ]);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $activity->company_id,
            'type' => 'activity.canceled',
        ]);
    }

    public function test_sdr_only_sees_own_activities(): void
    {
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);
        $other = User::factory()->create(['role' => UserRole::Closer]);

        Activity::factory()->create([
            'title' => 'Minha tarefa visível',
            'assigned_to_user_id' => $sdr->id,
            'created_by_user_id' => $other->id,
        ]);
        Activity::factory()->create([
            'title' => 'Tarefa de outra pessoa',
            'assigned_to_user_id' => $other->id,
            'created_by_user_id' => $other->id,
        ]);

        $response = $this->actingAs($sdr)->get(route('activities.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activities/Index')
            ->has('activities.data', 1)
            ->where('activities.data.0.title', 'Minha tarefa visível'));
    }

    public function test_manager_sees_all_activities(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        $other = User::factory()->create(['role' => UserRole::Sdr]);

        Activity::factory()->create([
            'title' => 'Atividade do time',
            'assigned_to_user_id' => $other->id,
            'created_by_user_id' => $other->id,
        ]);

        $response = $this->actingAs($manager)->get(route('activities.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activities/Index')
            ->has('activities.data', 1)
            ->where('activities.data.0.title', 'Atividade do time'));
    }

    public function test_company_timeline_is_paginated_and_filterable(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'responsible_user_id' => $user->id,
        ]);

        TimelineEvent::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'activity.created',
            'title' => 'Follow-up criado',
            'description' => 'Atividade importante para o decisor.',
            'occurred_at' => now(),
        ]);
        TimelineEvent::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'company.updated',
            'title' => 'Empresa atualizada',
            'description' => 'Outro evento.',
            'occurred_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->get(route('companies.timeline', [
            'company' => $company,
            'type' => 'activity.created',
            'search' => 'decisor',
        ]));

        $response->assertOk();
        $response->assertSee('Follow-up criado');
        $response->assertDontSee('Outro evento.');
    }
}
