<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Http\Request;

class AnalyticsService
{
    public function __construct(
        private readonly Request $request,
    ) {
    }

    public function track(string $eventName, array $attributes = []): AnalyticsEvent
    {
        $user = $this->request->user();

        return AnalyticsEvent::query()->create([
            'user_id' => $attributes['user_id'] ?? $user?->id,
            'farmer_id' => $attributes['farmer_id'] ?? $this->resolveFarmerId($user),
            'event_name' => $eventName,
            'module' => $attributes['module'] ?? null,
            'page' => $attributes['page'] ?? null,
            'route_name' => $attributes['route_name'] ?? null,
            'url' => $attributes['url'] ?? null,
            'session_id' => $this->request->session()->getId(),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->trimUserAgent($this->request->userAgent()),
            'properties' => $this->sanitizeProperties($attributes['properties'] ?? []),
            'occurred_at' => now(),
        ]);
    }

    private function resolveFarmerId(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        return $user->role === User::ROLE_FARMER ? $user->farmer_id : null;
    }

    private function trimUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return mb_substr($userAgent, 0, 65535);
    }

    private function sanitizeProperties(array $properties): ?array
    {
        $filtered = collect($properties)
            ->reject(fn ($value, $key): bool => $this->isSensitiveKey((string) $key) || blank($value))
            ->map(function ($value) {
                if (is_bool($value) || is_numeric($value) || is_string($value) || $value === null) {
                    return $value;
                }

                if (is_array($value)) {
                    return $value;
                }

                return (string) $value;
            })
            ->all();

        return $filtered === [] ? null : $filtered;
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array($key, [
            'email',
            'mobile_number',
            'phone',
            'contact_number',
            'address',
            'password',
            'reference_no',
            'search_term',
            'query',
            'message',
            'content',
        ], true);
    }
}
