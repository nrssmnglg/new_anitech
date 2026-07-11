<?php

namespace App\Services\Advisories;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class AdvisoryPublicationService
{
    public function draft(array|object $payload): array
    {
        $data = $this->normalize($payload);

        return array_merge($data, [
            'slug' => $data['slug'] ?? Str::slug((string) ($data['title'] ?? 'advisory')),
            'status' => 'Draft',
        ]);
    }

    public function publish(array|object $advisory, ?int $publishedBy = null): array
    {
        $data = $this->normalize($advisory);

        return array_merge($data, [
            'status' => 'Published',
            'published_by' => $publishedBy ?? ($data['published_by'] ?? null),
            'published_at' => CarbonImmutable::now()->toDateTimeString(),
        ]);
    }

    public function unpublish(array|object $advisory): array
    {
        $data = $this->normalize($advisory);

        return array_merge($data, [
            'status' => 'Draft',
            'published_at' => null,
        ]);
    }

    private function normalize(array|object $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (method_exists($payload, 'toArray')) {
            return $payload->toArray();
        }

        return get_object_vars($payload);
    }
}
