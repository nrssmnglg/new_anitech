<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasPublicUuid
{
    protected static function bootHasPublicUuid(): void
    {
        static::creating(function (Model $model): void {
            if (! filled($model->getAttribute('uuid'))) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
