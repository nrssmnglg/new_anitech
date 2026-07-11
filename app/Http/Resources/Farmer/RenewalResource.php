<?php

namespace App\Http\Resources\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RenewalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assessment = $this->paymentAssessments->sortByDesc('id')->first();
        $latestPayment = $assessment?->payments?->sortByDesc('id')->first();
        $documents = $this->whenLoaded('documents');
        $feeSchedule = $assessment?->feeSchedule;
        $assessmentSettled = $assessment
            ? in_array($assessment->status?->value ?? (string) $assessment->status, ['paid', 'overpaid', 'waived'], true)
            : false;
        $statusValue = $assessmentSettled
            ? 'completed'
            : ($this->status?->value ?? (string) $this->status);
        $statusLabel = $assessmentSettled
            ? 'Completed'
            : ($this->status?->label() ?? ucfirst((string) $this->status));

        return [
            'id' => $this->getRouteKey(),
            'application_no' => $this->application_no,
            'year' => $this->year,
            'status' => $statusValue,
            'status_label' => $statusLabel,
            'source' => $this->source,
            'submitted_at' => optional($this->submitted_at)->toIso8601String(),
            'reviewed_at' => optional($this->reviewed_at)->toIso8601String(),
            'rejected_at' => optional($this->rejected_at)->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'timeline' => [
                'submitted' => (bool) $this->submitted_at,
                'under_review' => (bool) $this->submitted_at,
                'needs_correction' => filled($this->rejection_reason),
                'approved' => $statusValue === 'approved',
                'completed' => $assessmentSettled,
            ],
            'is_late' => (bool) $this->is_late,
            'deadline' => optional($feeSchedule?->renewal_deadline)->toIso8601String(),
            'assessment' => $assessment ? [
                'id' => $assessment->id,
                'total_amount_due' => (float) $assessment->total_amount_due,
                'membership_fee' => (float) $assessment->membership_fee,
                'annual_due' => (float) $assessment->annual_due,
                'mortuary_fee' => (float) $assessment->mortuary_fee,
                'status' => $assessment->status?->value ?? (string) $assessment->status,
                'status_label' => $assessment->status?->label() ?? ucfirst((string) $assessment->status),
                'payments_count' => $assessment->payments->count(),
            ] : null,
            'latest_payment' => $latestPayment ? [
                'id' => $latestPayment->id,
                'reference_no' => $latestPayment->reference_no,
                'amount_paid' => (float) $latestPayment->amount_paid,
                'paid_at' => optional($latestPayment->paid_at)->toIso8601String(),
                'status' => $latestPayment->status?->value ?? (string) $latestPayment->status,
                'status_label' => $latestPayment->status?->label() ?? ucfirst((string) $latestPayment->status),
            ] : null,
            'checklist' => $documents ? $documents->map(fn ($document) => [
                'type' => $document->document_type?->value ?? (string) $document->document_type,
                'label' => $document->document_type?->label() ?? $document->documentType?->name ?? 'Document',
                'uploaded' => filled($document->file_path ?? $document->path ?? null),
                'verification_status' => $document->verification_status?->value ?? (string) $document->verification_status,
                'verification_status_label' => $document->verification_status?->label() ?? ucfirst((string) $document->verification_status),
                'remarks' => $document->remarks,
            ])->values() : [],
        ];
    }
}
