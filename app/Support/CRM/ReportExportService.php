<?php

namespace App\Support\CRM;

use App\Enums\ReportExportStatus;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportExportService
{
    public function __construct(private readonly ReportBuilder $builder) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function create(User $user, string $report, string $format, array $filters): ReportExport
    {
        return ReportExport::query()->create([
            'user_id' => $user->id,
            'report' => $report,
            'format' => $format,
            'status' => ReportExportStatus::Pending,
            'filters' => $filters,
        ]);
    }

    public function build(ReportExport $export): ReportExport
    {
        $export->update([
            'status' => ReportExportStatus::Processing,
            'error_message' => null,
        ]);

        try {
            $table = $this->builder->table($export->user, $export->report, $export->filters ?? []);
            $extension = $this->extension($export->format);
            $fileName = str($table['label'])
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '-')
                ->trim('-')
                ->append('-'.now()->format('Ymd-His').'.'.$extension)
                ->toString();
            $path = "reports/{$export->user_id}/{$fileName}";

            Storage::put($path, $this->content($export->format, $table));

            $export->update([
                'status' => ReportExportStatus::Completed,
                'file_path' => $path,
                'file_name' => $fileName,
                'mime_type' => $this->mimeType($export->format),
                'rows_count' => count($table['rows']),
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $export->update([
                'status' => ReportExportStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $export->refresh();
    }

    /**
     * @param  array<string, mixed>  $table
     */
    private function content(string $format, array $table): string
    {
        return match ($format) {
            'csv' => $this->csv($table),
            'excel', 'xlsx' => $this->excelHtml($table),
            'pdf' => $this->pdf($table),
            default => throw new \InvalidArgumentException('Formato de exportacao invalido.'),
        };
    }

    /**
     * @param  array<string, mixed>  $table
     */
    private function csv(array $table): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_values($table['columns']), ';');

        foreach ($table['rows'] as $row) {
            fputcsv($handle, array_map(fn (string $key): mixed => $row[$key] ?? '', array_keys($table['columns'])), ';');
        }

        rewind($handle);

        return "\xEF\xBB\xBF".stream_get_contents($handle);
    }

    /**
     * @param  array<string, mixed>  $table
     */
    private function excelHtml(array $table): string
    {
        $head = collect($table['columns'])
            ->map(fn (string $label): string => '<th>'.e($label).'</th>')
            ->implode('');
        $body = collect($table['rows'])
            ->map(function (array $row) use ($table): string {
                $cells = collect(array_keys($table['columns']))
                    ->map(fn (string $key): string => '<td>'.e((string) ($row[$key] ?? '')).'</td>')
                    ->implode('');

                return "<tr>{$cells}</tr>";
            })
            ->implode('');

        return <<<HTML
        <html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body>
            <table>
                <thead><tr>{$head}</tr></thead>
                <tbody>{$body}</tbody>
            </table>
        </body>
        </html>
        HTML;
    }

    /**
     * @param  array<string, mixed>  $table
     */
    private function pdf(array $table): string
    {
        $lines = [$table['label'], 'Gerado em '.now()->format('d/m/Y H:i'), ''];
        $lines[] = implode(' | ', array_values($table['columns']));

        foreach (array_slice($table['rows'], 0, 120) as $row) {
            $lines[] = implode(' | ', array_map(fn (string $key): string => (string) ($row[$key] ?? ''), array_keys($table['columns'])));
        }

        $stream = "BT\n/F1 9 Tf\n40 800 Td\n";
        foreach ($lines as $line) {
            $stream .= '('.$this->pdfText(mb_substr((string) $line, 0, 130)).") Tj\n0 -13 Td\n";
        }
        $stream .= "ET\n";

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n{$stream}endstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text);
    }

    private function extension(string $format): string
    {
        return match ($format) {
            'csv' => 'csv',
            'excel', 'xlsx' => 'xls',
            'pdf' => 'pdf',
            default => 'txt',
        };
    }

    private function mimeType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv; charset=UTF-8',
            'excel', 'xlsx' => 'application/vnd.ms-excel',
            'pdf' => 'application/pdf',
            default => 'text/plain',
        };
    }
}
