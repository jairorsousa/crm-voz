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
            'legal_name' => fake()->company(),
            'trade_name' => fake()->companySuffix().' '.fake()->word(),
            'cnpj' => $this->validCnpj(),
            'segment' => fake()->randomElement(['Educação', 'Cursos livres', 'Ensino superior', 'Serviços']),
            'site' => fake()->url(),
            'phone' => fake()->numerify('##########'),
            'email' => fake()->companyEmail(),
            'whatsapp' => fake()->numerify('###########'),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'address' => fake()->streetAddress(),
            'status' => fake()->randomElement(CompanyStatus::cases()),
            'lead_source' => fake()->randomElement(['Indicação', 'Inbound', 'Outbound', 'Evento']),
            'responsible_user_id' => User::factory(),
            'last_interaction_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'average_collection_ticket' => fake()->randomFloat(2, 800, 15000),
            'overdue_customers_count' => fake()->numberBetween(20, 1200),
            'total_default_amount' => fake()->randomFloat(2, 20000, 1000000),
            'approx_customers_count' => fake()->numberBetween(200, 10000),
            'current_system' => fake()->randomElement(['ERP próprio', 'Totvs', 'Omie', 'Excel']),
            'has_internal_collection_team' => fake()->boolean(),
            'has_erp_integration' => fake()->boolean(),
            'portfolio_notes' => fake()->sentence(),
            'company_type' => fake()->randomElement(CompanyType::cases()),
            'company_size' => fake()->randomElement(CompanySize::cases()),
            'commercial_potential' => fake()->randomElement(['Baixo', 'Médio', 'Alto']),
            'lead_temperature' => fake()->randomElement(LeadTemperature::cases()),
            'priority' => fake()->randomElement(PriorityLevel::cases()),
            'pain_profile' => fake()->randomElement(['Inadimplência alta', 'Time enxuto', 'Sem automação', 'Baixa régua de cobrança']),
            'closing_probability' => fake()->numberBetween(0, 100),
        ];
    }

    private function validCnpj(): string
    {
        $base = array_map(fn (): int => fake()->numberBetween(0, 9), range(1, 12));
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
