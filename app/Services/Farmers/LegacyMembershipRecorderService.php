<?php

namespace App\Services\Farmers;

use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Models\MembershipTransaction;
use App\Models\RenewalRequest;
use App\Services\Membership\MembershipLedgerService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Payments\PaymentPostingService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyMembershipRecorderService
{
    public function __construct(
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly PaymentPostingService $paymentPostingService,
        private readonly MembershipLedgerService $membershipLedgerService,
    ) {
    }

    public function syncForOldRecord(Farmer $farmer, ?int $year = null, bool $existingFarmer = false, ?int $memberTypeId = null): Farmer
    {
        return DB::transaction(function () use ($farmer, $year, $existingFarmer, $memberTypeId): Farmer {
            // Serialize historical submissions for this farmer before checking coverage.
            $farmer = Farmer::query()->lockForUpdate()->findOrFail($farmer->id);
            $registeredAt = CarbonImmutable::parse($farmer->registered_at ?? $farmer->created_at);
            $year ??= $registeredAt->year;
            if ($year < $registeredAt->year || $year > now()->year || $year < 1900) {
                throw new DomainException('Historical year must be the registration year or later, up to the current year.');
            }

            $memberType = MemberType::query()->findOrFail($memberTypeId ?? $farmer->member_type_id);
            if (! in_array($memberType->code, ['OM', 'OSC', 'NM', 'NSC'], true)) {
                throw new DomainException('Select OM, OSC, NM, or NSC for historical encoding.');
            }
            if ($existingFarmer && ! in_array($memberType->code, ['OM', 'OSC'], true)) {
                throw new DomainException('An existing farmer renewal must use OM or OSC for the selected year.');
            }

            if (MembershipTransaction::query()->where('farmer_id', $farmer->id)
                ->where('year', $year)->whereIn('transaction_type', ['Application', 'Renewal'])->exists()) {
                throw new DomainException('An Application or Renewal already exists for this farmer and historical year. No duplicate was recorded.');
            }

            $isRenewal = $existingFarmer || in_array($memberType->code, ['OM', 'OSC'], true);
            $model = $isRenewal ? RenewalRequest::class : MembershipApplication::class;
            $recordedAt = CarbonImmutable::create($year, 2, 14)->startOfDay();
            $transaction = $model::query()->create([
                'farmer_id' => $farmer->id,
                'year' => $year,
                'source' => 'legacy',
                'application_no' => ($isRenewal ? 'REN-' : 'APP-') . $year . '-' . Str::upper(Str::random(8)),
                'status' => 'approved',
                'submitted_at' => $recordedAt,
                'reviewed_at' => $recordedAt,
                'is_late' => false,
            ]);
            $context = [
                'member_type_code' => $memberType->code,
                'member_type_id' => $memberType->id,
                'year' => $year,
                'require_exact_year' => true,
            ];
            $assessment = $isRenewal
                ? $this->paymentAssessmentService->createForRenewal($transaction, $context)
                : $this->paymentAssessmentService->createForApplication($transaction, $context);
            $summary = $this->paymentPostingService->record($assessment, [
                'payment_method' => 'cash',
                'amount_paid' => (float) $assessment->total_amount_due,
                'paid_at' => $recordedAt->toDateTimeString(),
                'reference_no' => 'LEGACY-' . ($isRenewal ? 'REN-' : 'APP-') . $farmer->farmer_code . '-' . $year,
            ])['summary'];

            MembershipLedger::query()->create($this->membershipLedgerService->buildFromSource(
                $isRenewal ? 'renewal' : 'application',
                ['id' => $transaction->id, 'status' => 'Active', 'year' => $year],
                [
                    'fee_schedule_id' => $assessment->fee_schedule_id,
                    'member_type' => $memberType->code,
                    'mortuary_eligible' => ! in_array($memberType->code, ['NSC', 'OSC'], true),
                ],
                $summary,
            ));

            // Historical coverage must not overwrite today's registry status or profile.
            return $farmer->fresh(['profile', 'memberType']);
        });
    }
}
