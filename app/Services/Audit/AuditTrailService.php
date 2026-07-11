<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AuditTrailService
{
    public const ACTION_APPROVED = 'approved';
    public const ACTION_EDITED = 'edited';
    public const ACTION_RESPONDED = 'responded';
    public const ACTION_PROCESSED = 'processed';

    public function __construct(
        private readonly Request $request,
    ) {
    }

    public function record(
        string $module,
        string $event,
        string $description,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_role' => $actor?->role,
            'module' => $module,
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $this->subjectLabel($subject),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->trimUserAgent($this->request->userAgent()),
            'change_summary' => $this->extractChangeSummary($metadata),
            'metadata' => $this->sanitizeMetadata($metadata),
        ]);
    }

    public function recordChange(
        string $module,
        string $event,
        string $description,
        ?User $actor = null,
        ?Model $subject = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
    ): AuditLog {
        $changes = $this->buildChangeSummary($before, $after);

        if ($changes !== []) {
            $metadata['changes'] = $changes;
        }

        return $this->record($module, $event, $description, $actor, $subject, $metadata);
    }

    public function recordById(
        string $module,
        string $event,
        string $description,
        ?int $actorUserId = null,
        ?Model $subject = null,
        array $metadata = [],
    ): AuditLog {
        $actor = $actorUserId ? User::query()->find($actorUserId) : null;

        return $this->record($module, $event, $description, $actor, $subject, $metadata);
    }

    public function activityMetadata(
        string $action,
        string $label,
        array $metadata = [],
    ): array {
        return array_merge([
            'activity_action' => $action,
            'activity_label' => $label,
        ], $metadata);
    }

    private function trimUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return mb_substr($userAgent, 0, 65535);
    }

    private function subjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        $candidates = [
            $subject->getAttribute('name'),
            $subject->getAttribute('title'),
            $subject->getAttribute('application_no'),
            $subject->getAttribute('employee_id'),
            $subject->getAttribute('farmer_code'),
            $subject->getAttribute('code'),
            $subject->getAttribute('year'),
        ];

        foreach ($candidates as $value) {
            if (filled($value)) {
                return class_basename($subject) . ': ' . $this->stringify($value);
            }
        }

        return class_basename($subject) . ' #' . $subject->getKey();
    }

    private function extractChangeSummary(array $metadata): ?array
    {
        $changes = $metadata['changes'] ?? null;

        if (! is_array($changes) || $changes === []) {
            return null;
        }

        return array_values($changes);
    }

    private function sanitizeMetadata(array $metadata): ?array
    {
        if ($metadata === []) {
            return null;
        }

        unset($metadata['changes']);

        $filtered = collect($metadata)
            ->reject(fn ($value, $key): bool => blank($value) || $this->isSensitiveMetadataKey((string) $key))
            ->map(fn ($value) => $this->normalizeValue($value))
            ->all();

        return $filtered === [] ? null : $filtered;
    }

    private function buildChangeSummary(array $before, array $after): array
    {
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

        return collect($keys)
            ->map(function (string|int $key) use ($before, $after): ?array {
                $key = (string) $key;
                $from = $this->normalizeValue($before[$key] ?? null);
                $to = $this->normalizeValue($after[$key] ?? null);

                if ($from === $to) {
                    return null;
                }

                return [
                    'field' => Str::headline(str_replace('_', ' ', $key)),
                    'from' => $this->stringify($from),
                    'to' => $this->stringify($to),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof Collection) {
            return $value->all();
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return $value;
    }

    private function stringify(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'None';
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => $this->stringify($item))
                ->implode(', ');
        }

        return (string) $value;
    }

    private function isSensitiveMetadataKey(string $key): bool
    {
        return in_array($key, [
            'ip_address',
            'user_agent',
            'password',
            'current_password',
            'password_confirmation',
            'archived_user_id',
        ], true);
    }
}
