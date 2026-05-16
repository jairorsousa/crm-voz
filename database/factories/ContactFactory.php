<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->name(),
            'position' => $this->faker->jobTitle(),
            'department' => $this->faker->randomElement(['Diretoria', 'Financeiro', 'Operações', 'Comercial', 'TI']),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->numerify('##########'),
            'whatsapp' => $this->faker->numerify('###########'),
            'linkedin_url' => $this->faker->optional()->url(),
            'type' => $this->faker->randomElement(ContactType::cases()),
            'is_primary' => false,
            'receives_automations' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => [
            'type' => ContactType::DecisionMaker,
            'is_primary' => true,
        ]);
    }
}
