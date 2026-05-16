<?php

namespace Database\Factories;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDirection;
use App\Enums\CommunicationOrigin;
use App\Enums\CommunicationStatus;
use App\Models\CommunicationMessage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationMessage>
 */
class CommunicationMessageFactory extends Factory
{
    protected $model = CommunicationMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'contact_id' => Contact::factory()->for($company),
            'opportunity_id' => null,
            'user_id' => User::factory(),
            'communication_template_id' => null,
            'channel' => CommunicationChannel::Email,
            'direction' => CommunicationDirection::Outbound,
            'status' => CommunicationStatus::Queued,
            'origin' => CommunicationOrigin::Manual,
            'provider' => 'mail',
            'to_address' => fake()->safeEmail(),
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'queued_at' => now(),
        ];
    }
}
