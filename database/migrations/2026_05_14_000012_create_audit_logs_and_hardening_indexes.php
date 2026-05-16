<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('auditable');
            $table->string('event', 120)->index();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->index(['responsible_user_id', 'status', 'lead_source', 'segment'], 'companies_owner_status_source_segment_idx');
            $table->index(['segment', 'status', 'created_at'], 'companies_segment_status_created_idx');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->index(['company_id', 'type', 'is_primary'], 'contacts_company_type_primary_idx');
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            $table->index(['responsible_user_id', 'status', 'expected_close_date'], 'opps_owner_status_forecast_idx');
            $table->index(['source', 'status', 'created_at'], 'opps_source_status_created_idx');
            $table->index(['pipeline_stage_id', 'expected_close_date'], 'opps_stage_forecast_idx');
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->index(['assigned_to_user_id', 'type', 'status', 'due_at'], 'activities_owner_type_status_due_idx');
        });

        Schema::table('communication_messages', function (Blueprint $table): void {
            $table->index(['channel', 'user_id', 'status', 'created_at'], 'comm_channel_user_status_created_idx');
            $table->index(['channel', 'origin', 'created_at'], 'comm_channel_origin_created_idx');
        });

        Schema::table('timeline_events', function (Blueprint $table): void {
            $table->index(['company_id', 'type', 'occurred_at'], 'timeline_company_type_occurred_idx');
        });

        $this->encryptExistingIntegrationSecrets();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timeline_events', function (Blueprint $table): void {
            $table->dropIndex('timeline_company_type_occurred_idx');
        });

        Schema::table('communication_messages', function (Blueprint $table): void {
            $table->dropIndex('comm_channel_user_status_created_idx');
            $table->dropIndex('comm_channel_origin_created_idx');
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->dropIndex('activities_owner_type_status_due_idx');
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropIndex('opps_owner_status_forecast_idx');
            $table->dropIndex('opps_source_status_created_idx');
            $table->dropIndex('opps_stage_forecast_idx');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropIndex('contacts_company_type_primary_idx');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex('companies_owner_status_source_segment_idx');
            $table->dropIndex('companies_segment_status_created_idx');
        });

        Schema::dropIfExists('audit_logs');
    }

    private function encryptExistingIntegrationSecrets(): void
    {
        $sensitiveFields = [
            'integrations.twilio' => ['auth_token', 'webhook_token'],
            'integrations.evolution' => ['key', 'webhook_token'],
            'integrations.mail' => ['password'],
        ];

        foreach ($sensitiveFields as $settingKey => $fields) {
            $setting = DB::table('crm_settings')->where('key', $settingKey)->first();

            if (! $setting?->value) {
                continue;
            }

            $value = json_decode((string) $setting->value, true);

            if (! is_array($value)) {
                continue;
            }

            foreach ($fields as $field) {
                if (! filled($value[$field] ?? null) || str_starts_with((string) $value[$field], 'encrypted:')) {
                    continue;
                }

                $value[$field] = 'encrypted:'.Crypt::encryptString((string) $value[$field]);
            }

            DB::table('crm_settings')
                ->where('id', $setting->id)
                ->update(['value' => json_encode($value)]);
        }
    }
};
