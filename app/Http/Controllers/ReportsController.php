<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorePortalReportRequest;
use App\Services\Reports\CorePortalCsvExporter;
use App\Services\Reports\CorePortalReportService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportsController extends Controller
{
    public function index(
        CorePortalReportRequest $request,
        CorePortalReportService $reports,
    ): Response {
        return Inertia::render('Reports/Index', $reports->page(
            $request->report(),
            $request->user(),
            $request->filters(),
        ));
    }

    public function export(
        CorePortalReportRequest $request,
        string $report,
        CorePortalCsvExporter $exporter,
    ): StreamedResponse {
        return $exporter->download($report, $request->user(), $request->filters());
    }
}
