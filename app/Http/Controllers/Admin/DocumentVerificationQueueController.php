<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentVerificationStatus;
use App\Exports\DocumentVerificationQueueExport;
use App\Http\Controllers\Controller;
use App\Models\FarmerDocument;
use App\Services\Documents\FarmerDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVerificationQueueController extends Controller
{
    public function __construct(
        private readonly FarmerDocumentService $farmerDocumentService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = $this->filtersFromRequest($request);

        $filteredDocuments = $this->applyCollectionFilters(
            $this->queueQuery($filters)->get(),
            $filters,
        )->values();
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 12;
        $documents = new LengthAwarePaginator(
            $filteredDocuments->forPage($page, $perPage)->values()->map(fn (FarmerDocument $document): array => $this->serializeQueueDocument($document)),
            $filteredDocuments->count(),
            $perPage,
            $page,
            [
                'path' => route('admin.document-verification-queue.index'),
                'query' => $request->query(),
            ],
        );

        $summaryDocuments = $this->applyCollectionFilters(
            $this->queueQuery([
                ...$filters,
                'status' => 'all',
                'flag' => 'all',
            ])->get(),
            [
                ...$filters,
                'status' => 'all',
                'flag' => 'all',
            ],
        );

        return Inertia::render('Admin/DocumentVerificationQueue/Index', [
            'documents' => $documents,
            'filters' => $filters,
            'filterOptions' => [
                'workflows' => [
                    ['value' => 'all', 'label' => 'All Workflows'],
                    ['value' => 'application', 'label' => 'Applications'],
                    ['value' => 'renewal', 'label' => 'Renewals'],
                ],
                'statuses' => [
                    ['value' => 'all', 'label' => 'All Statuses'],
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'verified', 'label' => 'Verified'],
                    ['value' => 'rejected', 'label' => 'Rejected'],
                ],
                'flags' => [
                    ['value' => 'all', 'label' => 'All Flags'],
                    ['value' => 'missing', 'label' => 'Missing'],
                    ['value' => 'expired', 'label' => 'Expired'],
                    ['value' => 'resubmission', 'label' => 'Re-submission'],
                    ['value' => 'ready', 'label' => 'Ready for Review'],
                ],
            ],
            'summary' => [
                'total' => $summaryDocuments->count(),
                'pending' => $summaryDocuments->filter(fn (FarmerDocument $document) => $document->verification_status?->value === 'pending')->count(),
                'rejected' => $summaryDocuments->filter(fn (FarmerDocument $document) => $document->verification_status?->value === 'rejected')->count(),
                'ready' => $summaryDocuments->filter(fn (FarmerDocument $document) => $this->farmerDocumentService->readyForVerification($document))->count(),
                'expired' => $summaryDocuments->filter(fn (FarmerDocument $document) => $this->farmerDocumentService->isExpired($document))->count(),
            ],
            'urls' => [
                'index' => route('admin.document-verification-queue.index'),
                'export' => route('admin.document-verification-queue.export'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse|BinaryFileResponse|\Illuminate\Http\Response
    {
        $filters = $this->filtersFromRequest($request);
        $columns = $this->resolveExportColumns($request->input('columns', []));
        $format = strtolower((string) $request->query('format', 'pdf'));
        $documents = $this->applyCollectionFilters(
            $this->queueQuery($filters)->get(),
            $filters,
        )->values()->map(fn (FarmerDocument $document): array => $this->serializeQueueDocument($document));
        $fileBase = 'document-verification-queue-' . now()->format('Ymd-His');

        if ($format === 'xlsx') {
            return Excel::download(
                new DocumentVerificationQueueExport($documents, $columns),
                $fileBase . '.xlsx'
            );
        }

        $pdf = Pdf::loadView('admin.document-verification-queue.export-pdf', [
            'documents' => $documents,
            'columns' => $columns,
            'columnLabels' => DocumentVerificationQueueExport::availableColumns(),
            'filters' => $filters,
            'generatedAt' => now()->format('F d, Y h:i A'),
        ])->setPaper('a4', 'landscape');

        if ($request->boolean('preview')) {
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileBase . '.pdf"',
            ]);
        }

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            $fileBase . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'search' => trim((string) $request->string('search')),
            'workflow' => (string) $request->string('workflow', 'all'),
            'status' => (string) $request->string('status', 'all'),
            'flag' => (string) $request->string('flag', 'all'),
        ];
    }

    private function queueQuery(array $filters): Builder
    {
        return FarmerDocument::query()
            ->with([
                'documentType:id,code,name',
                'membershipTransaction.farmer.profile:farmer_id,first_name,middle_name,last_name,suffix',
                'membershipTransaction.farmer:id,farmer_code',
                'membershipTransaction:id,farmer_id,transaction_type,source,application_no,year,submitted_at',
                'verifier:id,name',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('original_name', 'like', "%{$search}%")
                        ->orWhereHas('membershipTransaction.farmer', fn (Builder $farmerQuery) => $farmerQuery
                            ->where('farmer_code', 'like', "%{$search}%")
                            ->orWhereHas('profile', fn (Builder $profileQuery) => $profileQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")));
                });
            })
            ->when(($filters['workflow'] ?? 'all') !== 'all', function (Builder $query) use ($filters): void {
                $type = $filters['workflow'] === 'renewal' ? 'Renewal' : 'Application';
                $query->whereHas('membershipTransaction', fn (Builder $transactionQuery) => $transactionQuery->where('transaction_type', $type));
            })
            ->when(($filters['status'] ?? 'all') !== 'all', function (Builder $query) use ($filters): void {
                $status = match ($filters['status']) {
                    'pending' => 'Pending',
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                    default => null,
                };

                if ($status !== null) {
                    $query->where('verification_status', $status);
                }
            })
            ->latest('uploaded_at')
            ->latest('id');
    }

    private function applyCollectionFilters($documents, array $filters)
    {
        return $documents->filter(function (FarmerDocument $document) use ($filters): bool {
            return match ($filters['flag'] ?? 'all') {
                'missing' => ! $this->farmerDocumentService->uploadPresent($document),
                'expired' => $this->farmerDocumentService->isExpired($document),
                'resubmission' => $this->farmerDocumentService->requiresResubmission($document),
                'ready' => $this->farmerDocumentService->readyForVerification($document),
                default => true,
            };
        });
    }

    private function resolveExportColumns(array $columns): array
    {
        $available = DocumentVerificationQueueExport::availableColumns();
        $selected = collect($columns)
            ->map(fn ($column) => (string) $column)
            ->filter(fn (string $column): bool => array_key_exists($column, $available))
            ->values()
            ->all();

        return $selected !== []
            ? $selected
            : array_keys($available);
    }

    private function serializeQueueDocument(FarmerDocument $document): array
    {
        $transaction = $document->membershipTransaction;
        $workflow = strtolower((string) $transaction?->transaction_type) === 'renewal' ? 'renewal' : 'application';
        $uploadPresent = $this->farmerDocumentService->uploadPresent($document);

        if (! $uploadPresent && $transaction) {
            $this->farmerDocumentService->recoverStorageUploadIfPresent($document, $transaction);
            $uploadPresent = $this->farmerDocumentService->uploadPresent($document);
        }

        $readyForVerification = $this->farmerDocumentService->readyForVerification($document);
        $isExpired = $this->farmerDocumentService->isExpired($document);
        $expiresAt = $this->farmerDocumentService->expiresAt($document);
        $needsResubmission = $this->farmerDocumentService->requiresResubmission($document);
        $validationNotes = $this->farmerDocumentService->validationNotes($document);
        $status = $document->verification_status ?? DocumentVerificationStatus::PENDING;

        return [
            'id' => $document->id,
            'workflow' => $workflow,
            'workflowLabel' => $workflow === 'renewal' ? 'Renewal' : 'Application',
            'farmerName' => $transaction?->farmer?->full_name ?? 'Unknown Farmer',
            'farmerCode' => $transaction?->farmer?->farmer_code ?? 'No code',
            'documentLabel' => $document->document_type?->label() ?? 'Document',
            'referenceLabel' => $workflow === 'renewal'
                ? (($transaction?->application_no ?: 'Renewal #' . $transaction?->id) . ' / ' . ($transaction?->year ?? 'N/A'))
                : ($transaction?->application_no ?: 'Application #' . $transaction?->id),
            'sourceLabel' => strtoupper(str_replace('_', '-', (string) ($transaction?->source ?? 'walk_in'))),
            'status' => [
                'value' => $status?->value ?? 'pending',
                'label' => $status?->label() ?? 'Pending',
            ],
            'uploadPresent' => $uploadPresent,
            'readyForVerification' => $readyForVerification,
            'isExpired' => $isExpired,
            'expiresAtLabel' => $expiresAt?->format('M d, Y'),
            'needsResubmission' => $needsResubmission,
            'validationNotes' => $validationNotes,
            'remarks' => $document->remarks,
            'uploadedAt' => optional($document->uploaded_at)->format('M d, Y h:i A'),
            'verifiedAt' => optional($document->verified_at)->format('M d, Y h:i A'),
            'verifierName' => $document->verifier?->name,
            'previewMimeType' => $uploadPresent
                ? $this->previewMimeTypeForDocument($document)
                : null,
            'originalName' => $document->original_name ?: basename((string) $document->file_path),
            'actions' => [
                'reviewUrl' => $workflow === 'renewal'
                    ? route('admin.renewals.documents.review', [$transaction, $document])
                    : route('admin.membership-applications.documents.review', [$transaction, $document]),
                'viewUrl' => $uploadPresent
                    ? ($workflow === 'renewal'
                        ? route('admin.renewals.documents.view', [$transaction, $document])
                        : route('admin.membership-applications.documents.view', [$transaction, $document]))
                    : null,
                'showParentUrl' => $workflow === 'renewal'
                    ? route('admin.renewals.show', $transaction)
                    : route('admin.membership-applications.show', $transaction),
            ],
        ];
    }

    private function previewMimeTypeForDocument(FarmerDocument $document): string
    {
        $mimeType = strtolower(trim((string) ($document->mime_type ?? '')));

        if ($mimeType !== '' && $mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        $disk = $document->disk ?: 'public';
        $path = (string) $document->path;
        $storageMimeType = strtolower((string) (Storage::disk($disk)->mimeType($path) ?: ''));

        if ($storageMimeType !== '' && $storageMimeType !== 'application/octet-stream') {
            return $storageMimeType;
        }

        return match (strtolower(pathinfo($document->original_name ?: $path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            default => 'application/octet-stream',
        };
    }
}
