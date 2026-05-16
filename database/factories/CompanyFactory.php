<?php

namespace Database\Factories;

use App\Enums\CompanySize;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\LeadTemperature;
use App\Enums\PriorityLevel;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legal_name' => $this->faker->company(),
            'trade_name' => $this->faker->companySuffix().' '.$this->faker->word(),
            'cnpj' => $this->validCnpj(),
            'segment' => $this->faker->randomElement(['Educação', 'Cursos livres', 'Ensino superior', 'Serviços']),
            'site' => $this->faker->url(),
            'phone' => $this->faker->numerify('##########'),
            'email' => $this->faker->companyEmail(),
            'whatsapp' => $this->faker->numerify('###########'),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'address' => $this->faker->streetAddress(),
            'status' => $this->faker->randomElement(CompanyStatus::cases()),
            'lead_source' => $this->faker->randomElement(['Indicação', 'Inbound', 'Outbound', 'Evento']),
            'responsible_user_id' => User::factory(),
            'last_interaction_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'average_collection_ticket' => $this->faker->randomFloat(2, 800, 15000),
            'overdue_customers_count' => $this->faker->numberBetween(20, 1200),
            'total_default_amount' => $this->faker->randomFloat(2, 20000, 1000000),
            'approx_customers_count' => $this->faker->numberBetween(200, 10000),
            'current_system' => $this->faker->randomElement(['ERP próprio', 'Totvs', 'Omie', 'Excel']),
            'has_internal_collection_team' => $this->faker->boolean(),
            'has_erp_integration' => $this->faker->boolean(),
            'portfolio_notes' => $this->faker->sentence(),
            'company_type' => $this->faker->randomElement(CompanyType::cases()),
            'company_size' => $this->faker->randomElement(CompanySize::cases()),
            'commercial_potential' => $this->faker->randomElement(['Baixo', 'Médio', 'Alto']),
            'lead_temperature' => $this->faker->randomElement(LeadTemperature::cases()),
            'priority' => $this->faker->randomElement(PriorityLevel::cases()),
            'pain_profile' => $this->faker->randomElement(['Inadimplência alta', 'Time enxuto', 'Sem automação', 'Baixa régua de cobrança']),
            'closing_probability' => $this->faker->numberBetween(0, 100),
        ];
    }

    private function validCnpj(): string
    {
        $base = array_map(fn (): int => $this->faker->numberBetween(0, 9), range(1, 12));
        $firstDigit = $this->calculateDigit($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = $this->calculateDigit([...$base, $firstDigit], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return implode('', [...$base, $firstDigit, $secondDigit]);
    }

    /**
     * @param  array<int, int>  $digits
     * @param  array<int, int>  $weights
     */
    private function calculateDigit(array $digits, array $weights): int
    {
        $sum = array_sum(array_map(fn (int $digit, int $weight): int => $digit * $weight, $digits, $weights));
        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
