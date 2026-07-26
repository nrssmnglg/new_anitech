<?php

namespace App\Models;

use App\Enums\ReactivationStatus;
use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReactivationRequest extends MembershipTransaction
{
    use HasEncryptedPublicRouteKey;

    protected $table = 'membership_transactions';

    protected static function booted(): void
    {
        static::addGlobalScope('reactivation_only', function (Builder $builder): void {
            $builder->where('transaction_type', 'Reactivation');
        });

        static::creating(function (self $reactivation): void {
            $reactivation->transaction_type = 'Reactivation';
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
            get: function (mixed $value): ?ReactivationStatus {
                $normalized = strtolower((string) $value);

                return match ($normalized) {
                    'pending' => ReactivationStatus::SUBMITTED,
                    'approved' => ReactivationStatus::APPROVED,
                    'rejected' => ReactivationStatus::REJECTED,
                    default => ReactivationStatus::tryFrom($normalized),
                };
            },
            set: function (mixed $value): string {
                $normalized = $value instanceof ReactivationStatus
                    ? $value->value
                    : strtolower((string) $value);

                return match ($normalized) {
                    ReactivationStatus::APPROVED->value, ReactivationStatus::COMPLETED->value => 'Approved',
                    ReactivationStatus::REJECTED->value, ReactivationStatus::CANCELLED->value => 'Rejected',
                    default => 'Pending',
                };
            },
        );
    }
}
