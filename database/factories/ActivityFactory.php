<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\PriorityLevel;
use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'contact_id' => null,
            'opportunity_id' => null,
            'assigned_to_user_id' => User::factory(),
            'created_by_user_id' => User::factory(),
            'type' => fake()->randomElement(ActivityType::cases()),
            'status' => ActivityStatus::Pending,
            'priority' => fake()->randomElement(PriorityLevel::cases()),
            'title' => fake()->randomElement([
                'Ligar para decisor',
                'Enviar follow-up',
                'Agendar reunião',
                'Revisar proposta',
            ]),
            'description' => fake()->optional()->sentence(),
            'due_at' => fake()->dateTimeBetween('-2 days', '+10 days'),
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'status' => ActivityStatus::Pending,
            'due_at' => now()->subDay(),
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (): array => [
            'status' => ActivityStatus::Pending,
            'due_at' => now()->startOfDay()->addHours(10),
        ]);
    }
}
