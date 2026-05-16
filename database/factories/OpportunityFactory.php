<?php

namespace Database\Factories;

use App\Enums\OpportunityStatus;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Support\CRM\PipelineDefaults;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pipeline = Pipeline::query()->where('is_default', true)->first()
            ?? PipelineDefaults::ensureDefaultPipeline();
        $stage = PipelineStage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('is_won', false)
            ->where('is_lost', false)
            ->inRandomOrder()
            ->first();

        return [
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage?->id,
            'company_id' => Company::factory(),
            'contact_id' => null,
            'responsible_user_id' => User::factory(),
            'title' => 'Projeto '.$this->faker->words(3, true),
            'estimated_value' => $this->faker->randomFloat(2, 5000, 250000),
            'probability' => $this->faker->numberBetween(10, 90),
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+90 days')->format('Y-m-d'),
            'source' => $this->faker->randomElement(['Outbound', 'Inbound', 'Indicação', 'Evento']),
            'status' => OpportunityStatus::Open,
            'products_interests' => $this->faker->randomElement(['Cobrança ativa', 'Régua de cobrança', 'Recuperação de inadimplência']),
            'notes' => $this->faker->optional()->sentence(),
            'last_stage_changed_at' => now(),
        ];
    }
}
