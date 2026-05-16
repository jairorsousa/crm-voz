<?php

namespace App\Support\CRM;

use App\Models\CrmSetting;

class IntegrationSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function twilio(): array
    {
        return [
            'account_sid' => config('services.twilio.account_sid'),
            'auth_token' => config('services.twilio.auth_token'),
            'api_key' => config('services.twilio.api_key'),
            'api_secret' => config('services.twilio.api_secret'),
            'twiml_app_sid' => config('services.twilio.twiml_app_sid'),
            'caller_id' => config('services.twilio.caller_id'),
            'from_number' => config('services.twilio.from_number'),
            'voice_webhook_url' => config('services.twilio.voice_webhook_url'),
            'webhook_token' => config('services.twilio.webhook_token'),
            ...CrmSetting::valueFor('integrations.twilio'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function evolution(): array
    {
        return [
            'url' => config('services.evolution.url'),
            'key' => config('services.evolution.key'),
            'instance' => config('services.evolution.instance'),
            'webhook_token' => config('services.evolution.webhook_token'),
            ...CrmSetting::valueFor('integrations.evolution'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mail(): array
    {
        return [
            'mailer' => config('mail.default', 'mail'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'password' => config('mail.mailers.smtp.password'),
            ...CrmSetting::valueFor('integrations.mail'),
        ];
    }
}
