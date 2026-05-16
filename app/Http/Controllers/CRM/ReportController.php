<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Jobs\BuildReportExport;
use App\Models\ReportExport;
use App\Support\CRM\ReportBuilder;
use App\Support\CRM\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportBuilder $builder): Response
    {
        $filters = $this->filters($request, $builder);

        return Inertia::render('Reports/Index', [
            'filters' => $filters,
            'reports' => $builder->catalog(),
            'overview' => $builder->overview($request->user(), $filters),
            'previews' => $builder->previews($request->user(), $filters),
            'options' => $builder->options(),
            'exports' => $builder->exportsFor($request->user()),
        ]);
    }

    public function export(
        Request $request,
        string $report,
        string $format,
        ReportBuilder $builder,
        ReportExportService $service,
    ): BinaryFileResponse {
        $this->ensureReportAndFormat($builder, $report, $format);

        $export = $service->create($request->user(), $report, $format, $this->filters($request, $builder));
        $export = $service->build($export);

        abort_unless($export->file_path && Storage::exists($export->file_path), 422, $export->error_message ?: 'Exportacao indisponivel.');

        return response()->download(
            Storage::path($export->file_path),
            $export->file_name,
            ['Content-Type' => $export->mime_type],
        );
    }

    public function queueExport(
        Request $request,
        string $report,
        string $format,
        ReportBuilder $builder,
        ReportExportService $service,
    ): RedirectResponse {
        $this->ensureReportAndFormat($builder, $report, $format);

        $export = $service->create($request->user(), $report, $format, $this->filters($request, $builder));

        BuildReportExport::dispatch($export->id);

        return back()->with('success', 'Exportacao enviada para a fila de relatorios.');
    }

    public function download(Request $request, ReportExport $export): BinaryFileResponse
    {
        abort_unless($export->user_id === $request->user()->id, 403);
        abort_unless($export->file_path && Storage::exists($export->file_path), 404);

        return response()->download(
            Storage::path($export->file_path),
            $export->file_name,
            ['Content-Type' => $export->mime_type],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request, ReportBuilder $builder): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'stage_id' => ['nullable', 'integer', 'exists:pipeline_stages,id'],
            'source' => ['nullable', 'string', 'max:120'],
            'segment' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);

        return $builder->normalizeFilters($validated);
    }

    private function ensureReportAndFormat(ReportBuilder $builder, string $report, string $format): void
    {
        abort_unless(collect($builder->catalog())->pluck('key')->contains($report), 404);
        abort_unless(in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true), 404);
    }
}
