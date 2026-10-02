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
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birth_date',
        'sex',
        'civil_status',
        'mobile_number',
        'address',
        'email',
        'remarks',
    ];

    protected array $profileAttributes = [];

    protected static function booted(): void
    {
        static::saving(function (Farmer $farmer): void {
            $profileKeys = ['first_name', 'middle_name', 'last_name', 'suffix', 'sex', 'civil_status', 'birth_date', 'address', 'mobile_number', 'email', 'remarks'];
            foreach ($profileKeys as $key) {
                if (array_key_exists($key, $farmer->attributes)) {
                    $farmer->profileAttributes[$key] = $farmer->attributes[$key];
                    unset($farmer->attributes[$key]);
                }
            }
        });

        static::saved(function (Farmer $farmer): void {
            if (! empty($farmer->profileAttributes)) {
                $email = $farmer->profileAttributes['email'] ?? null;
                $remarks = $farmer->profileAttributes['remarks'] ?? null;
                unset($farmer->profileAttributes['email'], $farmer->profileAttributes['remarks']);

                if (isset($farmer->profileAttributes['sex'])) {
                    $farmer->profileAttributes['sex'] = ucfirst(strtolower((string) $farmer->profileAttributes['sex']));
                } else {
                    $farmer->profileAttributes['sex'] = 'Male';
                }

                if (isset($farmer->profileAttributes['civil_status'])) {
                    $farmer->profileAttributes['civil_status'] = ucfirst(strtolower((string) $farmer->profileAttributes['civil_status']));
                } else {
                    $farmer->profileAttributes['civil_status'] = 'Single';
                }

                $farmer->profile()->updateOrCreate([], $farmer->profileAttributes);

                if ($email) {
                    $user = $farmer->users()->first();
                    if ($user) {
                        $user->update(['email' => $email]);
                    } else {
                        $farmer->users()->create([
                            'name' => $farmer->full_name ?: 'Farmer',
                            'email' => $email,
                            'password' => bcrypt('password'),
                        ]);
                    }
                }

                if ($remarks) {
                    $userId = \Illuminate\Support\Facades\Auth::id() ?? User::query()->value('id');
                    if ($userId) {
                        $farmer->internalNotes()->create([
                            'body' => $remarks,
                            'created_by' => $userId,
                        ]);
                    }
                }

                $farmer->load(['profile', 'users', 'internalNotes']);
                $farmer->profileAttributes = [];
            }
        });
    }

    public function getEmailAttribute(): ?string
    {
        return $this->users()->first()?->email
            ?? ($this->profileAttributes['email'] ?? null);
    }

    public function getRemarksAttribute(): ?string
    {
        return $this->internalNotes()->latest()->value('body')
            ?? ($this->profileAttributes['remarks'] ?? null);
    }

    public function getFirstNameAttribute(): ?string
    {
        return $this->profile?->first_name ?? ($this->profileAttributes['first_name'] ?? null);
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->profile?->last_name ?? ($this->profileAttributes['last_name'] ?? null);
    }

    public function getMiddleNameAttribute(): ?string
    {
        return $this->profile?->middle_name ?? ($this->profileAttributes['middle_name'] ?? null);
    }

    public function getSuffixAttribute(): ?string
    {
        return $this->profile?->suffix ?? ($this->profileAttributes['suffix'] ?? null);
    }

    public function getMobileNumberAttribute(): ?string
    {
        return $this->profile?->mobile_number ?? ($this->profileAttributes['mobile_number'] ?? null);
    }

    public function getAddressAttribute(): ?string
    {
        return $this->profile?->address ?? ($this->profileAttributes['address'] ?? null);
    }

    public function getBirthDateAttribute(): mixed
    {
        return $this->profile?->birth_date ?? ($this->profileAttributes['birth_date'] ?? null);
    }

    public function getSexAttribute(): ?string
    {
        return $this->profile?->sex ?? ($this->profileAttributes['sex'] ?? null);
    }

    public function getCivilStatusAttribute(): ?string
    {
        return $this->profile?->civil_status ?? ($this->profileAttributes['civil_status'] ?? null);
    }

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

        if (str_contains(strtolower($inactiveReason), 'deceas')) {
            return FarmerStatus::DECEASED;
        }

        if ($this->inactive_at !== null || $inactiveReason !== '') {
            return FarmerStatus::INACTIVE;
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
