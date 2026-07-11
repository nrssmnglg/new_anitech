<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'actor_name',
        'actor_role',
        'module',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'subject_label',
        'ip_address',
        'user_agent',
        'change_summary',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'change_summary' => 'array',
            'metadata' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
