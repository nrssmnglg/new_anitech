<?php

namespace App\Services\Queries;

use Carbon\CarbonImmutable;

class QueryWorkflowService
{
    public function submit(array|object $payload): array
    {
        $data = $this->normalize($payload);

        return array_merge($data, [
            'status' => 'New',
            'created_at' => $data['created_at'] ?? CarbonImmutable::now()->toDateTimeString(),
        ]);
    }

    public function respond(array|object $query, array|object $response): array
    {
        $queryData = $this->normalize($query);
        $responseData = $this->normalize($response);

        return [
            'query' => array_merge($queryData, ['status' => 'In Progress']),
            'response' => array_merge($responseData, [
                'responded_at' => $responseData['responded_at'] ?? CarbonImmutable::now()->toDateTimeString(),
            ]),
        ];
    }

    public function close(array|object $query): array
    {
        if ($query instanceof \Illuminate\Database\Eloquent\Model) {
            return ['status' => 'Resolved'];
        }

        $data = $this->normalize($query);

        return array_merge($data, [
            'status' => 'Resolved',
        ]);
    }

    public function reopen(array|object $query): array
    {
        if ($query instanceof \Illuminate\Database\Eloquent\Model) {
            return ['status' => 'New'];
        }

        $data = $this->normalize($query);

        return array_merge($data, [
            'status' => 'New',
        ]);
    }

    public function escalate(array|object $query): array
    {
        if ($query instanceof \Illuminate\Database\Eloquent\Model) {
            return ['status' => 'Escalated'];
        }

        $data = $this->normalize($query);

        return array_merge($data, [
            'status' => 'Escalated',
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
