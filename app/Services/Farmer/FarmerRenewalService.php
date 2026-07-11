<?php

namespace App\Services\Farmer;

use App\Enums\AssessmentStatus;
use App\Models\Farmer;
use App\Models\FeeSchedule;
use App\Models\RenewalRequest;
use App\Services\Documents\FarmerDocumentService;
use App\Services\Membership\RenewalRequestService;
use App\Services\Payments\PaymentAssessmentService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FarmerRenewalService
{
    public function __construct(
        private readonly FarmerDocumentService $farmerDocumentService,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly RenewalRequestService $renewalRequestService,
    ) {
    }

    public function list(Farmer $farmer, int $perPage = 10): LengthAwarePaginator
    {
        return $farmer->renewalRequests()
            ->with(['documents.documentType', 'paymentAssessments.payments', 'paymentAssessments.feeSchedule'])
            ->latest('year')
            ->latest('id')
            ->paginate($perPage);
    }

    public function eligibility(Farmer $farmer, ?int $year = null): array
    {
        $targetYear = $year ?: now()->year;
        $schedule = $this->feeScheduleFor($farmer);
        $deadline = $schedule?->renewal_deadline
            ? CarbonImmutable::parse($schedule->renewal_deadline)
            : CarbonImmutable::create($targetYear, 12, 31, 23, 59, 59);

        $renewal = $farmer->renewalRequests()
            ->with(['documents.documentType', 'paymentAssessments.payments', 'paymentAssessments.feeSchedule'])
            ->where('year', $targetYear)
            ->latest('id')
            ->first();

        if ($renewal) {
            $checklist = $this->checklistSummary($renewal);
            $assessment = $renewal->paymentAssessments->sortByDesc('id')->first();
            $isPaid = $assessment && $this->isAssessmentSettled($assessment->status?->value ?? (string) $assessment->status);

            return [
                'year' => $targetYear,
                'deadline' => $deadline->toIso8601String(),
                'deadline_soon' => now()->diffInDays($deadline, false) <= 30,
                'state' => $isPaid ? 'already_renewed' : 'in_progress',
                'message' => $isPaid
                    ? 'Your renewal for this year is already paid or completed.'
                    : 'A renewal for this year is already started. Continue this same renewal instead of creating a new one.',
                'can_start' => false,
                'can_resume' => true,
                'renewal_id' => $renewal->getRouteKey(),
                'application_no' => $renewal->application_no,
                'checklist' => $checklist,
                'fees' => $this->feeBreakdown($renewal, $schedule),
                'phase' => $this->phaseLabel($renewal, $assessment),
            ];
        }

        return [
            'year' => $targetYear,
            'deadline' => $deadline->toIso8601String(),
            'deadline_soon' => now()->diffInDays($deadline, false) <= 30,
            'state' => 'eligible',
            'message' => 'You are eligible to start your renewal for this year.',
            'can_start' => true,
            'can_resume' => false,
            'renewal_id' => null,
            'application_no' => null,
            'checklist' => [],
            'fees' => $this->feeBreakdown(null, $schedule),
            'phase' => 'Ready to start',
        ];
    }

    public function startOrResume(Farmer $farmer, ?int $year = null): RenewalRequest
    {
        $targetYear = $year ?: now()->year;

        $existing = $farmer->renewalRequests()
            ->with(['documents.documentType', 'paymentAssessments.payments', 'paymentAssessments.feeSchedule'])
            ->where('year', $targetYear)
            ->latest('id')
            ->first();

        if ($existing) {
            $this->farmerDocumentService->ensureRenewalChecklist($existing);

            return $existing->refresh()->load(['documents.documentType', 'paymentAssessments.payments', 'paymentAssessments.feeSchedule']);
        }

        $renewal = $this->renewalRequestService->create([
            'farmer_id' => $farmer->id,
            'year' => $targetYear,
            'source' => 'mobile',
        ]);

        $this->farmerDocumentService->ensureRenewalChecklist($renewal);

        return $renewal->refresh()->load(['documents.documentType', 'paymentAssessments.payments', 'paymentAssessments.feeSchedule']);
    }

    private function checklistSummary(RenewalRequest $renewal): array
    {
        $documents = $this->farmerDocumentService->ensureRenewalChecklist($renewal);

        return $documents->map(function ($document): array {
            $status = $document->verification_status?->value ?? (string) $document->verification_status;

            return [
                'type' => $document->document_type?->value ?? (string) $document->document_type,
                'label' => $document->document_type?->label() ?? $document->documentType?->name ?? 'Document',
                'uploaded' => $this->farmerDocumentService->uploadPresent($document),
                'needs_resubmission' => $this->farmerDocumentService->requiresResubmission($document),
                'verification_status' => $status,
                'verification_status_label' => $document->verification_status?->label() ?? ucfirst((string) $status),
                'notes' => $this->farmerDocumentService->validationNotes($document),
            ];
        })->values()->all();
    }

    private function feeBreakdown(?RenewalRequest $renewal, ?FeeSchedule $schedule): array
    {
        $assessment = $renewal?->paymentAssessments?->sortByDesc('id')->first();

        return [
            'annual_due' => (float) ($assessment?->annual_due ?? 0),
            'mortuary_fee' => (float) ($assessment?->mortuary_fee ?? $schedule?->mortuary_fee ?? 0),
            'membership_fee' => (float) ($assessment?->membership_fee ?? $schedule?->membership_fee ?? 0),
            'total_due' => (float) ($assessment?->total_amount_due ?? (($schedule?->mortuary_fee ?? 0) + ($schedule?->membership_fee ?? 0))),
            'status' => $assessment?->status?->value ?? (string) ($assessment?->status ?? 'pending'),
            'status_label' => $assessment?->status?->label() ?? ucfirst((string) ($assessment?->status?->value ?? $assessment?->status ?? 'pending')),
        ];
    }

    private function phaseLabel(RenewalRequest $renewal, mixed $assessment): string
    {
        if ($assessment && $this->isAssessmentSettled($assessment->status?->value ?? (string) $assessment->status)) {
            return 'Completed';
        }

        $status = $renewal->status?->value ?? (string) $renewal->status;

        return match ($status) {
            'approved' => 'Waiting for payment',
            'rejected' => 'Needs correction',
            'under_review' => 'Waiting for staff review',
            default => 'Renewal submitted',
        };
    }

    private function feeScheduleFor(Farmer $farmer): ?FeeSchedule
    {
        try {
            return $this->paymentAssessmentService->resolveFeeSchedule($farmer->member_type_id);
        } catch (DomainException) {
            return null;
        }
    }

    private function isAssessmentSettled(?string $status): bool
    {
        return in_array(strtolower((string) $status), [
            AssessmentStatus::PAID->value,
            AssessmentStatus::OVERPAID->value,
            AssessmentStatus::WAIVED->value,
        ], true);
    }
}
