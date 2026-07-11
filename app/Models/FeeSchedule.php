<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_type_id',
        'year',
        'membership_fee',
        'annual_due',
        'mortuary_fee',
        'renewal_deadline',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'membership_fee' => 'decimal:2',
        'annual_due' => 'decimal:2',
        'mortuary_fee' => 'decimal:2',
        'renewal_deadline' => 'date',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class);
    }

    public function paymentAssessments(): HasMany
    {
        return $this->hasMany(PaymentAssessment::class);
    }
}
