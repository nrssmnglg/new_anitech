<?php

namespace App\Models;

use App\Enums\RenewalStatus;
use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RenewalRequest extends MembershipTransaction
{
    use HasEncryptedPublicRouteKey;

    protected $table = 'membership_transactions';

    protected static function booted(): void
    {
        static::addGlobalScope('renewal_only', function (Builder $builder): void {
            $builder->where('transaction_type', 'Renewal');
        });

        static::creating(function (self $renewal): void {
            $renewal->transaction_type = 'Renewal';
        });
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
            get: function (mixed $value): ?RenewalStatus {
                $normalized = strtolower((string) $value);

                return match ($normalized) {
                    'pending' => RenewalStatus::SUBMITTED,
                    'approved' => RenewalStatus::APPROVED,
                    'rejected' => RenewalStatus::REJECTED,
                    default => RenewalStatus::tryFrom($normalized),
                };
            },
            set: function (mixed $value): string {
                $normalized = $value instanceof RenewalStatus
                    ? $value->value
                    : strtolower((string) $value);

                return match ($normalized) {
                    RenewalStatus::APPROVED->value, RenewalStatus::COMPLETED->value => 'Approved',
                    RenewalStatus::REJECTED->value, RenewalStatus::CANCELLED->value => 'Rejected',
                    default => 'Pending',
                };
            },
        );
    }
}
