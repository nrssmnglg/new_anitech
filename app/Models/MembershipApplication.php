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

    protected ?string $capturedRejectionDetails = null;

    public function setRejectionDetailsAttribute(?string $value): void
    {
        $this->capturedRejectionDetails = $value;
    }

    public function getRejectionDetailsAttribute(): ?string
    {
        if ($this->capturedRejectionDetails !== null) {
            return $this->capturedRejectionDetails;
        }

        $docRemarks = $this->documents()->where('verification_status', 'Rejected')->latest('updated_at')->value('remarks');
        if (filled($docRemarks)) {
            return $docRemarks;
        }

        $auditMetadata = AuditLog::query()
            ->where('subject_type', $this->getMorphClass())
            ->where('subject_id', $this->getKey())
            ->where('action', 'application_rejected')
            ->latest('created_at')
            ->value('metadata');

        if (is_array($auditMetadata) && filled($auditMetadata['details'] ?? null)) {
            return $auditMetadata['details'];
        }

        return null;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('application_only', function (Builder $builder): void {
            $builder->where('transaction_type', 'Application');
        });

        static::creating(function (self $application): void {
            $application->transaction_type = 'Application';
        });

        static::created(function (self $application): void {
            if ($application->capturedRejectionDetails) {
                $userId = $application->reviewed_by ?? \Illuminate\Support\Facades\Auth::id() ?? \App\Models\User::query()->value('id');
                if ($userId) {
                    $application->internalNotes()->create([
                        'body' => $application->capturedRejectionDetails,
                        'created_by' => $userId,
                    ]);
                }
            }
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

    public function getRemarksAttribute(): ?string
    {
        return $this->internalNotes()->latest()->value('body');
    }

    public function getRejectionReasonLabelAttribute(): ?string
    {
        if ($this->rejection_reason instanceof \App\Enums\MembershipApplicationRejectionReason) {
            return $this->rejection_reason->label();
        }

        $raw = $this->rejection_reason ? (string) $this->rejection_reason : null;

        return $raw ? str($raw)->replace('_', ' ')->title()->value() : null;
    }

    public function getEffectiveRejectionDetailsAttribute(): ?string
    {
        $label = $this->rejection_reason instanceof \App\Enums\MembershipApplicationRejectionReason
            ? $this->rejection_reason->label()
            : ($this->rejection_reason ? str((string) $this->rejection_reason)->replace('_', ' ')->title()->value() : null);

        return $this->capturedRejectionDetails
            ?? $this->getAttribute('rejection_details')
            ?? $this->internalNotes()->latest()->value('body')
            ?? $this->remarks
            ?? $label;
    }
}
