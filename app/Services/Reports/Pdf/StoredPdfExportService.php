<?php

namespace App\Services\Reports\Pdf;

use App\Services\Reports\ReportGenerationService;
use Illuminate\Support\Facades\Storage;

class StoredPdfExportService
{
    public function __construct(
        private readonly ReportGenerationService $reportGenerationService,
    ) {
    }

    public function store(string $reportName, string $fileName, string $pdfContent, array $filters = [], ?int $requestedBy = null): object
    {
        $disk = 'local';
        $path = 'reports/' . now()->format('Y/m') . '/' . $fileName;
        Storage::disk($disk)->put($path, $pdfContent);

        return (object) $this->reportGenerationService->complete(
            $this->reportGenerationService->begin($reportName, $filters, $requestedBy),
            $disk,
            $path,
            $fileName
        );
    }
}
