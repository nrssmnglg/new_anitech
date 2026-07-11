<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasPublicRouteKeyFallback
{
    abstract public function getRouteKeyName(): string;

    protected function publicRouteKeyFallbackColumn(): ?string
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $routeKey = $field ?? $this->getRouteKeyName();

        $query = $this->newQuery()->where($routeKey, $value);
        $fallbackColumn = $this->publicRouteKeyFallbackColumn();

        if ($fallbackColumn !== null && $routeKey === $this->getRouteKeyName() && is_numeric($value)) {
            $query->orWhere($fallbackColumn, (int) $value);
        }

        return $query->first();
    }
}
