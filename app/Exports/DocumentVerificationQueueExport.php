<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentVerificationQueueExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $documents,
        private readonly array $columns,
    ) {
    }

    public static function availableColumns(): array
    {
        return [
            'workflow' => 'Workflow',
            'farmer_name' => 'Farmer Name',
            'farmer_code' => 'Farmer Code',
            'document_label' => 'Document',
            'reference_label' => 'Reference',
            'source_label' => 'Source',
            'status' => 'Status',
            'flag' => 'Flag',
            'uploaded_at' => 'Uploaded At',
            'verified_at' => 'Verified At',
            'expires_at' => 'Expires At',
            'verifier_name' => 'Verifier',
            'remarks' => 'Remarks',
            'validation_notes' => 'Validation Notes',
        ];
    }

    public function collection(): Collection
    {
        return $this->documents;
    }

    public function headings(): array
    {
        return array_values(array_intersect_key(self::availableColumns(), array_flip($this->columns)));
    }

    public function map($document): array
    {
        return collect($this->columns)->map(fn (string $column): string => match ($column) {
            'workflow' => (string) ($document['workflowLabel'] ?? ''),
            'farmer_name' => (string) ($document['farmerName'] ?? ''),
            'farmer_code' => (string) ($document['farmerCode'] ?? ''),
            'document_label' => (string) ($document['documentLabel'] ?? ''),
            'reference_label' => (string) ($document['referenceLabel'] ?? ''),
            'source_label' => (string) ($document['sourceLabel'] ?? ''),
            'status' => (string) ($document['status']['label'] ?? ''),
            'flag' => (string) $this->flagLabel($document),
            'uploaded_at' => (string) ($document['uploadedAt'] ?? ''),
            'verified_at' => (string) ($document['verifiedAt'] ?? ''),
            'expires_at' => (string) ($document['expiresAtLabel'] ?? ''),
            'verifier_name' => (string) ($document['verifierName'] ?? ''),
            'remarks' => (string) ($document['remarks'] ?? ''),
            'validation_notes' => (string) implode(' | ', $document['validationNotes'] ?? []),
            default => '',
        })->all();
    }

    private function flagLabel(array $document): string
    {
        if (($document['needsResubmission'] ?? false) === true) {
            return 'Re-submission';
        }

        if (($document['isExpired'] ?? false) === true) {
            return 'Expired';
        }

        if (($document['uploadPresent'] ?? true) === false) {
            return 'Missing';
        }

        if (($document['readyForVerification'] ?? false) === true) {
            return 'Ready';
        }

        return 'Waiting';
    }
}
