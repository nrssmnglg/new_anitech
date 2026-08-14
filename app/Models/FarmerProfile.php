<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'civil_status',
        'birth_date',
        'address',
        'mobile_number',
    ];

    protected $attributes = [
        'civil_status' => null,
        'address' => null,
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}
