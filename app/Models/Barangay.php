<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barangay extends Model
{
    use HasEncryptedPublicRouteKey;
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    public function associations(): HasMany
    {
        return $this->hasMany(Association::class);
    }

    public function association(): HasOne
    {
        return $this->hasOne(Association::class);
    }

    public function farmers(): HasMany
    {
        return $this->hasMany(Farmer::class);
    }
}
