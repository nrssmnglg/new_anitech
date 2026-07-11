<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function download(ReportExport $reportExport): StreamedResponse|RedirectResponse
    {
        abort_unless($reportExport->status === 'completed', 404);

        if (! $reportExport->disk || ! $reportExport->path || ! Storage::disk($reportExport->disk)->exists($reportExport->path)) {
            return redirect()
                ->route('admin.reports.index')
                ->with('error', 'The selected report file is no longer available in storage.');
        }

        return Storage::disk($reportExport->disk)->download($reportExport->path, $reportExport->file_name ?: basename($reportExport->path));
    }
}
