<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    use HasEncryptedPublicRouteKey;
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'module_type',
        'module_id',
        'original_name',
        'mime_type',
        'file_path',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function module(): MorphTo
    {
        return $this->morphTo();
    }
}
