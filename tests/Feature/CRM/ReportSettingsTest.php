<?php

namespace Tests\Feature\CRM;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationStatus;
use App\Enums\CompanyStatus;
use App\Enums\OpportunityStatus;
use App\Enums\ReportExportStatus;
use App\Enums\UserRole;
use App\Models\CommunicationMessage;
use App\Models\Company;
use App\Models\CrmSetting;
use App\Models\Opportunity;
use App\Models\ReportExport;
use App\Models\User;
use App\Support\CRM\PipelineDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_filtered_reports(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        $closer = User::factory()->create(['role' => UserRole::Closer]);
        $pipeline = PipelineDefaults::ensureDefaultPipeline();
        $stage = $pipeline->stages->first();

        $company = Company::factory()->create([
            'responsible_user_id' => $closer->id,
            'segment' => 'Varejo',
            'lead_source' => 'Site',
            'status' => CompanyStatus::Negotiation,
            'total_default_amount' => 120000,
        ]);

        Opportunity::factory()->create([
            'company_id' => $company->id,
            'responsible_user_id' => $closer->id,
            'pipeline_stage_id' => $stage->id,
            'source' => 'Site',
            'status' => OpportunityStatus::Open,
            'estimated_value' => 35000,
            'created_at' => '2026-05-10 10:00:00',
        ]);

        CommunicationMessage::factory()->create([
            'company_id' => $company->id,
            'user_id' => $closer->id,
            'channel' => CommunicationChannel::Email,
            'status' => CommunicationStatus::Sent,
            'created_at' => '2026-05-11 10:00:00',
        ]);

        $response = $this->actingAs($manager)->get(route('reports.index', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'segment' => 'Varejo',
            'source' => 'Site',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->where('filters.segment', 'Varejo')
            ->where('overview.0.value', '1')
            ->where('overview.1.value', '1')
            ->has('previews', 11)
            ->where('previews.0.key', 'companies')
            ->where('previews.0.rows.0.empresa', $company->displayName())
            ->has('options.users')
            ->has('exports'));
    }

    public function test_report_exports_can_be_downloaded_immediately_or_generated_in_queue(): void
    {
        Storage::fake();

        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        Company::factory()->create([
            'segment' => 'Serviços',
            'lead_source' => 'Indicação',
        ]);

        $response = $this->actingAs($manager)->get(route('reports.export', [
            'report' => 'companies',
            'format' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = file_get_contents($response->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('Empresa;Segmento;Origem;Status', $content);

        $this->actingAs($manager)->post(route('reports.exports.queue', [
            'report' => 'companies',
            'format' => 'excel',
        ]))->assertRedirect();

        $export = ReportExport::query()->latest()->firstOrFail();

        $this->assertSame(ReportExportStatus::Completed, $export->status);
        $this->assertNotNull($export->file_path);
        Storage::assertExists($export->file_path);
    }

    public function test_admin_can_update_settings_that_change_runtime_behavior(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);
        $pipeline = PipelineDefaults::ensureDefaultPipeline();
        $stage = $pipeline->stages->first();
        $this->actingAs($admin)->patch(route('settings.general.update'), [
            'name' => 'VOZ Cobrança',
            'document' => '12.345.678/0001-90',
            'site' => 'https://voz.example.com',
            'email' => 'comercial@voz.example.com',
            'phone' => '1130000000',
            'address' => 'Rua Central, 100',
        ])->assertRedirect();

        $this->actingAs($admin)->patch(route('settings.integrations.update', 'twilio'), [
            'account_sid' => 'AC123',
            'auth_token' => 'secret',
            'from_number' => '+551130000000',
            'voice_webhook_url' => 'https://voz.example.com/webhook/twilio',
        ])->assertRedirect();

        $this->actingAs($admin)->patch(route('settings.users.update', $sdr), [
            'role' => UserRole::Closer->value,
        ])->assertRedirect();

        $this->actingAs($admin)->patch(route('settings.stages.update', $stage), [
            'name' => 'Lead recebido',
            'color' => '#123456',
            'position' => 1,
            'is_won' => false,
            'is_lost' => false,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('settings.options.store'), [
            'group' => 'lost_reasons',
            'label' => 'Concorrente',
            'color' => '#EF4444',
        ])->assertRedirect();

        $this->assertSame('VOZ Cobrança', CrmSetting::valueFor('voz.company')['name']);
        $this->assertSame('AC123', CrmSetting::valueFor('integrations.twilio')['account_sid']);
        $this->assertSame(UserRole::Closer, $sdr->refresh()->role);
        $this->assertSame('Lead recebido', $stage->refresh()->name);
        $this->assertDatabaseHas('crm_option_values', [
            'group' => 'lost_reasons',
            'label' => 'Concorrente',
        ]);
    }

    public function test_only_admin_can_open_settings(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);

        $this->actingAs($manager)->get(route('settings.index'))->assertForbidden();
    }
}
