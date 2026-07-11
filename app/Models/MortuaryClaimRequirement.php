<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MortuaryClaimRequirement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'mortuary_claim_id',
        'requirement_type_id',
        'is_received',
        'verified_by',
        'verified_at',
        'remarks',
    ];

    protected $casts = [
        'is_received' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function mortuaryClaim(): BelongsTo
    {
        return $this->belongsTo(MortuaryClaim::class);
    }

    public function requirementType(): BelongsTo
    {
        return $this->belongsTo(ClaimRequirementType::class, 'requirement_type_id');
    }
}
