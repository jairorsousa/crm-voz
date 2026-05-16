<?php

namespace Database\Factories;

use App\Enums\AutomationActionType;
use App\Enums\AutomationTrigger;
use App\Models\CommercialAutomation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommercialAutomation>
 */
class CommercialAutomationFactory extends Factory
{
    protected $model = CommercialAutomation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'trigger' => AutomationTrigger::OpportunityStageChanged,
            'conditions' => [],
            'actions' => [
                [
                    'type' => AutomationActionType::AddTimelineNote->value,
                    'title' => 'Automação executada',
                    'description' => 'Registro automático no histórico.',
                ],
            ],
            'is_active' => true,
        ];
    }
}
