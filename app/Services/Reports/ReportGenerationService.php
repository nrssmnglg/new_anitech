<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;

class ReportGenerationService
{
    public function begin(string $reportName, array $filters = [], ?int $requestedBy = null): array
    {
        return [
            'report_name' => $reportName,
            'filters' => $filters,
            'requested_by' => $requestedBy,
            'status' => 'pending',
        ];
    }

    public function complete(array|object $export, string $disk, string $path, ?string $fileName = null): array
    {
        $data = $this->normalize($export);

        return array_merge($data, [
            'disk' => $disk,
            'path' => $path,
            'file_name' => $fileName ?? basename($path),
            'status' => 'completed',
            'generated_at' => CarbonImmutable::now()->toDateTimeString(),
        ]);
    }

    public function fail(array|object $export, string $reason): array
    {
        $data = $this->normalize($export);
        $payload = $data['payload'] ?? [];
        $payload['failure_reason'] = $reason;

        return array_merge($data, [
            'status' => 'failed',
            'payload' => $payload,
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
