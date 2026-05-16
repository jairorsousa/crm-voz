<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Support\CRM\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BuildReportExport implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $exportId)
    {
        $this->onQueue('reports');
    }

    public function handle(ReportExportService $service): void
    {
        $service->build(ReportExport::query()->findOrFail($this->exportId));
    }
}
