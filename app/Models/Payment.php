<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_assessment_id',
        'payment_method_id',
        'amount_paid',
        'membership_fee',
        'annual_due',
        'mortuary_fee',
        'reference_no',
        'paid_at',
        'verified_by',
        'verified_at',
        'status',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'membership_fee' => 'decimal:2',
        'annual_due' => 'decimal:2',
        'mortuary_fee' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function paymentAssessment(): BelongsTo
    {
        return $this->belongsTo(PaymentAssessment::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'module');
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?PaymentStatus {
                $normalized = strtolower((string) $value);

                return match ($normalized) {
                    'verified' => PaymentStatus::PAID,
                    default => PaymentStatus::tryFrom($normalized),
                };
            },
            set: function (mixed $value): string {
                $normalized = $value instanceof PaymentStatus
                    ? $value->value
                    : strtolower((string) $value);

                return match ($normalized) {
                    PaymentStatus::PENDING->value => 'Pending',
                    // The persisted payment workflow intentionally records
                    // only pending, verified, rejected, paid, and cancelled.
                    // Partial, overpaid, and waived calculations remain
                    // assessment-level details and resolve to a compatible
                    // payment record status.
                    PaymentStatus::PARTIALLY_PAID->value => 'Pending',
                    PaymentStatus::PAID->value => 'Paid',
                    PaymentStatus::VERIFIED->value => 'Verified',
                    PaymentStatus::OVERPAID->value,
                    PaymentStatus::WAIVED->value => 'Paid',
                    PaymentStatus::CANCELLED->value => 'Cancelled',
                    PaymentStatus::REJECTED->value => 'Rejected',
                    default => 'Pending',
                };
            },
        );
    }

    public function getPaymentMethodAttribute(): ?string
    {
        return $this->relationLoaded('paymentMethod')
            ? $this->getRelation('paymentMethod')?->code
            : $this->paymentMethod()->value('code');
    }
}
