<?php

namespace App\Services\Documents;

use App\Enums\DocumentVerificationStatus;
use App\Models\FarmerDocument;
use App\Models\MembershipApplication;
use App\Models\ReactivationRequest;
use App\Models\RenewalRequest;
use Carbon\CarbonImmutable;
use DomainException;

class DocumentVerificationService
{
    public function __construct(
        private readonly DocumentRequirementService $documentRequirementService,
        private readonly FarmerDocumentService $farmerDocumentService,
    ) {
    }

    public function verifyDocument(FarmerDocument $document, ?int $verifiedBy = null, ?string $remarks = null): FarmerDocument
    {
        $this->farmerDocumentService->assertReadyForVerification($document);

        $document->forceFill([
            'verification_status' => DocumentVerificationStatus::VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => CarbonImmutable::now(),
            'remarks' => $remarks,
        ])->save();

        return $document->refresh();
    }

    public function rejectDocument(FarmerDocument $document, ?int $verifiedBy = null, ?string $remarks = null): FarmerDocument
    {
        if (blank($remarks)) {
            throw new DomainException('Rejection remarks are required when rejecting a document.');
        }

        $document->forceFill([
            'verification_status' => DocumentVerificationStatus::REJECTED,
            'verified_by' => $verifiedBy,
            'verified_at' => CarbonImmutable::now(),
            'remarks' => $remarks,
        ])->save();

        return $document->refresh();
    }

    public function allRequiredVerified(MembershipApplication $application): bool
    {
        return $this->documentRequirementService->allRequiredVerified(
            'application',
            $application->documents,
            $application,
        );
    }

    public function missingRequiredDocuments(MembershipApplication $application): array
    {
        return $this->documentRequirementService->missingFor('application', $application->documents, $application, true);
    }

    public function allRequiredVerifiedForRenewal(RenewalRequest $renewalRequest): bool
    {
        return $this->documentRequirementService->allRequiredVerified(
            'renewal',
            $renewalRequest->documents,
            $renewalRequest,
        );
    }

    public function missingRequiredDocumentsForRenewal(RenewalRequest $renewalRequest): array
    {
        return $this->documentRequirementService->missingFor('renewal', $renewalRequest->documents, $renewalRequest, true);
    }

    public function allRequiredVerifiedForReactivation(ReactivationRequest $reactivationRequest): bool
    {
        return $this->documentRequirementService->allRequiredVerified(
            'reactivation',
            $reactivationRequest->documents,
            $reactivationRequest,
        );
    }

    public function missingRequiredDocumentsForReactivation(ReactivationRequest $reactivationRequest): array
    {
        return $this->documentRequirementService->missingFor('reactivation', $reactivationRequest->documents, $reactivationRequest, true);
    }
}
