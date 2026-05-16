<?php

namespace Tests\Feature\CRM;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDirection;
use App\Enums\CommunicationStatus;
use App\Models\CommunicationChannel as CommunicationChannelModel;
use App\Models\CommunicationMessage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_email_and_register_timeline(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['responsible_user_id' => $user->id]);
        $contact = Contact::factory()->for($company)->create([
            'email' => 'decisor@example.com',
        ]);
        $channel = CommunicationChannelModel::factory()->create([
            'name' => 'E-mail do usuário',
            'type' => CommunicationChannel::Email,
            'provider' => 'smtp',
            'config' => [
                'host' => '127.0.0.1',
                'port' => 1025,
                'from_address' => $user->email,
                'from_name' => $user->name,
            ],
        ]);
        $channel->users()->attach($user);

        $response = $this->actingAs($user)->post(route('emails.store'), [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'communication_channel_id' => $channel->id,
            'to_address' => 'decisor@example.com',
            'subject' => 'Proposta VOZ',
            'body' => 'Olá, vamos conversar sobre a operação de cobrança.',
        ]);

        $response->assertRedirect(route('emails.index'));

        $this->assertDatabaseHas('communication_messages', [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'channel' => CommunicationChannel::Email->value,
            'status' => CommunicationStatus::Sent->value,
            'subject' => 'Proposta VOZ',
        ]);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'type' => 'communication.email.sent',
        ]);
    }

    public function test_call_attempt_fails_clearly_when_twilio_is_not_configured(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['responsible_user_id' => $user->id]);
        $contact = Contact::factory()->for($company)->create(['phone' => '11999999999']);
        $channel = CommunicationChannelModel::factory()->call()->create();
        $channel->users()->attach($user);

        $response = $this->actingAs($user)->post(route('calls.store'), [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'communication_channel_id' => $channel->id,
            'to_address' => '11999999999',
            'notes' => 'Tentativa inicial.',
        ]);

        $response->assertRedirect(route('calls.index'));

        $this->assertDatabaseHas('communication_messages', [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'channel' => CommunicationChannel::Call->value,
            'status' => CommunicationStatus::Failed->value,
            'to_address' => '+5511999999999',
        ]);

        $message = CommunicationMessage::query()->firstOrFail();
        $this->assertStringContainsString('Twilio não configurado', (string) $message->error_message);
    }

    public function test_whatsapp_attempt_fails_clearly_when_evolution_is_not_configured(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['responsible_user_id' => $user->id]);
        $contact = Contact::factory()->for($company)->create(['whatsapp' => '11988887777']);
        $channel = CommunicationChannelModel::factory()->whatsapp()->create();
        $channel->users()->attach($user);

        $response = $this->actingAs($user)->post(route('whatsapp.store'), [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'communication_channel_id' => $channel->id,
            'to_address' => '11988887777',
            'body' => 'Olá, tudo bem?',
        ]);

        $response->assertRedirect(route('whatsapp.index'));

        $this->assertDatabaseHas('communication_messages', [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'channel' => CommunicationChannel::Whatsapp->value,
            'status' => CommunicationStatus::Failed->value,
        ]);

        $message = CommunicationMessage::query()->firstOrFail();
        $this->assertStringContainsString('Evolution API não configurada', (string) $message->error_message);
    }

    public function test_twilio_webhook_updates_call_idempotently(): void
    {
        $message = CommunicationMessage::factory()->create([
            'channel' => CommunicationChannel::Call,
            'status' => CommunicationStatus::Sent,
            'provider' => 'twilio',
            'external_id' => 'CA123',
            'to_address' => '11999999999',
        ]);

        $payload = [
            'CallSid' => 'CA123',
            'CallStatus' => 'completed',
            'CallDuration' => '42',
        ];

        $this->postJson(route('webhooks.twilio.calls'), $payload)->assertOk();
        $this->postJson(route('webhooks.twilio.calls'), $payload)->assertOk();

        $message->refresh();
        $this->assertSame(CommunicationStatus::Completed, $message->status);
        $this->assertSame(42, $message->duration_seconds);
        $this->assertDatabaseCount('communication_webhook_events', 1);
    }

    public function test_evolution_webhook_records_inbound_message_idempotently(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create(['whatsapp' => '11977776666']);

        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => [
                    'id' => 'WA123',
                    'remoteJid' => '5511977776666@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => 'Tenho interesse na proposta.',
                ],
            ],
        ];

        $this->postJson(route('webhooks.evolution.whatsapp'), $payload)->assertOk();
        $this->postJson(route('webhooks.evolution.whatsapp'), $payload)->assertOk();

        $this->assertDatabaseHas('communication_messages', [
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'channel' => CommunicationChannel::Whatsapp->value,
            'direction' => CommunicationDirection::Inbound->value,
            'status' => CommunicationStatus::Received->value,
            'body' => 'Tenho interesse na proposta.',
        ]);
        $this->assertDatabaseCount('communication_webhook_events', 1);
        $this->assertDatabaseCount('communication_messages', 1);
    }
}
