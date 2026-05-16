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

class SendWhatsappCommunication implements ShouldQueue
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
            $settings = $message->communicationChannel?->settings() ?? IntegrationSettings::evolution();
            $baseUrl = rtrim((string) $settings['url'], '/');
            $apiKey = (string) $settings['key'];
            $instance = (string) $settings['instance'];

            if (blank($baseUrl) || blank($apiKey) || blank($instance)) {
                throw new RuntimeException('Evolution API não configurada. Verifique EVOLUTION_API_URL, EVOLUTION_API_KEY e EVOLUTION_INSTANCE.');
            }

            $response = Http::withHeaders(['apikey' => $apiKey])
                ->post("{$baseUrl}/message/sendText/{$instance}", [
                    'number' => $message->to_address,
                    'text' => $message->body,
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Evolution API retornou erro: '.$response->body());
            }

            $payload = $response->json() ?? [];

            $message->update([
                'status' => CommunicationStatus::Sent,
                'external_id' => data_get($payload, 'key.id') ?? data_get($payload, 'messageId') ?? data_get($payload, 'id'),
                'provider_payload' => $payload,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            CommunicationTimeline::record($message->refresh(), 'WhatsApp enviado');
        } catch (Throwable $exception) {
            $message->update([
                'status' => CommunicationStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            CommunicationTimeline::record($message->refresh(), 'Falha ao enviar WhatsApp', $exception->getMessage());
        }
    }
}
