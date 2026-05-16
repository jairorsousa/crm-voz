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
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Diretoria', 'Financeiro', 'Operações', 'Comercial', 'TI']),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'whatsapp' => fake()->numerify('###########'),
            'linkedin_url' => fake()->optional()->url(),
            'type' => fake()->randomElement(ContactType::cases()),
            'is_primary' => false,
            'receives_automations' => true,
            'notes' => fake()->optional()->sentence(),
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
