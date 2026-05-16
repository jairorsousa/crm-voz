<?php

namespace Tests\Feature\CRM;

use App\Enums\CommunicationChannel;
use App\Enums\UserRole;
use App\Models\CommunicationChannel as CommunicationChannelModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommunicationChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_channel_with_authorized_users(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);

        $response = $this->actingAs($manager)->post(route('channels.store'), [
            'name' => 'WhatsApp SDR',
            'type' => CommunicationChannel::Whatsapp->value,
            'provider' => 'evolution',
            'config' => [
                'url' => 'https://evolution.example.com',
                'key' => 'secret-key',
                'instance' => 'sdr-01',
                'webhook_token' => 'hook-secret',
            ],
            'is_active' => true,
            'is_shared' => false,
            'is_default' => true,
            'user_ids' => [$sdr->id],
        ]);

        $response->assertRedirect(route('channels.index'));

        $channel = CommunicationChannelModel::query()->firstOrFail();

        $this->assertSame('WhatsApp SDR', $channel->name);
        $this->assertSame('secret-key', $channel->config['key']);
        $this->assertDatabaseHas('communication_channel_user', [
            'communication_channel_id' => $channel->id,
            'user_id' => $sdr->id,
        ]);

        $rawConfig = (string) DB::table('communication_channels')->where('id', $channel->id)->value('config');
        $this->assertStringNotContainsString('secret-key', $rawConfig);
    }

    public function test_sdr_cannot_manage_channels(): void
    {
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);

        $this->actingAs($sdr)->get(route('channels.index'))->assertForbidden();
    }
}
