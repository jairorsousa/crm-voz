<?php

namespace Database\Factories;

use App\Enums\CommunicationChannel as CommunicationChannelType;
use App\Models\CommunicationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationChannel>
 */
class CommunicationChannelFactory extends Factory
{
    protected $model = CommunicationChannel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'type' => CommunicationChannelType::Email,
            'provider' => 'smtp',
            'config' => [
                'host' => '127.0.0.1',
                'port' => 1025,
                'from_address' => fake()->safeEmail(),
                'from_name' => fake()->company(),
            ],
            'is_active' => true,
            'is_shared' => false,
            'is_default' => true,
        ];
    }

    public function call(): self
    {
        return $this->state(fn (): array => [
            'type' => CommunicationChannelType::Call,
            'provider' => 'twilio',
            'config' => [
                'account_sid' => null,
                'auth_token' => null,
                'from_number' => '+5511999999999',
                'voice_webhook_url' => 'https://voz.example.com/twilio',
                'webhook_token' => null,
            ],
            'is_shared' => true,
        ]);
    }

    public function whatsapp(): self
    {
        return $this->state(fn (): array => [
            'type' => CommunicationChannelType::Whatsapp,
            'provider' => 'evolution',
            'config' => [
                'url' => null,
                'key' => null,
                'instance' => 'voz-teste',
                'webhook_token' => null,
            ],
        ]);
    }
}
