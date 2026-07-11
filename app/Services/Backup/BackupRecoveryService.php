<?php

namespace App\Services\Backup;

use App\Models\Farmer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupRecoveryService
{
    private const DISK = 'local';

    public function createDatabaseSnapshot(?int $requestedBy = null): array
    {
        $tables = [
            'users',
            'farmers',
            'farmer_profiles',
            'membership_transactions',
            'payment_assessments',
            'payments',
            'queries',
            'query_responses',
            'notifications',
            'notification_recipients',
            'audit_logs',
        ];

        $payload = [
            'type' => 'database_backup',
            'created_at' => now()->toIso8601String(),
            'requested_by' => $requestedBy,
            'tables' => collect($tables)
                ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->get()->map(fn ($row) => (array) $row)->all()])
                ->all(),
        ];

        return $this->storeSnapshot('database', 'database-backup', $payload, [
            'record_count' => collect($payload['tables'])->sum(fn (array $rows): int => count($rows)),
            'tables' => $tables,
        ]);
    }

    public function createFarmerExportSnapshot(Collection $farmers, array $filters = [], ?int $requestedBy = null): array
    {
        $payload = [
            'type' => 'farmer_export_snapshot',
            'created_at' => now()->toIso8601String(),
            'requested_by' => $requestedBy,
            'filters' => $filters,
            'records' => $farmers->map(function (Farmer $farmer): array {
                $farmer->loadMissing(['profile', 'barangay', 'association', 'memberType']);

                return [
                    'farmer' => $farmer->toArray(),
                    'profile' => $farmer->profile?->toArray(),
                    'barangay' => $farmer->barangay?->toArray(),
                    'association' => $farmer->association?->toArray(),
                    'member_type' => $farmer->memberType?->toArray(),
                ];
            })->values()->all(),
        ];

        return $this->storeSnapshot('export-snapshots', 'farmer-export-snapshot', $payload, [
            'record_count' => $farmers->count(),
            'filters' => $filters,
        ]);
    }

    public function createFarmerArchiveSnapshot(Collection $farmers, string $reason, ?int $requestedBy = null): array
    {
        $payload = [
            'type' => 'farmer_archive_restore_point',
            'created_at' => now()->toIso8601String(),
            'requested_by' => $requestedBy,
            'reason' => $reason,
            'records' => $farmers->map(function (Farmer $farmer): array {
                $farmer->loadMissing(['profile', 'barangay', 'association', 'memberType']);

                return [
                    'farmer_id' => $farmer->id,
                    'farmer_code' => $farmer->farmer_code,
                    'farmer' => $farmer->toArray(),
                    'profile' => $farmer->profile?->toArray(),
                ];
            })->values()->all(),
        ];

        return $this->storeSnapshot('restore-points', 'farmer-archive-restore-point', $payload, [
            'record_count' => $farmers->count(),
            'reason' => $reason,
            'farmer_ids' => $farmers->modelKeys(),
        ]);
    }

    public function listSnapshots(): array
    {
        return collect(Storage::disk(self::DISK)->files('backups/manifests'))
            ->filter(fn (string $path): bool => str_ends_with($path, '.json'))
            ->map(function (string $path): ?array {
                $decoded = json_decode((string) Storage::disk(self::DISK)->get($path), true);

                if (! is_array($decoded)) {
                    return null;
                }

                $filePath = (string) ($decoded['file_path'] ?? '');
                $exists = $filePath !== '' && Storage::disk(self::DISK)->exists($filePath);

                return [
                    'key' => $decoded['key'] ?? basename($path, '.json'),
                    'type' => $decoded['type'] ?? 'snapshot',
                    'label' => str((string) ($decoded['type'] ?? 'snapshot'))->replace('-', ' ')->headline()->toString(),
                    'created_at' => $decoded['created_at'] ?? null,
                    'requested_by' => $decoded['requested_by'] ?? null,
                    'file_name' => $decoded['file_name'] ?? basename($filePath),
                    'file_path' => $filePath,
                    'file_size' => $decoded['file_size'] ?? null,
                    'checksum' => $decoded['checksum'] ?? null,
                    'metadata' => $decoded['metadata'] ?? [],
                    'exists' => $exists,
                ];
            })
            ->filter()
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    public function getSnapshot(string $key): ?array
    {
        $path = 'backups/manifests/' . $key . '.json';

        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $decoded = json_decode((string) Storage::disk(self::DISK)->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function restoreFarmerFromSnapshot(string $key, Farmer $farmer): bool
    {
        $snapshot = $this->getSnapshot($key);

        if (! $snapshot || ! filled($snapshot['file_path'] ?? null) || ! Storage::disk(self::DISK)->exists($snapshot['file_path'])) {
            return false;
        }

        $payload = json_decode((string) Storage::disk(self::DISK)->get($snapshot['file_path']), true);

        if (! is_array($payload)) {
            return false;
        }

        $record = collect($payload['records'] ?? [])->firstWhere('farmer_id', $farmer->id);

        if (! is_array($record)) {
            return false;
        }

        DB::transaction(function () use ($farmer, $record): void {
            $farmerData = $record['farmer'] ?? [];
            $profileData = $record['profile'] ?? [];

            $farmer->forceFill(collect($farmerData)->only([
                'barangay_id',
                'association_id',
                'member_type_id',
                'membership_status',
                'record_origin',
                'registered_at',
                'activated_at',
                'inactive_at',
                'inactive_reason',
            ])->all())->save();

            if ($farmer->profile) {
                $farmer->profile->forceFill(collect($profileData)->only([
                    'first_name',
                    'middle_name',
                    'last_name',
                    'suffix',
                    'sex',
                    'civil_status',
                    'birth_date',
                    'address',
                    'mobile_number',
                ])->all())->save();
            }
        });

        return true;
    }

    private function storeSnapshot(string $directory, string $prefix, array $payload, array $metadata = []): array
    {
        $timestamp = now()->format('Ymd-His');
        $key = Str::slug($prefix) . '-' . $timestamp . '-' . Str::lower(Str::random(6));
        $fileName = $key . '.json';
        $filePath = 'backups/' . trim($directory, '/') . '/' . now()->format('Y/m') . '/' . $fileName;
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        Storage::disk(self::DISK)->put($filePath, $json);

        $manifest = [
            'key' => $key,
            'type' => $directory,
            'created_at' => now()->toIso8601String(),
            'requested_by' => $payload['requested_by'] ?? null,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => Storage::disk(self::DISK)->size($filePath),
            'checksum' => hash('sha256', $json),
            'metadata' => $metadata,
        ];

        Storage::disk(self::DISK)->put(
            'backups/manifests/' . $key . '.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        return $manifest;
    }
}
