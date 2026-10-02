<?php

namespace App\Services\Documents;

use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Models\DocumentRequirement;
use App\Models\DocumentType as DocumentTypeModel;
use App\Models\MembershipApplication;
use App\Models\ReactivationRequest;
use App\Models\RenewalRequest;
use InvalidArgumentException;
use Throwable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DocumentRequirementService
{
    public function requiredLabelsFor(string $workflow, array|object $context = []): array
    {
        $query = DocumentRequirement::query()->where('transaction_type', ucfirst(strtolower($workflow)));

        if ($query->exists()) {
            return $query->where('is_active', true)->where('is_required', true)
                ->with('documentType')->orderBy('id')->get()
                ->map(fn (DocumentRequirement $requirement) => $requirement->documentType?->name
                    ?: $requirement->documentType?->code)
                ->filter()->values()->all();
        }

        return array_map(fn (DocumentType $type) => $type->label(), $this->requiredFor($workflow, $context));
    }

    public function requiredFor(string $workflow, array|object $context = []): array
    {
        $source = $this->resolveSource($context);
        $configured = $this->configuredRequirementsFor($workflow, $source);

        if ($configured !== null) {
            return $configured->all();
        }

        return $this->defaultRequiredFor($workflow, $source);
    }

    public function configuredDocumentTypesFor(string $workflow, array|object $context = []): Collection
    {
        $source = $this->resolveSource($context);

        return $this->configuredRequirementsFor($workflow, $source, false)
            ?? collect($this->defaultRequiredFor($workflow, $source));
    }

    private function configuredChecklistRequirementsFor(string $workflow, array|object $context = []): Collection
    {
        $source = $this->resolveSource($context);
        $configured = $this->configuredRequirementRecordsFor($workflow, false);

        if ($configured !== null) {
            return $configured
                ->map(function (DocumentRequirement $requirement): ?array {
                    $type = DocumentType::tryFrom((string) $requirement->documentType?->code);

                    return $type ? [
                        'type' => $type,
                        'is_required' => (bool) $requirement->is_required,
                    ] : null;
                })
                ->filter()
                ->values();
        }

        return collect($this->defaultRequiredFor($workflow, $source))
            ->map(fn (DocumentType $type): array => [
                'type' => $type,
                'is_required' => true,
            ]);
    }

    private function defaultRequiredFor(string $workflow, ?string $source = null): array
    {
        return match (strtolower($workflow)) {
            'application' => $source === 'walk_in'
                ? [
                    DocumentType::BIRTH_CERTIFICATE,
                    DocumentType::CEDULA,
                    DocumentType::TWO_BY_TWO_PICTURE,
                    DocumentType::OFFICE_MEMBERSHIP_FORM,
                ]
                : [
                    DocumentType::BIRTH_CERTIFICATE,
                    DocumentType::CEDULA,
                    DocumentType::TWO_BY_TWO_PICTURE,
                ],
            'renewal' => [],
            'reactivation' => [
                DocumentType::CEDULA,
                DocumentType::PREVIOUS_MEMBERSHIP_ID,
                DocumentType::PAYMENT_RECEIPT,
                DocumentType::OFFICE_MEMBERSHIP_FORM,
            ],
            default => throw new InvalidArgumentException("Unsupported workflow [{$workflow}]."),
        };
    }

    public function checklistRowsForApplication(MembershipApplication $application): array
    {
        $source = strtolower((string) $application->source);

        return $this->configuredChecklistRequirementsFor('application', $application)
            ->map(function (array $requirement) use ($application, $source): array {
            $type = $requirement['type'];
            $documentTypeId = DocumentTypeModel::query()->firstOrCreate(
                ['code' => $type->value],
                [
                    'name' => $type->label(),
                    'description' => $type->label(),
                    'status' => 'Active',
                ],
            )->id;

            return [
                'membership_transaction_id' => $application->id,
                'document_type_id' => $documentTypeId,
                'is_required' => (bool) $requirement['is_required'],
                'original_name' => $source === 'walk_in' ? $type->label() : 'Pending Upload',
                'file_path' => $source === 'walk_in' ? 'office-checklist/' . $type->value : 'pending-upload/' . $type->value,
                'verification_status' => DocumentVerificationStatus::PENDING,
                'remarks' => null,
                'uploaded_at' => now(),
            ];
        })->all();
    }

    public function checklistRowsForRenewal(RenewalRequest $renewalRequest): array
    {
        $source = strtolower((string) $renewalRequest->source);

        return $this->configuredChecklistRequirementsFor('renewal', $renewalRequest)
            ->map(function (array $requirement) use ($renewalRequest, $source): array {
                $type = $requirement['type'];
                $documentTypeId = DocumentTypeModel::query()->firstOrCreate(
                    ['code' => $type->value],
                    [
                        'name' => $type->label(),
                        'description' => $type->label(),
                        'status' => 'Active',
                    ],
                )->id;

                return [
                    'membership_transaction_id' => $renewalRequest->id,
                    'document_type_id' => $documentTypeId,
                    'is_required' => (bool) $requirement['is_required'],
                    'original_name' => $source === 'walk_in' ? $type->label() : 'Pending Upload',
                    'file_path' => $source === 'walk_in' ? 'office-checklist/' . $type->value : 'pending-upload/' . $type->value,
                    'verification_status' => DocumentVerificationStatus::PENDING,
                    'remarks' => null,
                    'uploaded_at' => now(),
                ];
            })->all();
    }

    public function checklistRowsForReactivation(ReactivationRequest $reactivationRequest): array
    {
        $source = strtolower((string) $reactivationRequest->source);

        return $this->configuredChecklistRequirementsFor('reactivation', $reactivationRequest)
            ->map(function (array $requirement) use ($reactivationRequest, $source): array {
                $type = $requirement['type'];
                $documentTypeId = DocumentTypeModel::query()->firstOrCreate(
                    ['code' => $type->value],
                    [
                        'name' => $type->label(),
                        'description' => $type->label(),
                        'status' => 'Active',
                    ],
                )->id;

                return [
                    'membership_transaction_id' => $reactivationRequest->id,
                    'document_type_id' => $documentTypeId,
                    'is_required' => (bool) $requirement['is_required'],
                    'original_name' => $source === 'walk_in' ? $type->label() : 'Pending Upload',
                    'file_path' => $source === 'walk_in' ? 'office-checklist/' . $type->value : 'pending-upload/' . $type->value,
                    'verification_status' => DocumentVerificationStatus::PENDING,
                    'remarks' => null,
                    'uploaded_at' => now(),
                ];
            })->all();
    }

    public function allRequiredVerified(string $workflow, iterable $submittedDocuments, array|object $context = []): bool
    {
        return $this->missingFor($workflow, $submittedDocuments, $context, true) === [];
    }

    public function missingFor(
        string $workflow,
        iterable $submittedDocuments,
        array|object $context = [],
        bool $verifiedOnly = true,
    ): array {
        $required = $this->requiredFor($workflow, $context);
        $submitted = [];

        foreach ($submittedDocuments as $document) {
            $type = null;
            if (is_object($document) && isset($document->document_type) && $document->document_type instanceof DocumentType) {
                $type = $document->document_type;
            }

            if ($type === null) {
                $normalized = $this->normalizeDocument($document);
                $rawTypeValue = $normalized['document_type'] ?? $normalized['type'] ?? null;
                $typeValue = is_array($rawTypeValue)
                    ? ($rawTypeValue['code'] ?? $rawTypeValue['value'] ?? null)
                    : $rawTypeValue;

                $typeValue ??= $this->resolveDocumentTypeValue($document, $normalized);
                $type = $typeValue instanceof DocumentType
                    ? $typeValue
                    : DocumentType::tryFrom((string) $typeValue);
            }

            if ($type === null) {
                continue;
            }

            if ($verifiedOnly) {
                $status = null;
                if (is_object($document) && isset($document->verification_status)) {
                    $status = $document->verification_status instanceof DocumentVerificationStatus
                        ? $document->verification_status
                        : DocumentVerificationStatus::tryFrom(strtolower((string) $document->verification_status));
                }

                if ($status === null) {
                    $normalized ??= $this->normalizeDocument($document);
                    $statusValue = $normalized['verification_status'] ?? null;
                    $status = $statusValue instanceof DocumentVerificationStatus
                        ? $statusValue
                        : DocumentVerificationStatus::tryFrom(strtolower((string) $statusValue));
                }

                if ($status !== DocumentVerificationStatus::VERIFIED) {
                    continue;
                }
            }

            $submitted[] = $type;
        }

        return array_values(array_filter(
            $required,
            fn (DocumentType $requiredType): bool => ! in_array($requiredType, $submitted, true),
        ));
    }

    private function normalizeDocument(array|object $document): array
    {
        if (is_array($document)) {
            return $document;
        }

        if (method_exists($document, 'toArray')) {
            return $document->toArray();
        }

        return get_object_vars($document);
    }

    private function resolveDocumentTypeValue(array|object $document, array $normalized): ?string
    {
        $documentType = null;

        if (
            is_object($document)
            && method_exists($document, 'documentType')
        ) {
            $documentTypeRecord = method_exists($document, 'relationLoaded') && $document->relationLoaded('documentType')
                ? $document->getRelation('documentType')
                : $document->documentType()->first(['id', 'code']);

            $documentType = $documentTypeRecord?->code;
        }

        if ($documentType) {
            return (string) $documentType;
        }

        $documentTypeId = $normalized['document_type_id'] ?? null;

        if (! $documentTypeId) {
            return null;
        }

        return DocumentTypeModel::query()
            ->whereKey($documentTypeId)
            ->value('code');
    }

    private function resolveSource(array|object $context = []): ?string
    {
        if (is_array($context)) {
            return isset($context['source']) ? strtolower((string) $context['source']) : null;
        }

        if ($context instanceof MembershipApplication) {
            return strtolower((string) $context->source);
        }

        if ($context instanceof RenewalRequest) {
            return strtolower((string) $context->source);
        }

        if ($context instanceof ReactivationRequest) {
            return strtolower((string) $context->source);
        }

        return isset($context->source) ? strtolower((string) $context->source) : null;
    }

    private function configuredRequirementsFor(string $workflow, ?string $source, bool $requiredOnly = true): ?Collection
    {
        $requirements = $this->configuredRequirementRecordsFor($workflow, $requiredOnly);

        return $requirements?->map(fn (DocumentRequirement $requirement) => DocumentType::tryFrom((string) $requirement->documentType?->code))
            ->filter()
            ->values();
    }

    private function configuredRequirementRecordsFor(string $workflow, bool $requiredOnly = true): ?Collection
    {
        try {
            $hasTable = Schema::hasTable('document_requirements');
        } catch (Throwable) {
            return null;
        }

        if (! $hasTable) {
            return null;
        }

        $transactionType = ucfirst(strtolower($workflow));

        $baseQuery = DocumentRequirement::query()
            ->where('transaction_type', $transactionType);

        if (! $baseQuery->exists()) {
            return null;
        }

        $requirements = $baseQuery
            ->where('is_active', true)
            ->when($requiredOnly, fn ($query) => $query->where('is_required', true))
            ->orderBy('id')
            ->with('documentType')
            ->get();

        return $requirements;
    }
}

