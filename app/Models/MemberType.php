<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'requires_membership_fee',
        'mortuary_eligible',
        'status',
    ];

    protected $casts = [
        'requires_membership_fee' => 'boolean',
        'mortuary_eligible' => 'boolean',
    ];

    public function farmers(): HasMany
    {
        return $this->hasMany(Farmer::class);
    }

    public function feeSchedules(): HasMany
    {
        return $this->hasMany(FeeSchedule::class);
    }

    public function advisories(): HasMany
    {
        return $this->hasMany(Advisory::class);
    }
}
