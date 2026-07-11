<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Advisory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'audience_type',
        'barangay_id',
        'member_type_id',
        'published_by',
        'published_at',
        'like_count',
        'dislike_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'module');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(AdvisoryReaction::class);
    }
}
