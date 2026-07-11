<?php

namespace App\Services\Documents;

use App\Enums\DocumentVerificationStatus;
use App\Models\FarmerDocument;
use App\Models\MembershipApplication;
use App\Models\RenewalRequest;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Collection;

class FarmerDocumentService
{
    public function __construct(
        private readonly DocumentRequirementService $documentRequirementService,
    ) {
    }

    public function ensureApplicationChecklist(MembershipApplication $application): Collection
    {
        foreach ($this->documentRequirementService->checklistRowsForApplication($application) as $row) {
            FarmerDocument::query()->updateOrCreate(
                [
                    'membership_transaction_id' => $application->id,
                    'document_type_id' => $row['document_type_id'],
                ],
                $row,
            );
        }

        return $application->documents()->with('documentType')->orderBy('document_type_id')->get();
    }

    public function ensureRenewalChecklist(RenewalRequest $renewalRequest): Collection
    {
        foreach ($this->documentRequirementService->checklistRowsForRenewal($renewalRequest) as $row) {
            FarmerDocument::query()->updateOrCreate(
                [
                    'membership_transaction_id' => $renewalRequest->id,
                    'document_type_id' => $row['document_type_id'],
                ],
                $row,
            );
        }

        return $renewalRequest->documents()->with('documentType')->orderBy('document_type_id')->get();
    }

    public function markReceived(FarmerDocument $document, bool $received, ?int $userId = null): FarmerDocument
    {
        $document->forceFill([
            'verification_status' => $received ? DocumentVerificationStatus::VERIFIED : DocumentVerificationStatus::PENDING,
            'verified_by' => $received ? $userId : null,
            'verified_at' => $received ? CarbonImmutable::now() : null,
        ])->save();

        return $document->refresh()->load('documentType');
    }

    public function uploadPresent(FarmerDocument $document): bool
    {
        $path = trim((string) $document->file_path);

        if ($path === '') {
            return false;
        }

        return ! str_starts_with($path, 'pending-upload/')
            && ! str_starts_with($path, 'office-checklist/');
    }

    public function readyForVerification(FarmerDocument $document): bool
    {
        $document->loadMissing('membershipApplication');
        $source = strtolower((string) optional($document->membershipApplication)->source);

        if ($source === 'walk_in') {
            return $document->verification_status === DocumentVerificationStatus::VERIFIED || $this->uploadPresent($document);
        }

        return $this->uploadPresent($document);
    }

    public function requiredSummary(string $workflow, iterable $documents, array|object $context = []): array
    {
        $required = $this->documentRequirementService->requiredFor($workflow, $context);
        $missing = $this->documentRequirementService->missingFor($workflow, $documents, $context);

        return [
            'required' => $required,
            'missing' => $missing,
            'is_complete' => $missing === [],
        ];
    }

    public function assertReadyForVerification(FarmerDocument $document): void
    {
        if (! $this->readyForVerification($document)) {
            throw new DomainException('This document cannot be verified until it has been received or uploaded.');
        }
    }

    public function expiresAt(FarmerDocument $document): ?CarbonImmutable
    {
        $days = $this->expiryDaysFor($document);

        if ($days === null) {
            return null;
        }

        $baseDate = $document->verified_at ?? $document->uploaded_at;

        if ($baseDate === null) {
            return null;
        }

        return CarbonImmutable::parse($baseDate)->addDays($days);
    }

    public function isExpired(FarmerDocument $document): bool
    {
        $expiresAt = $this->expiresAt($document);

        return $expiresAt !== null && $expiresAt->isPast();
    }

    public function requiresResubmission(FarmerDocument $document): bool
    {
        return $document->verification_status === DocumentVerificationStatus::REJECTED
            || $this->isExpired($document);
    }

    public function validationNotes(FarmerDocument $document): array
    {
        $notes = [];

        if (! $this->uploadPresent($document)) {
            $notes[] = 'No scan uploaded yet.';
        }

        if ($this->isExpired($document)) {
            $expiresAt = $this->expiresAt($document);
            $notes[] = 'Document expired' . ($expiresAt ? ' on ' . $expiresAt->format('M d, Y') : '') . '.';
        }

        if ($document->verification_status === DocumentVerificationStatus::REJECTED) {
            $notes[] = 'Rejected during validation. Re-submission required.';
        }

        if (filled($document->remarks)) {
            $notes[] = 'Validation note: ' . trim((string) $document->remarks);
        }

        return $notes;
    }

    private function expiryDaysFor(FarmerDocument $document): ?int
    {
        $type = strtolower((string) ($document->document_type?->value ?? ''));

        return match ($type) {
            'cedula',
            'payment_receipt',
            'previous_membership_id',
            'office_membership_form' => 365,
            'two_by_two_picture' => 730,
            default => null,
        };
    }
}



