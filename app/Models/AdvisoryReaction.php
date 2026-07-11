<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisoryReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'advisory_id',
        'farmer_id',
        'reaction',
    ];

    public function advisory(): BelongsTo
    {
        return $this->belongsTo(Advisory::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}
