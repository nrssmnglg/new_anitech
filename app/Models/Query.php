<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Query extends Model
{
    use HasEncryptedPublicRouteKey;
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'farmer_id',
        'category_id',
        'subject',
        'message',
        'status',
        'archived_at',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QueryCategory::class, 'category_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QueryResponse::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'module');
    }

    public function internalNotes(): MorphMany
    {
        return $this->morphMany(InternalNote::class, 'noteable')->latest('created_at');
    }
}
