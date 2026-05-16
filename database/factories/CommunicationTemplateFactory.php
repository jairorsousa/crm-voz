<?php

namespace Database\Factories;

use App\Enums\CommunicationChannel;
use App\Models\CommunicationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationTemplate>
 */
class CommunicationTemplateFactory extends Factory
{
    protected $model = CommunicationTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $channel = fake()->randomElement([CommunicationChannel::Email, CommunicationChannel::Whatsapp]);

        return [
            'channel' => $channel,
            'name' => fake()->words(3, true),
            'subject' => $channel === CommunicationChannel::Email ? fake()->sentence(5) : null,
            'body' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
