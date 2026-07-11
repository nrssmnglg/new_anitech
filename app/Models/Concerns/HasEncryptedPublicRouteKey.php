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
        if (is_numeric($value)) {
            return $this->newQuery()->whereKey((int) $value)->first();
        }

        $decodedId = $this->publicRouteKeyService()->decode((string) $value);

        if ($decodedId === null) {
            return null;
        }

        return $this->newQuery()->whereKey($decodedId)->first();
    }

    protected function publicRouteKeyService(): PublicRouteKeyService
    {
        return app(PublicRouteKeyService::class);
    }
}
