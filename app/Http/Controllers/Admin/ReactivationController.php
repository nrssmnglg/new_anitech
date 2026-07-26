<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssessmentStatus;
use App\Enums\DocumentVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachFarmerDocumentScanRequest;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Http\Requests\Admin\VerifyFarmerDocumentRequest;
use App\Models\Farmer;
use App\Models\FarmerDocument;
use App\Models\ReactivationRequest;
use App\Services\Documents\FarmerDocumentService;
use App\Services\Membership\ReactivationRequestService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReactivationController extends Controller
{
    public function __construct(
        private readonly ReactivationRequestService $reactivationRequestService,
        private readonly FarmerDocumentService $farmerDocumentService,
    ) {
    }

    public function store(Farmer $farmer): RedirectResponse
    {
        try {
            $request = $this->reactivationRequestService->createForFarmer($farmer, [
                'source' => 'walk_in',
                'year' => now()->year,
            ]);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'reactivation' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.reactivations.show', $request)
            ->with('success', 'Reactivation checklist started. Verify the required documents and settle the arrears payment to activate the farmer again.');
    }

    public function show(ReactivationRequest $reactivation): InertiaResponse
    {
        $reactivation->load([
            'farmer.profile',
            'farmer.barangay:id,name',
            'farmer.association:id,name',
            'farmer.memberType:id,code,name',
            'documents.documentType',
            'documents.verifier:id,name',
            'paymentAssessments.payments.paymentMethod',
        ]);

        $assessment = $this->reactivationRequestService->createOrRefreshAssessment($reactivation);
        $breakdown = $this->reactivationRequestService->assessmentBreakdown($reactivation);
        $documents = $reactivation->documents
            ->sortBy('document_type_id')
            ->values();
        $requiredDocuments = $documents->filter(fn (FarmerDocument $document): bool => (bool) $document->is_required);
        $verifiedRequiredCount = $requiredDocuments
            ->filter(fn (FarmerDocument $document): bool => $document->verification_status === DocumentVerificationStatus::VERIFIED)
            ->count();
        $documentsComplete = $requiredDocuments->isNotEmpty() && $verifiedRequiredCount === $requiredDocuments->count();
        $latestAssessment = $reactivation->paymentAssessments->sortByDesc('id')->first();
        $payments = $latestAssessment?->payments?->sortByDesc('paid_at')->values() ?? collect();
        $paymentSettled = in_array($latestAssessment?->status, [
            AssessmentStatus::PAID,
            AssessmentStatus::OVERPAID,
            AssessmentStatus::WAIVED,
        ], true);

        return Inertia::render('Admin/Reactivations/Show', [
            'reactivation' => [
                'id' => $reactivation->id,
                'applicationNo' => $reactivation->application_no,
                'status' => [
                    'value' => $reactivation->status?->value ?? 'submitted',
                    'label' => $reactivation->status?->label() ?? 'Submitted',
                ],
                'submittedAt' => optional($reactivation->submitted_at)->format('M d, Y h:i A'),
                'year' => $reactivation->year,
                'source' => $reactivation->source,
            ],
            'farmer' => [
                'id' => $reactivation->farmer?->id,
                'farmerCode' => $reactivation->farmer?->farmer_code,
                'fullName' => $reactivation->farmer?->full_name,
                'memberTypeLabel' => $reactivation->farmer?->memberType?->name,
                'memberTypeCode' => $reactivation->farmer?->memberType?->code,
                'barangay' => $reactivation->farmer?->barangay?->name,
                'association' => $reactivation->farmer?->association?->name,
                'inactiveReason' => $reactivation->farmer?->inactive_reason,
                'registeredAt' => optional($reactivation->farmer?->registered_at)->format('M d, Y'),
            ],
            'assessment' => [
                'membershipFee' => (float) ($assessment->membership_fee ?? 0),
                'annualDue' => (float) ($assessment->annual_due ?? 0),
                'mortuaryFee' => (float) ($assessment->mortuary_fee ?? 0),
                'totalAmountDue' => (float) ($assessment->total_amount_due ?? 0),
                'statusLabel' => $latestAssessment?->status?->label() ?? 'Pending',
                'arrearsYears' => $breakdown['arrears_years'],
            ],
            'documents' => $documents->map(fn (FarmerDocument $document): array => [
                'id' => $document->id,
                'label' => $document->document_type?->label() ?? 'Document',
                'isReceived' => (bool) $document->is_received,
                'uploadPresent' => $this->farmerDocumentService->uploadPresent($document),
                'verificationStatus' => [
                    'value' => $document->verification_status?->value ?? 'pending',
                    'label' => $document->verification_status?->label() ?? 'Pending',
                ],
                'remarks' => $document->remarks,
                'originalName' => $document->original_name ?: basename((string) $document->file_path),
                'actions' => [
                    'reviewUrl' => route('admin.reactivations.documents.review', [$reactivation, $document]),
                    'attachUrl' => route('admin.reactivations.documents.attach-scan', [$reactivation, $document]),
                    'viewUrl' => $this->farmerDocumentService->uploadPresent($document)
                        ? route('admin.reactivations.documents.view', [$reactivation, $document])
                        : null,
                ],
            ])->values()->all(),
            'payments' => $payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'amountPaid' => (float) $payment->amount_paid,
                'paidAt' => optional($payment->paid_at)->format('M d, Y h:i A'),
                'method' => $payment->payment_method,
                'referenceNo' => $payment->reference_no,
                'statusLabel' => $payment->status?->label() ?? 'Recorded',
            ])->all(),
            'flow' => [
                'documentsComplete' => $documentsComplete,
                'verifiedCount' => $verifiedRequiredCount,
                'requiredCount' => $requiredDocuments->count(),
                'paymentSettled' => $paymentSettled,
                'canRecordPaymentInAdmin' => $documentsComplete,
                'step' => $paymentSettled ? 3 : ($documentsComplete ? 2 : 1),
            ],
            'urls' => [
                'index' => route('admin.farmers.show', $reactivation->farmer),
                'recordPayment' => route('admin.reactivations.payment.store', $reactivation),
                'farmerShow' => route('admin.farmers.show', $reactivation->farmer),
            ],
        ]);
    }

    public function reviewDocument(
        VerifyFarmerDocumentRequest $request,
        ReactivationRequest $reactivation,
        FarmerDocument $document,
    ): RedirectResponse {
        if ((int) $document->membership_transaction_id !== (int) $reactivation->id) {
            abort(404);
        }

        try {
            $action = $request->validated('action');
            $remarks = $request->validated('remarks');

            match ($action) {
                'receive' => $this->reactivationRequestService->markDocumentReceived($reactivation, $document->id, true, Auth::id()),
                'unreceive' => $this->reactivationRequestService->markDocumentReceived($reactivation, $document->id, false, Auth::id()),
                'verify' => $this->reactivationRequestService->verifyDocument($reactivation, $document->id, Auth::id(), $remarks),
                'reject' => $this->reactivationRequestService->rejectDocument($reactivation, $document->id, Auth::id(), $remarks),
            };
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.reactivations.show', $reactivation)
            ->with('success', 'Reactivation document checklist updated.');
    }

    public function attachDocumentScan(
        AttachFarmerDocumentScanRequest $request,
        ReactivationRequest $reactivation,
        FarmerDocument $document,
    ): RedirectResponse {
        if ((int) $document->membership_transaction_id !== (int) $reactivation->id) {
            abort(404);
        }

        try {
            $this->reactivationRequestService->attachOfficeDocumentScan(
                $reactivation,
                $document->id,
                $request->file('document'),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.reactivations.show', $reactivation)
            ->with('success', 'Document scan attached successfully.');
    }

    public function recordPayment(StorePaymentRequest $request, ReactivationRequest $reactivation): RedirectResponse
    {
        try {
            $result = $this->reactivationRequestService->recordPayment($reactivation, $request->validated(), Auth::id());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'payment' => $exception->getMessage(),
            ]);
        }

        if (in_array($result['summary']['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID], true)) {
            return redirect()
                ->route('admin.farmers.show', $reactivation->farmer)
                ->with('success', 'Reactivation completed and farmer membership is active again.');
        }

        return redirect()
            ->route('admin.reactivations.show', $reactivation)
            ->with('success', 'Payment recorded. Full arrears payment is still required before reactivation completes.');
    }

    public function viewDocument(ReactivationRequest $reactivation, FarmerDocument $document): StreamedResponse
    {
        abort_unless($document->membership_transaction_id == $reactivation->id, 404);
        abort_unless($this->farmerDocumentService->uploadPresent($document), 404);

        $disk = $document->disk ?: 'public';
        $path = (string) $document->file_path;
        $mimeType = $this->previewMimeTypeForDocument($document);
        $filename = $document->original_name ?: basename((string) $document->file_path);

        return Storage::disk($disk)->response(
            $path,
            $filename,
            ['Content-Type' => $mimeType],
            'inline',
        );
    }

    private function previewMimeTypeForDocument(FarmerDocument $document): string
    {
        $mimeType = strtolower(trim((string) ($document->mime_type ?? '')));

        if ($mimeType !== '' && $mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        $disk = $document->disk ?: 'public';
        $path = (string) $document->file_path;
        $storageMimeType = strtolower((string) (Storage::disk($disk)->mimeType($path) ?: ''));

        if ($storageMimeType !== '' && $storageMimeType !== 'application/octet-stream') {
            return $storageMimeType;
        }

        return match (strtolower(pathinfo($document->original_name ?: $path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
