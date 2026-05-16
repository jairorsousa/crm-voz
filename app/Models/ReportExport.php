<?php

namespace App\Models;

use App\Enums\ReportExportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'report',
        'format',
        'status',
        'filters',
        'file_path',
        'file_name',
        'mime_type',
        'rows_count',
        'error_message',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportExportStatus::class,
            'filters' => 'array',
            'rows_count' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
