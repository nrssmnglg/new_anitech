<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class MortuaryClaim extends Model
{
    use HasEncryptedPublicRouteKey;
    use HasFactory;

    protected $fillable = [
        'membership_ledger_id',
        'claim_reference',
        'claimer_first_name',
        'claimer_middle_name',
        'claimer_last_name',
        'claimer_relationship',
        'claimer_contact_number',
        'claimer_address',
        'claim_amount',
        'claim_date',
        'status',
        'filed_by',
        'approved_by',
        'released_by',
        'remarks',
    ];

    protected $casts = [
        'claim_amount' => 'decimal:2',
        'claim_date' => 'date',
    ];

    public function membershipLedger(): BelongsTo
    {
        return $this->belongsTo(MembershipLedger::class);
    }

    public function filedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function releasedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(MortuaryClaimRequirement::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(MortuaryClaimDisbursement::class);
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => strtolower((string) $value),
            set: fn (mixed $value): string => match (strtolower((string) $value)) {
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'released' => 'Released',
                default => 'Pending',
            },
        );
    }

    public function getFarmerAttribute(): ?Farmer
    {
        $this->loadMissing('membershipLedger.membershipTransaction.farmer.profile', 'membershipLedger.membershipTransaction.farmer.memberType', 'membershipLedger.membershipTransaction.farmer.barangay', 'membershipLedger.membershipTransaction.farmer.association');

        return $this->membershipLedger?->farmer;
    }

    public function getClaimerNameAttribute(): string
    {
        return trim(collect([
            $this->claimer_first_name,
            $this->claimer_middle_name,
            $this->claimer_last_name,
        ])->filter()->implode(' '));
    }

    public function getClaimerValidIdReceivedAttribute(): bool
    {
        return $this->requirementReceived('valid_id');
    }

    public function getProofOfRelationshipReceivedAttribute(): bool
    {
        return $this->requirementReceived('proof_of_relationship');
    }

    public function getDeathCertificateReceivedAttribute(): bool
    {
        return $this->requirementReceived('death_certificate');
    }

    public function getDeathCertificatePathAttribute(): ?string
    {
        return null;
    }

    public function getDeathCertificateDiskAttribute(): ?string
    {
        return null;
    }

    public function getDeathCertificateOriginalNameAttribute(): ?string
    {
        return null;
    }

    public function getDeathCertificateMimeTypeAttribute(): ?string
    {
        return null;
    }

    private function requirementReceived(string $code): bool
    {
        $this->loadMissing('requirements.requirementType');

        return $this->requirements->contains(function (MortuaryClaimRequirement $requirement) use ($code): bool {
            return strtolower((string) $requirement->requirementType?->code) === $code
                && (bool) $requirement->is_received;
        });
    }
}
