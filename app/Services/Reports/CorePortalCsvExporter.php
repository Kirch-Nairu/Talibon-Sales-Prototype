<?php

namespace App\Services\Reports;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CorePortalCsvExporter
{
    public function __construct(private readonly CorePortalReportService $reports) {}

    public function download(string $report, User $actor, array $filters): StreamedResponse
    {
        $columns = $this->reports->columns($report);
        $rows = $this->reports->exportRows($report, $actor, $filters);
        $filename = 'talibon-'.$report.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($columns, $rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_values($columns));

            foreach ($rows as $row) {
                fputcsv($output, array_map(
                    fn (string $key): mixed => $this->safeCell($row[$key] ?? null),
                    array_keys($columns),
                ));
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $effective = ltrim($value);
        if ($effective !== '' && in_array($effective[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
