<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Association extends Model
{
    use HasEncryptedPublicRouteKey;
    use HasFactory;

    protected $fillable = [
        'barangay_id',
        'code',
        'name',
        'president_name',
        'president_farmer_id',
        'status',
    ];

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function farmers(): HasMany
    {
        return $this->hasMany(Farmer::class);
    }

    public function president(): BelongsTo
    {
        return $this->belongsTo(Farmer::class, 'president_farmer_id');
    }
}
