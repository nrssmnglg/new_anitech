<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MembershipTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'transaction_type',
        'status',
        'application_no',
        'year',
        'source',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'rejected_at',
        'rejection_reason',
        'rejection_details',
        'is_late',
    ];

    protected $casts = [
        'year' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'rejection_reason' => \App\Enums\MembershipApplicationRejectionReason::class,
        'is_late' => 'boolean',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FarmerDocument::class);
    }

    public function paymentAssessments(): HasMany
    {
        return $this->hasMany(PaymentAssessment::class);
    }

    public function membershipLedgers(): HasMany
    {
        return $this->hasMany(MembershipLedger::class);
    }

    public function internalNotes(): MorphMany
    {
        return $this->morphMany(InternalNote::class, 'noteable')->latest('created_at');
    }
}
