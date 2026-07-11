<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'membership_transaction_id',
        'fee_schedule_id',
        'total_amount_due',
        'membership_fee',
        'annual_due',
        'mortuary_fee',
        'status',
    ];

    protected $casts = [
        'total_amount_due' => 'decimal:2',
        'membership_fee' => 'decimal:2',
        'annual_due' => 'decimal:2',
        'mortuary_fee' => 'decimal:2',
    ];

    public function membershipTransaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class);
    }

    public function feeSchedule(): BelongsTo
    {
        return $this->belongsTo(FeeSchedule::class);
    }

    public function membershipApplication(): BelongsTo
    {
        return $this->belongsTo(MembershipApplication::class, 'membership_transaction_id');
    }

    public function renewalRequest(): BelongsTo
    {
        return $this->belongsTo(RenewalRequest::class, 'membership_transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?AssessmentStatus {
                $normalized = strtolower(str_replace(' ', '_', (string) $value));

                return AssessmentStatus::tryFrom($normalized);
            },
            set: function (mixed $value): string {
                $normalized = $value instanceof AssessmentStatus
                    ? $value->value
                    : strtolower(str_replace(' ', '_', (string) $value));

                return match ($normalized) {
                    AssessmentStatus::PAID->value, AssessmentStatus::OVERPAID->value, AssessmentStatus::WAIVED->value => 'Paid',
                    AssessmentStatus::PARTIALLY_PAID->value => 'Partially Paid',
                    default => 'Pending',
                };
            },
        );
    }

    public function getMembershipApplicationIdAttribute(): ?int
    {
        return $this->membershipTransaction?->transaction_type === 'Application'
            ? $this->membership_transaction_id
            : null;
    }

    public function getRenewalRequestIdAttribute(): ?int
    {
        return $this->membershipTransaction?->transaction_type === 'Renewal'
            ? $this->membership_transaction_id
            : null;
    }
}
