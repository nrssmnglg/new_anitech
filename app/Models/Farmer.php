<?php

namespace App\Models;

use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Concerns\HasPublicRouteKeyFallback;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Farmer extends Model
{
    use HasPublicRouteKeyFallback;
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'farmer_code';
    }

    protected $fillable = [
        'farmer_code',
        'barangay_id',
        'association_id',
        'member_type_id',
        'is_registry_record',
        'membership_status',
        'record_origin',
        'registered_at',
        'activated_at',
        'inactive_at',
        'inactive_reason',
    ];

    protected $casts = [
        'is_registry_record' => 'boolean',
        'registered_at' => 'datetime',
        'activated_at' => 'datetime',
        'inactive_at' => 'datetime',
    ];

    protected function membershipStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value): ?MembershipStatus {
                if ($value instanceof MembershipStatus) {
                    return $value;
                }

                if ($value === null || $value === '') {
                    return null;
                }

                return MembershipStatus::tryFrom(strtolower((string) $value));
            },
            set: function ($value): ?string {
                if ($value instanceof MembershipStatus) {
                    return $value->value;
                }

                if ($value === null || $value === '') {
                    return null;
                }

                return MembershipStatus::tryFrom(strtolower((string) $value))?->value ?? strtolower((string) $value);
            },
        );
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(FarmerProfile::class);
    }

    public function membershipTransactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }

    public function membershipApplications(): HasMany
    {
        return $this->hasMany(MembershipApplication::class);
    }

    public function renewalRequests(): HasMany
    {
        return $this->hasMany(RenewalRequest::class);
    }

    public function reactivationRequests(): HasMany
    {
        return $this->hasMany(ReactivationRequest::class);
    }

    public function farmerDocuments(): HasMany
    {
        return $this->hasMany(FarmerDocument::class);
    }

    public function membershipLedgers(): HasManyThrough
    {
        return $this->hasManyThrough(
            MembershipLedger::class,
            MembershipTransaction::class,
            'farmer_id',
            'membership_transaction_id',
            'id',
            'id',
        );
    }

    public function queries(): HasMany
    {
        return $this->hasMany(Query::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function paymentAssessments(): HasManyThrough
    {
        return $this->hasManyThrough(
            PaymentAssessment::class,
            MembershipTransaction::class,
            'farmer_id',
            'membership_transaction_id',
            'id',
            'id',
        );
    }

    public function internalNotes(): MorphMany
    {
        return $this->morphMany(InternalNote::class, 'noteable')->latest('created_at');
    }

    public function getFullNameAttribute(): string
    {
        $profile = $this->profile;

        if (! $profile) {
            return $this->farmer_code;
        }

        return trim(collect([
            $profile->first_name,
            $profile->middle_name,
            $profile->last_name,
            $profile->suffix,
        ])->filter()->implode(' '));
    }

    public function getStatusAttribute(): FarmerStatus
    {
        $inactiveReason = trim((string) $this->inactive_reason);

        if ($inactiveReason !== '') {
            return str_contains(strtolower($inactiveReason), 'deceas')
                ? FarmerStatus::DECEASED
                : FarmerStatus::INACTIVE;
        }

        return $this->membership_status === MembershipStatus::ACTIVE
            ? FarmerStatus::ACTIVE
            : FarmerStatus::PENDING;
    }

    public function getIsRegistryRecordAttribute(): bool
    {
        $value = $this->getAttributeFromArray('is_registry_record');

        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }
}
