<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\HasPublicRouteKeyFallback;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipApplication extends MembershipTransaction
{
    use HasPublicRouteKeyFallback;

    protected $table = 'membership_transactions';

    public function getRouteKeyName(): string
    {
        return 'application_no';
    }

    protected static function booted(): void
    {
        static::addGlobalScope('application_only', function (Builder $builder): void {
            $builder->where('transaction_type', 'Application');
        });

        static::creating(function (self $application): void {
            $application->transaction_type = 'Application';
        });
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FarmerDocument::class, 'membership_transaction_id');
    }

    public function paymentAssessments(): HasMany
    {
        return $this->hasMany(PaymentAssessment::class, 'membership_transaction_id');
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?ApplicationStatus {
                $normalized = strtolower((string) $value);

                return match ($normalized) {
                    'pending' => ApplicationStatus::SUBMITTED,
                    'approved' => ApplicationStatus::APPROVED,
                    'rejected' => ApplicationStatus::REJECTED,
                    default => ApplicationStatus::tryFrom($normalized),
                };
            },
            set: function (mixed $value): string {
                $normalized = $value instanceof ApplicationStatus
                    ? $value->value
                    : strtolower((string) $value);

                return match ($normalized) {
                    ApplicationStatus::APPROVED->value => 'Approved',
                    ApplicationStatus::REJECTED->value => 'Rejected',
                    default => 'Pending',
                };
            },
        );
    }

    public function getRejectionReasonLabelAttribute(): ?string
    {
        return $this->rejection_reason ? str($this->rejection_reason)->replace('_', ' ')->title()->value() : null;
    }

    public function getEffectiveRejectionDetailsAttribute(): ?string
    {
        return $this->rejection_reason;
    }
}
