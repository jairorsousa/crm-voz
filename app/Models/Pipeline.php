<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }
}
