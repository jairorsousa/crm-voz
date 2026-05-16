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
        $channel = $this->faker->randomElement([CommunicationChannel::Email, CommunicationChannel::Whatsapp]);

        return [
            'channel' => $channel,
            'name' => $this->faker->words(3, true),
            'subject' => $channel === CommunicationChannel::Email ? $this->faker->sentence(5) : null,
            'body' => $this->faker->paragraph(),
            'is_active' => true,
        ];
    }
}
