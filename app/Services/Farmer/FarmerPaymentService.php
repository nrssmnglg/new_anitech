<?php

namespace App\Services\Farmer;

use App\Enums\AssessmentStatus;
use App\Models\Farmer;
use App\Models\MembershipApplication;
use App\Models\Payment;
use App\Models\PaymentAssessment;
use App\Models\RenewalRequest;
use App\Services\Payments\PaymentAssessmentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FarmerPaymentService
{
    public function __construct(
        private readonly PaymentAssessmentService $paymentAssessmentService,
    ) {
    }

    public function list(Farmer $farmer, int $perPage = 10): LengthAwarePaginator
    {
        return Payment::query()
            ->whereHas('paymentAssessment.membershipTransaction', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->with(['paymentMethod', 'paymentAssessment.membershipTransaction', 'attachments'])
            ->latest('paid_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function summary(Farmer $farmer): array
    {
        $current = $this->currentPaymentContext($farmer);
        $history = Payment::query()
            ->whereHas('paymentAssessment.membershipTransaction', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->with(['paymentAssessment.membershipTransaction'])
            ->latest('paid_at')
            ->latest('id')
            ->get();

        return [
            'total_records' => $history->count(),
            'verified' => $history->filter(fn (Payment $payment) => in_array(strtolower((string) ($payment->status?->value ?? $payment->status)), ['verified', 'paid', 'overpaid', 'waived'], true))->count(),
            'under_verification' => $history->filter(fn (Payment $payment) => strtolower((string) ($payment->status?->value ?? $payment->status)) === 'partially_paid')->count(),
            'rejected' => $history->filter(fn (Payment $payment) => strtolower((string) ($payment->status?->value ?? $payment->status)) === 'rejected')->count(),
            'current' => $current,
        ];
    }

    public function currentPaymentContext(Farmer $farmer): ?array
    {
        $assessment = PaymentAssessment::query()
            ->whereHas('membershipTransaction', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->with(['membershipTransaction', 'payments.paymentMethod', 'payments.attachments'])
            ->latest('id')
            ->get()
            ->first(fn (PaymentAssessment $assessment) => ! in_array(strtolower((string) ($assessment->status?->value ?? $assessment->status)), [
                AssessmentStatus::PAID->value,
                AssessmentStatus::OVERPAID->value,
                AssessmentStatus::WAIVED->value,
            ], true));

        if (! $assessment) {
            return null;
        }

        $transaction = $assessment->membershipTransaction;
        $latestPayment = $assessment->payments->sortByDesc('id')->first();
        $requiresProof = $latestPayment !== null && strtolower((string) ($latestPayment->status?->value ?? $latestPayment->status)) !== 'verified';

        return [
            'assessment_id' => $assessment->id,
            'source_id' => $transaction?->getKey(),
            'transaction_type' => strtolower((string) ($transaction?->transaction_type ?? 'payment')),
            'transaction_label' => $transaction?->transaction_type === 'Renewal'
                ? 'Renewal ' . ($transaction?->year ?? '')
                : 'Membership Application',
            'cycle_reference' => $transaction?->transaction_type === 'Renewal'
                ? (string) ($transaction?->year ?? '')
                : (string) ($transaction?->application_no ?? ''),
            'amount_due' => (float) $assessment->total_amount_due,
            'fee_breakdown' => [
                'membership_fee' => (float) ($assessment->membership_fee ?? 0),
                'annual_due' => (float) ($assessment->annual_due ?? 0),
                'mortuary_fee' => (float) ($assessment->mortuary_fee ?? 0),
            ],
            'payment_method' => 'QR payment / office verification',
            'instructions' => [
                'Review the total amount due and the exact fee breakdown before paying.',
                'Keep your payment reference number so you can recheck it later.',
                'If staff asks for proof, upload it directly to the matching transaction.',
                'Wait for payment verification before assuming the cycle is completed.',
            ],
            'status_key' => $this->readinessStatusKey($assessment, $latestPayment),
            'status_label' => $this->readinessStatusLabel($assessment, $latestPayment),
            'reference_no' => $latestPayment?->reference_no ?? ($transaction?->application_no ?? null),
            'latest_payment_id' => $latestPayment?->id,
            'requires_proof' => $requiresProof,
            'mismatch_message' => $latestPayment ? $this->paymentMismatchMessage($latestPayment, $assessment) : null,
            'proof_attachments' => $latestPayment?->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'url' => Storage::disk('public')->url($attachment->file_path),
                'uploaded_at' => optional($attachment->uploaded_at)->toIso8601String(),
            ])->values()->all() ?? [],
        ];
    }

    public function uploadProof(Farmer $farmer, int $paymentId, UploadedFile $file): Payment
    {
        $payment = Payment::query()
            ->whereKey($paymentId)
            ->whereHas('paymentAssessment.membershipTransaction', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->with(['paymentAssessment.membershipTransaction', 'attachments', 'paymentMethod'])
            ->firstOrFail();

        if (! in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'pdf'], true)) {
            throw ValidationException::withMessages([
                'proof' => 'Payment proof must be a JPG, PNG, or PDF file.',
            ]);
        }

        $path = $file->store('payments/' . $payment->id . '/proofs', 'public');

        $payment->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'uploaded_at' => now(),
        ]);

        return $payment->refresh()->load(['paymentAssessment.membershipTransaction', 'attachments', 'paymentMethod']);
    }

    private function readinessStatusKey(PaymentAssessment $assessment, ?Payment $payment): string
    {
        $assessmentStatus = strtolower((string) ($assessment->status?->value ?? $assessment->status ?? 'pending'));
        $paymentStatus = strtolower((string) ($payment?->status?->value ?? $payment?->status ?? ''));

        if (in_array($assessmentStatus, ['paid', 'overpaid', 'waived'], true) || in_array($paymentStatus, ['verified', 'paid', 'overpaid', 'waived'], true)) {
            return 'verified';
        }

        if ($paymentStatus === 'rejected') {
            return 'rejected';
        }

        if ($paymentStatus === 'partially_paid') {
            return 'under_verification';
        }

        if ($payment) {
            return 'submitted';
        }

        return 'not_yet_paid';
    }

    private function readinessStatusLabel(PaymentAssessment $assessment, ?Payment $payment): string
    {
        return match ($this->readinessStatusKey($assessment, $payment)) {
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            'under_verification' => 'Under Verification',
            'submitted' => 'Submitted',
            default => 'Not Yet Paid',
        };
    }

    private function paymentMismatchMessage(Payment $payment, PaymentAssessment $assessment): ?string
    {
        $due = (float) $assessment->total_amount_due;
        $paid = (float) $payment->amount_paid;

        if ($paid <= 0 || abs($paid - $due) < 0.01) {
            return null;
        }

        return $paid > $due
            ? 'The submitted amount is higher than the expected amount due.'
            : 'The submitted amount is lower than the expected amount due.';
    }
}
