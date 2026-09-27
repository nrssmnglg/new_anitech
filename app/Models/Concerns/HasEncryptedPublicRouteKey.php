<?php

namespace App\Models\Concerns;

use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Database\Eloquent\Model;

trait HasEncryptedPublicRouteKey
{
    public function getRouteKey(): string
    {
        return $this->publicRouteKeyService()->encode($this->getKey());
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->resolveRouteBindingQuery($this->newQuery(), $value, $field)->first();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if (is_numeric($value)) {
            return $query->where($field ?? $this->getRouteKeyName(), (int) $value);
        }

        $decodedId = $this->publicRouteKeyService()->decode((string) $value);

        if ($decodedId === null) {
            return $query->whereNull($this->getRouteKeyName());
        }

        return $query->where($field ?? $this->getRouteKeyName(), $decodedId);
    }

    protected function publicRouteKeyService(): PublicRouteKeyService
    {
        return app(PublicRouteKeyService::class);
    }
}
