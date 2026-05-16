<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmOptionValue extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'label',
        'color',
        'position',
        'is_active',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }
}
