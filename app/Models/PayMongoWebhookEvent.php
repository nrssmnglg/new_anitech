<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayMongoWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'paymongo_webhook_events';

    protected $fillable = [
        'paymongo_event_id',
        'event_type',
        'resource_id',
        'resource_type',
        'livemode',
        'status',
        'message',
        'reference_no',
        'payment_assessment_id',
        'membership_application_id',
        'renewal_request_id',
        'payment_id',
        'amount',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'livemode' => 'boolean',
        'amount' => 'decimal:2',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function paymentAssessment(): BelongsTo
    {
        return $this->belongsTo(PaymentAssessment::class);
    }

    public function membershipApplication(): BelongsTo
    {
        return $this->belongsTo(MembershipApplication::class);
    }

    public function renewalRequest(): BelongsTo
    {
        return $this->belongsTo(RenewalRequest::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
