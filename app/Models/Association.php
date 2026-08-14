<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Association extends Model
{
    use HasFactory;

    protected $fillable = [
        'barangay_id',
        'code',
        'name',
        'president_name',
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
}
