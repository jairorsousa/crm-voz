<?php

namespace App\Support\CRM;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Support\Str;

class PipelineDefaults
{
    /**
     * @return array<int, array{name: string, color: string, is_won?: bool, is_lost?: bool}>
     */
    public static function stages(): array
    {
        return [
            ['name' => 'Lead novo', 'color' => 'primary'],
            ['name' => 'Primeiro contato', 'color' => 'info'],
            ['name' => 'Qualificação', 'color' => 'info'],
            ['name' => 'Reunião agendada', 'color' => 'primary'],
            ['name' => 'Reunião realizada', 'color' => 'primary'],
            ['name' => 'Proposta enviada', 'color' => 'up'],
            ['name' => 'Negociação', 'color' => 'up'],
            ['name' => 'Fechado ganho', 'color' => 'success', 'is_won' => true],
            ['name' => 'Fechado perdido', 'color' => 'down', 'is_lost' => true],
        ];
    }

    public static function ensureDefaultPipeline(): Pipeline
    {
        $pipeline = Pipeline::query()->firstOrCreate([
            'is_default' => true,
        ], [
            'name' => 'Pipeline Comercial VOZ',
        ]);

        foreach (self::stages() as $index => $stage) {
            PipelineStage::query()->updateOrCreate([
                'pipeline_id' => $pipeline->id,
                'slug' => Str::slug($stage['name']),
            ], [
                'name' => $stage['name'],
                'position' => $index + 1,
                'color' => $stage['color'],
                'is_won' => $stage['is_won'] ?? false,
                'is_lost' => $stage['is_lost'] ?? false,
            ]);
        }

        return $pipeline->load('stages');
    }
}
