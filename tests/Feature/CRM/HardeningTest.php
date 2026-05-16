<?php

namespace Tests\Feature\CRM;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CrmSetting;
use App\Models\Opportunity;
use App\Models\User;
use App\Support\CRM\IntegrationSettings;
use App\Support\CRM\PipelineDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_credentials_are_encrypted_masked_and_audited(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->patch(route('settings.integrations.update', 'twilio'), [
            'account_sid' => 'AC123',
            'auth_token' => 'super-secret-token',
            'from_number' => '+551130000000',
            'voice_webhook_url' => 'https://voz.example.com/webhook/twilio',
            'webhook_token' => 'webhook-secret',
        ])->assertRedirect();

        $rawValue = DB::table('crm_settings')->where('key', 'integrations.twilio')->value('value');

        $this->assertStringNotContainsString('super-secret-token', (string) $rawValue);
        $this->assertStringNotContainsString('webhook-secret', (string) $rawValue);
        $this->assertSame('super-secret-token', IntegrationSettings::twilio()['auth_token']);
        $this->assertSame('webhook-secret', IntegrationSettings::twilio()['webhook_token']);

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.integrations.twilio.auth_token', null)
                ->where('settings.integrations.twilio.webhook_token', null));

        $audit = AuditLog::query()->where('event', 'settings.integration.updated')->firstOrFail();

        $this->assertSame('[redacted]', $audit->new_values['auth_token']);
        $this->assertSame('[redacted]', $audit->new_values['webhook_token']);
    }

    public function test_webhook_token_is_required_when_configured(): void
    {
        CrmSetting::putValue('integrations', 'integrations.evolution', 'Evolution', [
            'url' => 'https://evolution.example.com',
            'key' => 'api-secret',
            'instance' => 'voz',
            'webhook_token' => 'expected-token',
        ]);

        $payload = [
            'event' => 'messages.upsert',
            'data' => ['key' => ['id' => 'WA123', 'fromMe' => true]],
        ];

        $this->postJson(route('webhooks.evolution.whatsapp'), $payload)->assertForbidden();

        $this
            ->withHeader('X-VOZ-Webhook-Token', 'expected-token')
            ->postJson(route('webhooks.evolution.whatsapp'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_non_manager_cannot_access_other_records_by_direct_url(): void
    {
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);
        $other = User::factory()->create(['role' => UserRole::Closer]);
        $pipeline = PipelineDefaults::ensureDefaultPipeline();
        $stage = $pipeline->stages->first();
        $anotherStage = $pipeline->stages->skip(1)->first() ?: $stage;

        $otherCompany = Company::factory()->create(['responsible_user_id' => $other->id]);
        $otherContact = Contact::factory()->for($otherCompany)->create();
        $otherOpportunity = Opportunity::factory()->create([
            'company_id' => $otherCompany->id,
            'contact_id' => $otherContact->id,
            'responsible_user_id' => $other->id,
            'pipeline_stage_id' => $stage->id,
        ]);

        $this->actingAs($sdr)->get(route('companies.show', $otherCompany))->assertForbidden();
        $this->actingAs($sdr)->get(route('contacts.edit', $otherContact))->assertForbidden();
        $this->actingAs($sdr)->get(route('opportunities.edit', $otherOpportunity))->assertForbidden();

        $this->actingAs($sdr)->patch(route('pipeline.move', $otherOpportunity), [
            'pipeline_stage_id' => $anotherStage->id,
        ])->assertForbidden();
    }

    public function test_sensitive_routes_have_rate_limits(): void
    {
        $this->assertContains('throttle:communications', Route::getRoutes()->getByName('calls.store')->gatherMiddleware());
        $this->assertContains('throttle:settings', Route::getRoutes()->getByName('settings.integrations.update')->gatherMiddleware());
        $this->assertContains('throttle:reports', Route::getRoutes()->getByName('reports.export')->gatherMiddleware());
        $this->assertContains('throttle:webhooks', Route::getRoutes()->getByName('webhooks.evolution.whatsapp')->gatherMiddleware());
    }
}
