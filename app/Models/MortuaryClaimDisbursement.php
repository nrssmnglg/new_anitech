<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MortuaryClaimDisbursement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'mortuary_claim_id',
        'payment_method_id',
        'amount',
        'reference_no',
        'released_at',
        'released_by',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'released_at' => 'datetime',
    ];

    public function mortuaryClaim(): BelongsTo
    {
        return $this->belongsTo(MortuaryClaim::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
