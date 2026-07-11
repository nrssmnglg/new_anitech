<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MembershipLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'membership_transaction_id',
        'fee_schedule_id',
        'year',
        'amount_paid',
        'payment_status',
        'paid_at',
        'mortuary_eligible',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
        'mortuary_eligible' => 'boolean',
    ];

    public function membershipTransaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class);
    }

    public function feeSchedule(): BelongsTo
    {
        return $this->belongsTo(FeeSchedule::class);
    }

    public function mortuaryClaims(): HasMany
    {
        return $this->hasMany(MortuaryClaim::class);
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => strtolower(str_replace(' ', '_', (string) $value)),
            set: fn (mixed $value): string => match (strtolower(str_replace(' ', '_', (string) $value))) {
                'paid' => 'Paid',
                'overpaid' => 'Overpaid',
                'waived' => 'Waived',
                'partially_paid' => 'Partial',
                default => 'Unpaid',
            },
        );
    }

    public function getFarmerIdAttribute(): ?int
    {
        return $this->membershipTransaction?->farmer_id;
    }

    public function getFarmerAttribute(): ?Farmer
    {
        $this->loadMissing('membershipTransaction.farmer.profile', 'membershipTransaction.farmer.memberType', 'membershipTransaction.farmer.barangay', 'membershipTransaction.farmer.association');

        return $this->membershipTransaction?->farmer;
    }

    public function getMemberTypeSnapshotAttribute(): ?string
    {
        return $this->farmer?->memberType?->code;
    }

    public function getMortuaryFeeAttribute(): float
    {
        $assessment = $this->latestAssessment();

        return round((float) ($assessment?->mortuary_fee ?? 0), 2);
    }

    public function getTotalAmountDueAttribute(): float
    {
        $assessment = $this->latestAssessment();

        return round((float) ($assessment?->total_amount_due ?? 0), 2);
    }

    private function latestAssessment(): mixed
    {
        $this->loadMissing('membershipTransaction.paymentAssessments');

        $assessments = $this->membershipTransaction?->paymentAssessments;

        if (! $assessments instanceof Collection) {
            return null;
        }

        return $assessments->sortByDesc('id')->first();
    }
}
