<?php

namespace App\Http\Controllers\CRM;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDirection;
use App\Enums\CommunicationOrigin;
use App\Enums\CommunicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CommunicationChannel as CommunicationChannelModel;
use App\Models\CommunicationMessage;
use App\Models\CommunicationWebhookEvent;
use App\Models\Contact;
use App\Support\CRM\CommunicationTimeline;
use App\Support\CRM\IntegrationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommunicationWebhookController extends Controller
{
    public function twilioVoice(Request $request): Response
    {
        $to = preg_replace('/\D+/', '', (string) $request->input('To', '')) ?: '';
        $callerId = preg_replace('/[^\d+]/', '', (string) ($request->input('CallerId') ?: config('services.twilio.caller_id'))) ?: '';

        if (str_starts_with($to, '00')) {
            $to = mb_substr($to, 2);
        }

        if (filled($to) && ! str_starts_with($to, '55')) {
            $to = '55'.$to;
        }

        $to = filled($to) ? '+'.$to : '';

        if (blank($to) || blank($callerId)) {
            return $this->twiml('<Say language="pt-BR">Nao foi possivel iniciar a ligacao.</Say>');
        }

        $callerId = htmlspecialchars($callerId, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $to = htmlspecialchars($to, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return $this->twiml("<Dial callerId=\"{$callerId}\"><Number>{$to}</Number></Dial>");
    }

    public function twilioCall(Request $request): JsonResponse
    {
        $channel = $this->authorizeWebhook('twilio', $request);

        $payload = $request->all();
        $callSid = (string) ($payload['CallSid'] ?? '');
        $status = (string) ($payload['CallStatus'] ?? 'status');
        $event = $this->storeWebhookEvent('twilio', "call.{$status}", $callSid.'-'.sha1(json_encode($payload)), $callSid, $payload);

        if (! $event->wasRecentlyCreated && $event->processed_at) {
            return response()->json(['status' => 'duplicate']);
        }

        $message = CommunicationMessage::query()
            ->where('provider', 'twilio')
            ->when($channel, fn ($query) => $query->where('communication_channel_id', $channel->id))
            ->where('external_id', $callSid)
            ->first();

        if ($message) {
            $message->update([
                'status' => $this->twilioStatus($status),
                'duration_seconds' => isset($payload['CallDuration']) ? (int) $payload['CallDuration'] : $message->duration_seconds,
                'provider_payload' => array_merge($message->provider_payload ?? [], ['twilio_webhook' => $payload]),
                'completed_at' => in_array($status, ['completed', 'failed', 'busy', 'no-answer', 'canceled'], true) ? now() : $message->completed_at,
            ]);

            CommunicationTimeline::record($message->refresh(), 'Status da ligação atualizado');
        }

        $event->update(['processed_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    private function twiml(string $body): Response
    {
        return response(
            '<?xml version="1.0" encoding="UTF-8"?><Response>'.$body.'</Response>',
            200,
            ['Content-Type' => 'text/xml; charset=UTF-8'],
        );
    }

    public function evolutionWhatsapp(Request $request): JsonResponse
    {
        $channel = $this->authorizeWebhook('evolution', $request);

        $payload = $request->all();
        $eventType = (string) (data_get($payload, 'event') ?? data_get($payload, 'type') ?? 'message');
        $messageId = (string) (data_get($payload, 'data.key.id') ?? data_get($payload, 'key.id') ?? data_get($payload, 'messageId') ?? data_get($payload, 'id') ?? '');
        $eventId = (string) (data_get($payload, 'id') ?? $messageId.'-'.sha1(json_encode($payload)));
        $event = $this->storeWebhookEvent('evolution', $eventType, $eventId, $messageId, $payload);

        if (! $event->wasRecentlyCreated && $event->processed_at) {
            return response()->json(['status' => 'duplicate']);
        }

        $message = CommunicationMessage::query()
            ->where('provider', 'evolution')
            ->when($channel, fn ($query) => $query->where('communication_channel_id', $channel->id))
            ->where('external_id', $messageId)
            ->first();

        if ($message) {
            $message->update([
                'status' => str_contains($eventType, 'deliver') ? CommunicationStatus::Delivered : $message->status,
                'provider_payload' => array_merge($message->provider_payload ?? [], ['evolution_webhook' => $payload]),
                'delivered_at' => str_contains($eventType, 'deliver') ? now() : $message->delivered_at,
            ]);

            CommunicationTimeline::record($message->refresh(), 'Status do WhatsApp atualizado');
        } elseif (! (bool) data_get($payload, 'data.key.fromMe', false)) {
            $this->recordInboundWhatsapp($payload, $messageId, $channel);
        }

        $event->update(['processed_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeWebhookEvent(string $provider, string $eventType, string $eventId, ?string $messageId, array $payload): CommunicationWebhookEvent
    {
        return CommunicationWebhookEvent::query()->firstOrCreate([
            'provider' => $provider,
            'external_event_id' => $eventId,
        ], [
            'event_type' => $eventType,
            'external_message_id' => $messageId,
            'payload' => $payload,
        ]);
    }

    private function twilioStatus(string $status): CommunicationStatus
    {
        return match ($status) {
            'completed' => CommunicationStatus::Completed,
            'failed' => CommunicationStatus::Failed,
            'busy' => CommunicationStatus::Busy,
            'no-answer' => CommunicationStatus::NoAnswer,
            'canceled' => CommunicationStatus::Canceled,
            default => CommunicationStatus::Sent,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordInboundWhatsapp(array $payload, string $messageId, ?CommunicationChannelModel $channel): void
    {
        $remote = (string) (data_get($payload, 'data.key.remoteJid') ?? data_get($payload, 'from') ?? '');
        $number = preg_replace('/\D+/', '', $remote) ?: '';
        $suffix = mb_substr($number, -10);

        if ($suffix === '') {
            return;
        }

        $contact = Contact::query()
            ->with('company')
            ->where('whatsapp', 'like', "%{$suffix}")
            ->orWhere('phone', 'like', "%{$suffix}")
            ->first();

        if (! $contact) {
            return;
        }

        $body = (string) (data_get($payload, 'data.message.conversation')
            ?? data_get($payload, 'data.message.extendedTextMessage.text')
            ?? data_get($payload, 'message')
            ?? '');

        $message = CommunicationMessage::query()->create([
            'company_id' => $contact->company_id,
            'contact_id' => $contact->id,
            'communication_channel_id' => $channel?->id,
            'user_id' => null,
            'channel' => CommunicationChannel::Whatsapp,
            'direction' => CommunicationDirection::Inbound,
            'status' => CommunicationStatus::Received,
            'origin' => CommunicationOrigin::Webhook,
            'provider' => 'evolution',
            'external_id' => $messageId ?: null,
            'from_address' => $number,
            'to_address' => (string) ($channel?->settings()['instance'] ?? IntegrationSettings::evolution()['instance'] ?? config('services.evolution.instance')),
            'body' => $body,
            'provider_payload' => $payload,
            'received_at' => now(),
        ]);

        CommunicationTimeline::record($message, 'WhatsApp recebido');
    }

    private function authorizeWebhook(string $provider, Request $request): ?CommunicationChannelModel
    {
        $receivedToken = (string) ($request->header('X-VOZ-Webhook-Token') ?: $request->query('token'));

        $channels = CommunicationChannelModel::query()
            ->active()
            ->where('provider', $provider)
            ->get();

        foreach ($channels as $channel) {
            $expectedToken = (string) ($channel->settings()['webhook_token'] ?? '');

            if (blank($expectedToken)) {
                continue;
            }

            abort_if(blank($receivedToken), 403);

            if (hash_equals($expectedToken, $receivedToken)) {
                return $channel;
            }
        }

        $settings = $provider === 'twilio' ? IntegrationSettings::twilio() : IntegrationSettings::evolution();
        $expectedToken = (string) ($settings['webhook_token'] ?? '');

        if (blank($expectedToken)) {
            return null;
        }

        abort_unless(hash_equals($expectedToken, $receivedToken), 403);

        return null;
    }
}
