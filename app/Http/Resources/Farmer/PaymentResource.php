<?php

namespace App\Http\Resources\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paymentMethod = $this->resource->relationLoaded('paymentMethod')
            ? $this->resource->getRelation('paymentMethod')
            : null;
        $assessment = $this->resource->relationLoaded('paymentAssessment')
            ? $this->resource->getRelation('paymentAssessment')
            : null;
        $attachments = $this->resource->relationLoaded('attachments')
            ? $this->resource->getRelation('attachments')
            : collect();
        $transaction = $assessment && $assessment->relationLoaded('membershipTransaction')
            ? $assessment->getRelation('membershipTransaction')
            : null;

        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'status' => $this->status?->value ?? (string) $this->status,
            'status_label' => $this->status?->label() ?? ucfirst((string) ($this->status?->value ?? $this->status ?? 'pending')),
            'status_key' => $this->paymentStatusKey(),
            'amount_paid' => (float) $this->amount_paid,
            'membership_fee' => (float) $this->membership_fee,
            'annual_due' => (float) $this->annual_due,
            'mortuary_fee' => (float) $this->mortuary_fee,
            'paid_at' => optional($this->paid_at)->toIso8601String(),
            'verified_at' => optional($this->verified_at)->toIso8601String(),
            'mismatch_message' => $this->mismatchMessage($assessment),
            'payment_method' => $paymentMethod ? [
                'id' => $paymentMethod->id,
                'code' => $paymentMethod->code,
                'name' => $paymentMethod->name,
            ] : null,
            'proof_attachments' => AttachmentResource::collection($attachments),
            'assessment' => $assessment ? [
                'id' => $assessment->id,
                'total_amount_due' => (float) $assessment->total_amount_due,
                'status' => $assessment->status?->value ?? (string) $assessment->status,
                'status_label' => $assessment->status?->label() ?? ucfirst((string) ($assessment->status?->value ?? $assessment->status ?? 'pending')),
                'transaction_id' => $assessment->membership_transaction_id,
                'transaction_type' => $transaction?->transaction_type,
                'application_no' => $transaction?->application_no,
                'cycle_year' => $transaction?->year,
            ] : null,
            'cycle' => [
                'type' => strtolower((string) ($transaction?->transaction_type ?? 'payment')),
                'label' => $transaction?->transaction_type === 'Renewal'
                    ? 'Renewal ' . ($transaction?->year ?? '')
                    : ($transaction?->application_no ? 'Application ' . $transaction->application_no : 'Membership Application'),
                'reference' => $transaction?->transaction_type === 'Renewal'
                    ? (string) ($transaction?->year ?? '')
                    : (string) ($transaction?->application_no ?? ''),
            ],
        ];
    }

    private function paymentStatusKey(): string
    {
        $status = strtolower((string) ($this->status?->value ?? $this->status ?? 'pending'));

        return match ($status) {
            'paid', 'verified', 'overpaid', 'waived' => 'verified',
            'rejected', 'cancelled' => 'rejected',
            'partially_paid' => 'under_verification',
            default => 'submitted',
        };
    }

    private function mismatchMessage(mixed $assessment): ?string
    {
        if (! $assessment) {
            return null;
        }

        $due = (float) ($assessment->total_amount_due ?? 0);
        $paid = (float) $this->amount_paid;

        if ($paid <= 0 || abs($paid - $due) < 0.01) {
            return null;
        }

        return $paid > $due
            ? 'The submitted amount is higher than the assessed amount due and needs office review.'
            : 'The submitted amount is lower than the assessed amount due and needs office review.';
    }
}
