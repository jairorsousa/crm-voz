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
            $token = (string) $settings['auth_token'];
            $from = (string) $settings['from_number'];
            $webhookUrl = (string) $settings['voice_webhook_url'];

            if (blank($sid) || blank($token) || blank($from) || blank($webhookUrl)) {
                throw new RuntimeException('Twilio não configurado. Verifique TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_FROM_NUMBER e TWILIO_VOICE_WEBHOOK_URL.');
            }

            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Calls.json", [
                    'From' => $from,
                    'To' => $message->to_address,
                    'Url' => $webhookUrl,
                    'StatusCallback' => route('webhooks.twilio.calls'),
                    'StatusCallbackEvent' => 'initiated ringing answered completed',
                ]);

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
