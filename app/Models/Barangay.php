<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => strtolower((string) $value),
            set: fn (mixed $value): string => match (strtolower((string) $value)) {
                'inactive' => 'Inactive',
                default => 'Active',
            },
        );
    }

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

    public function advisories(): HasMany
    {
        return $this->hasMany(Advisory::class);
    }
}
