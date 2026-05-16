<?php

namespace App\Jobs;

use App\Enums\CommunicationStatus;
use App\Models\CommunicationMessage;
use App\Support\CRM\CommunicationTimeline;
use App\Support\CRM\IntegrationSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class StartTwilioCall implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $messageId)
    {
        $this->onQueue('communications');
    }

    public function handle(): void
    {
        $message = CommunicationMessage::query()->findOrFail($this->messageId);

        try {
            $settings = $message->communicationChannel?->settings() ?? IntegrationSettings::twilio();
            $sid = (string) $settings['account_sid'];
            $authUser = (string) (($settings['api_key'] ?? null) ?: $sid);
            $authSecret = (string) (($settings['api_secret'] ?? null) ?: ($settings['auth_token'] ?? null));
            $from = (string) (($settings['caller_id'] ?? null) ?: ($settings['from_number'] ?? null));
            $webhookUrl = (string) ($settings['voice_webhook_url'] ?? '');
            $twimlAppSid = (string) ($settings['twiml_app_sid'] ?? '');

            if (blank($sid) || blank($authUser) || blank($authSecret) || blank($from) || (blank($webhookUrl) && blank($twimlAppSid))) {
                throw new RuntimeException('Twilio não configurado. Verifique Account SID, credenciais, Caller ID e URL de voz ou TwiML App SID.');
            }

            $payload = [
                'From' => $from,
                'To' => $message->to_address,
                'StatusCallback' => route('webhooks.twilio.calls'),
                'StatusCallbackEvent' => 'initiated ringing answered completed',
            ];

            if (filled($twimlAppSid)) {
                $payload['ApplicationSid'] = $twimlAppSid;
            } else {
                $payload['Url'] = $webhookUrl;
            }

            $response = Http::asForm()
                ->withBasicAuth($authUser, $authSecret)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Calls.json", $payload);

            if ($response->failed()) {
                throw new RuntimeException('Twilio retornou erro: '.$response->body());
            }

            $payload = $response->json() ?? [];

            $message->update([
                'status' => CommunicationStatus::Sent,
                'external_id' => $payload['sid'] ?? null,
                'provider_payload' => $payload,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            CommunicationTimeline::record($message->refresh(), 'Ligação iniciada');
        } catch (Throwable $exception) {
            $message->update([
                'status' => CommunicationStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            CommunicationTimeline::record($message->refresh(), 'Falha ao iniciar ligação', $exception->getMessage());
        }
    }
}
